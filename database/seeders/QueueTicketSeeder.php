<?php

namespace Database\Seeders;

use App\Models\QueueTicket;
use Illuminate\Database\Seeder;

class QueueTicketSeeder extends Seeder
{
    public function run(): void
    {
        $priorities = ['pwd_senior_pregnant', 'student', 'parent'];
        $cashierPriorities = ['pwd_senior_pregnant', 'student', 'parent', 'clearance'];
        
        // 12 Cashier tickets
        for ($i = 1; $i <= 12; $i++) {
            $priority = $cashierPriorities[($i - 1) % 4];
            if ($priority === 'clearance') {
                $prefix = 'CLR';
            } else {
                $prefix = 'C' . ($priority === 'student' ? 'S' : 'P');
            }
            $code = $prefix . '-' . str_pad((string)$i, 3, '0', STR_PAD_LEFT);
            
            QueueTicket::firstOrCreate(
                ['code' => $code],
                [
                    'service_type' => 'cashier',
                    'priority' => $priority,
                    'status' => 'pending',
                    'counter_id' => null,
                    'hold_count' => 0,
                    'called_times' => 0,
                ]
            );
        }
        
        // 10 Registrar tickets
        for ($i = 1; $i <= 10; $i++) {
            $priority = $priorities[($i - 1) % 3];
            $prefix = 'R' . strtoupper(substr($priority, 0, 1));
            $code = $prefix . '-' . str_pad((string)$i, 3, '0', STR_PAD_LEFT);
            
            QueueTicket::firstOrCreate(
                ['code' => $code],
                [
                    'service_type' => 'registrar',
                    'priority' => $priority,
                    'status' => 'pending',
                    'counter_id' => null,
                    'hold_count' => 0,
                    'called_times' => 0,
                ]
            );
        }
    }
}
