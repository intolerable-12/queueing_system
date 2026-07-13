<?php

namespace App\Http\Controllers;

use App\Models\Counter;
use App\Models\QueueTicket;
use App\Models\QueueCutoff;
use App\Events\TicketUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Mike42\Escpos\EscposImage;

class KioskController extends Controller
{

    // registrar programs and their assigned counter numbers, MODIFY THE NUMBERS HERE TO CHANGE COUNTER ASSIGNMENTS FOR EACH PROGRAM.
    // modify "registrar_number" 1 = Counter 1, 2 = Counter 2, etc. 
    protected const REGISTRAR_PROGRAMS = [
        'senior_high_school' => [
            'label' => 'Senior High School',
            'registrar_number' => 1,
        ],
        'aisacct_it' => [
            'label' => 'AISACCT-IT',
            'registrar_number' => 2,
        ],
        'allied_health' => [
            'label' => 'Allied Health',
            'registrar_number' => 3,
        ],
        'arts_science' => [
            'label' => 'Arts & Science',
            'registrar_number' => 3,
        ],
        'business_education' => [
            'label' => 'Business Education',
            'registrar_number' => 2,
        ],
        'hotel_management' => [
            'label' => 'Hospitality Management',
            'registrar_number' => 2,
        ],
        'social_work' => [
            'label' => 'Social Work',
            'registrar_number' => 3,
        ],
        'teacher_education' => [
            'label' => 'Teacher Education',
            'registrar_number' => 3,
        ],
        'graduate_school' => [
            'label' => 'Graduate School',
            'registrar_number' => 4,
        ],
    ];

    public function index()
    {
        $closed = QueueCutoff::whereDate('cutoff_date', today())

            ->where('is_closed', true)

            ->exists();

        return view('kiosk.index', compact('closed'));
    }

    public function chooseService(Request $request)
    {
        $validated = $request->validate([
            'service_type' => 'required|in:cashier,registrar',
        ]);

        return view('kiosk.priority', ['service' => $validated['service_type']]);
    }

    public function showRegistrarPrograms()
    {
        return view('kiosk.registrar-program', [
            'columnOnePrograms' => [
                'senior_high_school',
                'aisacct_it',
                'allied_health',
                'arts_science',
                'business_education',
            ],
            'columnTwoPrograms' => [
                'hotel_management',
                'social_work',
                'teacher_education',
                'graduate_school',
            ],
            'programs' => self::REGISTRAR_PROGRAMS,
        ]);
    }

    public function choosePriority(Request $request)
    {
        $validated = $request->validate([
            'service' => 'required|in:cashier,registrar',
            'program' => 'nullable|string|in:' . implode(',', array_keys(self::REGISTRAR_PROGRAMS)),
        ]);

        return view('kiosk.priority', $validated);
    }

    public function issueTicket(Request $request)
    {

        $closed = QueueCutoff::whereDate('cutoff_date', today())

            ->where('is_closed', true)

            ->exists();

        if ($closed) {

            return redirect()

                ->route('kiosk.index')

                ->withErrors([

                    'cutoff' => 'Queue ticket issuance has ended for today.'

                ]);

        }


        $validated = $request->validate([
            'service' => 'required|in:cashier,registrar',
            'program' => 'nullable|string|in:' . implode(',', array_keys(self::REGISTRAR_PROGRAMS)),
            'priority' => 'required|in:pwd_senior_pregnant,student,parent',
        ]);

        $programKey = null;
        $designatedCounterId = null;

        // For registrar, don't assign to specific counter - any registrar counter can service it
        // (For cashier, $designatedCounterId remains null as well, meaning any cashier can service it)

        // If printing is enabled, ensure printer is online / ready before generating a code
        // Skip validation if SKIP_PRINTER_VALIDATION is enabled (for development/testing)
        if (config('app.printer_enabled', false) && !config('app.skip_printer_validation', false)) {
            $printerType = config('app.printer_type', 'windows');

            // For HTTP printing, ask the Windows print server /health endpoint
            if ($printerType === 'http') {
                if (!$this->isHttpPrinterReady()) {
                    return redirect()->route('kiosk.index')
                        ->withErrors([
                            'printer' => 'Printer is not connected or no paper is left. Please ask staff for assistance.',
                        ]);
                }
            } else {
                // Direct Windows / network printing: use strict PowerShell-based status
                $printerShareName = 'EPSON TM-T82II Receipt';
                if (!$this->isPrinterOnlineStrict($printerShareName)) {
                    return redirect()->route('kiosk.index')
                        ->withErrors([
                            'printer' => 'Printer is not connected or no paper is left. Please ask staff for assistance.',
                        ]);
                }
            }
        }

        // Generate prefix and sequence bucket
        // Student keeps S-series, all non-student priorities share P-series
        $isStudent = $validated['priority'] === 'student';
        $priorityPrefix = $isStudent ? 'S' : 'P';

        $prefix = strtoupper(substr($validated['service'], 0, 1)) . $priorityPrefix;

        // Reset daily: count only today's tickets for this service and bucket
        $countQuery = QueueTicket::where('service_type', $validated['service'])
            ->whereDate('created_at', today());

        if ($isStudent) {
            $countQuery->where('priority', 'student');
        } else {
            $countQuery->where('priority', '!=', 'student');
        }

        $countToday = $countQuery->count() + 1;

        // Create the sequence number
        $sequence = str_pad((string) $countToday, 3, '0', STR_PAD_LEFT);

        // Final Code ( CS-001)
        $code = $prefix . '-' . $sequence;

        // Save ticket
        $ticket = QueueTicket::create([
            'code' => $code,
            'service_type' => $validated['service'],
            'program' => $programKey,
            'priority' => $validated['priority'],
            'designated_counter_id' => $designatedCounterId,
        ]);

        event(new TicketUpdated('created', $ticket));

        if (config('app.printer_enabled', false)) {
            $this->printTicket($ticket);
        }

        return redirect()->route('kiosk.ticket', $ticket);
    }

    protected function getRegistrarCounterIdByNumber(int $registrarNumber): ?int
    {
        if ($registrarNumber < 1) {
            return null;
        }

        $counterId = Counter::where('type', 'registrar')
            ->where('name', (string) $registrarNumber)
            ->value('id');

        if ($counterId) {
            return (int) $counterId;
        }

        return Counter::where('type', 'registrar')
            ->orderBy('id')
            ->skip($registrarNumber - 1)
            ->value('id');
    }

    public function showTicket(QueueTicket $ticket)
    {
        return view('kiosk.ticket', ['ticket' => $ticket]);
    }

    // Print ticket using ESC/POS or HTTP
    protected function printTicket(QueueTicket $ticket)
    {
        try {
            Log::info('Starting print job for ticket: ' . $ticket->code);

            $printerType = config('app.printer_type', 'windows');

            // Use HTTP printing if configured
            if ($printerType === 'http') {
                return $this->printViaHttp($ticket);
            }

            // Original ESC/POS printing for Windows
            // Guard: Skip printing if printer is offline to avoid OS spooling backlog
            $printerShareName = 'EPSON TM-T82II Receipt'; // Windows printer name
            $smbPath = "smb://localhost/EPSONReceipt"; // Original working path

            // Strict check before sending any job to Windows spooler; prevents queue buildup
            if (!$this->isPrinterOnlineStrict($printerShareName)) {
                Log::warning('Printer appears offline. Skipping print for ticket: ' . $ticket->code);
                return; // Do not attempt to send job to spooler
            }

            // Try using original smb path first, then fall back to share name
            try {
                $connector = new \Mike42\Escpos\PrintConnectors\WindowsPrintConnector($smbPath);
            } catch (\Throwable $e) {
                Log::warning('SMB path connector failed, falling back to share name: ' . $e->getMessage());
                $connector = new \Mike42\Escpos\PrintConnectors\WindowsPrintConnector($printerShareName);
            }

            $printer = new \Mike42\Escpos\Printer($connector);

            // Load and print logo image centered
            $logoPath = public_path('images/Lourdes_final.png');

            if (file_exists($logoPath)) {
                try {
                    $manager = new ImageManager(new Driver());
                    $img = $manager->read($logoPath);

                    $img->scale(width: 120);
                    $img->greyscale();
                    $img->contrast(20);

                    $tempPath = sys_get_temp_dir() . '/receipt_logo_' . time() . '.png';
                    $img->save($tempPath);

                    $escposImg = EscposImage::load($tempPath, false);

                    $printer->setJustification(\Mike42\Escpos\Printer::JUSTIFY_CENTER);
                    $printer->graphics($escposImg);

                    @unlink($tempPath);
                } catch (\Exception $e) {
                    Log::warning('Logo print failed: ' . $e->getMessage());
                }
            }

            // CENTER ALL TEXT
            $printer->setJustification(\Mike42\Escpos\Printer::JUSTIFY_CENTER);

            $printer->text("\n");
            $printer->text("Your Queue Code\n");


            // BIG + BOLD QUEUE CODE
            // ULTRA BIG + ULTRA BOLD QUEUE CODE
            $printer->setJustification(\Mike42\Escpos\Printer::JUSTIFY_CENTER);

            // Increase font size (CHANGE 4,4 to 6,6 or 8,8 if you want even bigger)
            $printer->setTextSize(4, 4);
            $printer->setEmphasis(true); // bold ON

            $printer->text($ticket->code . "\n");

            // Reset back to normal
            $printer->setTextSize(1, 1);
            $printer->setEmphasis(false);


            $printer->text($ticket->created_at->format('M. j, Y h:i A') . "\n");


            $printer->selectPrintMode(\Mike42\Escpos\Printer::MODE_EMPHASIZED);
            $printer->text("Service: " . ucfirst($ticket->service_type) . "\n");
            $printer->selectPrintMode();

            $printer->feed(1);
            $printer->cut();
            $printer->close();

            Log::info('Print job completed successfully');
        } catch (\Throwable $e) {
            Log::error('Print failed: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
        }
    }

    // Print ticket via HTTP to remote print server (Windows 11 computer)
    protected function printViaHttp(QueueTicket $ticket)
    {
        try {
            $printerUrl = config('app.printer_target');

            if (!$printerUrl) {
                Log::error('PRINTER_TARGET not configured in .env');
                return;
            }

            Log::info('Sending print job via HTTP to: ' . $printerUrl);

            // Prepare print data in the format expected by the print server
            $printData = [
                'ticket' => [
                    'code' => $ticket->code,
                    'service_type' => $ticket->service_type,
                    'priority' => $ticket->priority,
                    // Use ISO string so Python can print it directly
                    'created_at' => $ticket->created_at->toIso8601String(),
                ],
            ];

            // Send HTTP request to Windows print server
            $response = \Illuminate\Support\Facades\Http::timeout(5)
                ->post($printerUrl, $printData);

            if ($response->successful()) {
                Log::info('Print job sent successfully via HTTP');
                $responseData = $response->json();
                Log::info('Print server response: ' . json_encode($responseData));
            } else {
                Log::error('Print server returned error: ' . $response->status() . ' - ' . $response->body());
            }

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Cannot connect to print server: ' . $e->getMessage());
            Log::error('Make sure the print server is running on the Windows 11 computer');
        } catch (\Throwable $e) {
            Log::error('HTTP print failed: ' . $e->getMessage());
        }
    }

    /**
     * Check HTTP-based printer health by calling the Windows print server /health endpoint.
     * Returns true only when the remote server reports it can actually print.
     */
    protected function isHttpPrinterReady(): bool
    {
        try {
            $printerTarget = config('app.printer_target');

            if (!$printerTarget) {
                Log::error('PRINTER_TARGET not configured in .env; treating HTTP printer as unavailable.');
                return false;
            }

            // Derive health URL from the print URL, e.g. http://host:3000/print -> http://host:3000/health
            $base = rtrim($printerTarget, '/');
            $healthUrl = preg_replace('#/print$#', '/health', $base);
            if (!$healthUrl) {
                $healthUrl = $base . '/health';
            }

            Log::info('Checking HTTP printer health at: ' . $healthUrl);

            $response = \Illuminate\Support\Facades\Http::timeout(3)->get($healthUrl);

            if (!$response->successful()) {
                Log::warning('Printer health check failed with status: ' . $response->status());
                return false;
            }

            $data = $response->json() ?: [];

            // Preferred: Python print server returns explicit can_print flag
            if (array_key_exists('can_print', $data)) {
                return (bool) $data['can_print'];
            }

            // Backwards compatibility: if only "status":"online" is present
            if (isset($data['status']) && strtolower((string) $data['status']) === 'online') {
                return true;
            }

            // If we cannot be sure, block ticket generation to avoid unprinted tickets
            return false;
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Cannot reach HTTP printer health endpoint: ' . $e->getMessage());
            return false;
        } catch (\Throwable $e) {
            Log::error('HTTP printer health check failed: ' . $e->getMessage());
            return false;
        }
    }

    // Check Windows printer status using PowerShell; returns true if not Offline
    protected function isPrinterOnline(string $printerName): bool
    {
        try {
            // Query printer status; be tolerant of environments where PowerShell or permissions block the call.
            // We'll treat failures as "online" to avoid false negatives at the kiosk.
            // Use single-quoted PHP string to avoid PHP interpolating PowerShell variables like $p
            $psCommand = '($p = Get-Printer -Name \'" . addslashes($printerName) . "\' -ErrorAction SilentlyContinue); if ($p) { if ($p.WorkOffline) { \"Offline\" } else { $p.PrinterStatus } } else { \"Unknown\" }';
            $cmd = 'powershell.exe -NoProfile -ExecutionPolicy Bypass -Command "' . $psCommand . '"';

            $output = @shell_exec($cmd);
            if ($output === null) {
                // If we cannot query status, assume online to prevent blocking kiosk usage
                Log::warning('Printer status query returned null. Assuming printer is online.');
                return true;
            }

            $status = strtolower(trim($output));
            // Consider only explicit "offline" as offline; everything else is treated as online
            if ($status === 'offline') {
                return false;
            }

            // Some environments report "unknown" or numeric codes; treat them as online to avoid false blocks
            return true;
        } catch (\Throwable $e) {
            // On any exception, default to online to avoid blocking kiosk
            Log::warning('Printer status check failed: ' . $e->getMessage() . ' — assuming printer is online.');
            return true;
        }
    }

    // Strict variant used right before spooling: only proceeds when clearly online/idle/printing
    protected function isPrinterOnlineStrict(string $printerName): bool
    {
        try {
            // Check if shell_exec is available
            if (!function_exists('shell_exec')) {
                Log::warning('shell_exec is disabled. Cannot check printer status. Treating as online to allow printing.');
                return true; // Allow printing if we can't check status
            }

            $escapedName = addslashes($printerName);

            // Check 1: Get printer status
            $psCommand = "(Get-Printer -Name '$escapedName' -ErrorAction SilentlyContinue).PrinterStatus";
            $cmd = 'powershell.exe -NoProfile -ExecutionPolicy Bypass -Command "' . $psCommand . '"';

            Log::info('Executing PowerShell command for printer status');

            $output = @shell_exec($cmd);

            Log::info('PowerShell printer status output: ' . var_export($output, true));

            if ($output === null || $output === false) {
                Log::warning('Printer status query returned null/false. Treating as offline to prevent printing.');
                return false; // Block printing if we can't check status
            }

            $statusRaw = trim($output);

            if ($statusRaw === '') {
                Log::warning('Printer status query returned empty. Treating as offline to prevent printing.');
                return false; // Block if status unknown
            }

            Log::info('Printer status value: ' . $statusRaw);

            // Check for problem statuses that should block printing
            $statusLower = strtolower($statusRaw);

            // Block on: NotAvailable (disconnected/out of paper), Error, Offline, Paused
            // Also check for compound statuses like "PaperOut, NotAvailable"
            $blockedKeywords = ['notavailable', 'paperout', 'error', 'offline', 'paused'];

            foreach ($blockedKeywords as $keyword) {
                if (strpos($statusLower, $keyword) !== false) {
                    Log::info('Printer status indicates problem (contains "' . $keyword . '"): ' . $statusRaw . ' - blocking printing');
                    return false;
                }
            }

            // Check 2: Look for stuck/error jobs in the queue
            $psJobCheck = "(Get-PrintJob -PrinterName '$escapedName' -ErrorAction SilentlyContinue | Where-Object { \$_.JobStatus -match 'Error|Paused|Blocked|Retained' }).Count";
            $cmdJobCheck = 'powershell.exe -NoProfile -ExecutionPolicy Bypass -Command "' . $psJobCheck . '"';

            Log::info('Checking for error jobs in queue');

            $jobOutput = @shell_exec($cmdJobCheck);
            $errorJobCount = intval(trim($jobOutput ?? '0'));

            Log::info('Error job count: ' . $errorJobCount);

            if ($errorJobCount > 0) {
                Log::info('Found ' . $errorJobCount . ' stuck/error jobs in queue - blocking printing');
                return false;
            }

            // Accept: Normal, Idle, Printing
            Log::info('Printer status accepted as online: ' . $statusRaw);
            return true;
        } catch (\Throwable $e) {
            Log::warning('Strict printer status check failed: ' . $e->getMessage() . ' — treating as offline.');
            return false;
        }
    }
}
