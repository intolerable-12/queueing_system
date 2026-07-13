# Printing Setup: Linux Laravel Server to Windows USB Printer (Python Only)

This project supports HTTP printing through the Python print server at PrintServer/print-server.py.

Do not use Node.js print-server variants for this project documentation.

## 1. Architecture

Ubuntu/Linux Laravel server -> Windows PC (Python print server) -> USB EPSON TM-T82II

- Laravel sends POST /print
- Windows Python service checks printer health at /health
- Printer is connected to Windows through USB

## 2. Linux Laravel Configuration

Set these values in .env:

```dotenv
PRINTER_ENABLED=true
SKIP_PRINTER_VALIDATION=false
PRINTER_TYPE=http
PRINTER_TARGET=http://WINDOWS_IP:3000/print
PRINTER_PORT=9100
```

Notes:
- Replace WINDOWS_IP with the actual Windows PC IP
- Keep SKIP_PRINTER_VALIDATION=false in production
- Validation uses /health and blocks ticket creation when printer is unavailable

## 3. Windows PC Setup (Python)

Follow all steps in [WINDOWS_PRINT_SERVER.md](WINDOWS_PRINT_SERVER.md).

Summary:

1. Install Python 3.11+
2. Create C:\PrintServer
3. Copy PrintServer/print-server.py to C:\PrintServer\print-server.py
4. (Optional) copy Lourdes_final.png for logo printing
5. Create virtual environment and install packages:

```powershell
cd C:\PrintServer
python -m venv .venv
.\.venv\Scripts\Activate.ps1
python -m pip install --upgrade pip
pip install flask pywin32 pillow
```

6. Confirm PRINTER_NAME matches installed Windows printer name
7. Start server:

```powershell
python C:\PrintServer\print-server.py
```

8. Allow inbound TCP 3000 in Windows Firewall

## 4. Endpoint Behavior

### 4.1 GET /health

Used by Laravel before issuing ticket when printer validation is enabled.

Expected JSON fields:
- printer
- can_print (true/false)
- issues (array)
- raw_status (integer status bits)

When can_print=false, kiosk ticket issuance is blocked to avoid unprinted tickets.

### 4.2 POST /print

Expected payload:

```json
{
  "ticket": {
    "code": "CS-001",
    "service_type": "cashier",
    "priority": "student",
    "created_at": "2026-01-01T08:00:00+08:00"
  }
}
```

Success response:

```json
{
  "success": true,
  "job_id": 123
}
```

## 5. Connectivity and Functional Tests

### 5.1 From Windows PC

```powershell
curl http://localhost:3000/health
```

### 5.2 From Linux server

```bash
curl http://WINDOWS_IP:3000/health

curl -X POST http://WINDOWS_IP:3000/print \
  -H "Content-Type: application/json" \
  -d '{"ticket":{"code":"CS-001","service_type":"cashier","priority":"student","created_at":"2026-01-01T08:00:00+08:00"}}'
```

### 5.3 From Laravel app

1. Open kiosk
2. Generate ticket
3. Check Laravel log for health/print records:

```bash
tail -f storage/logs/laravel.log
```

## 6. Run Automatically on Windows Boot (Recommended)

Use the Startup folder method (shell:startup) to auto-run C:\PrintServer\print-server.py at boot.

See full steps in [WINDOWS_PRINT_SERVER.md](WINDOWS_PRINT_SERVER.md).

## 7. Troubleshooting

### 7.1 /health cannot be reached

1. Verify Python print process is running after boot
2. Check firewall rule for TCP 3000
3. Verify Windows IP and network/subnet
4. Test from both Windows and Linux hosts

### 7.2 /health returns can_print=false

1. Confirm printer power and USB cable
2. Confirm paper and cover status
3. Confirm PRINTER_NAME exact match via Get-Printer
4. Clear stuck print jobs and restart print spooler if needed

### 7.3 /print fails

1. Verify pywin32 installed in active environment
2. Run script in foreground and inspect console traceback
3. Ensure printer is not offline/paused in Windows printer queue

### 7.4 Laravel still does not print

1. Confirm PRINTER_TYPE=http
2. Confirm PRINTER_TARGET points to /print endpoint
3. Confirm SKIP_PRINTER_VALIDATION is correct for the environment
4. Review Laravel logs for HTTP connection/timeout errors

## 8. Security Recommendations

1. Restrict firewall rule to Laravel server IP
2. Keep print server on private network only
3. Do not expose port 3000 to public internet
4. Use strong host hardening on Windows machine

## 9. Operational Recommendation

Standardize on a single print server implementation:
- PrintServer/print-server.py

This keeps printer health checks and payload format aligned with the current Laravel printing logic.
