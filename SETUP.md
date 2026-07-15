# School Queueing System

Laravel-based queueing system with three interfaces: **Kiosk**, **TV Monitor**, and **Operator** (Cashier/Registrar).

## Features

- **Kiosk**: Students/parents select service (Cashier/Registrar), priority (PWD/Senior/Pregnant, Student, Parent), get queue code printed via EPSON TM-T82II.
- **TV Monitor**: Split-screen showing 4 counters per service with "now serving" codes and TTS announcements.
- **Operator**: Cashier/Registrar select window, serve next ticket, on-hold functionality, auto-remove oldest on-hold after 3 nexts.
- **Real-time**: Laravel Reverb broadcasts ticket state changes to monitor and operators.
- **Printer**: EPSON TM-T82II printing via HTTP to the Windows Python print server (`PrintServer/print-server.py`).

## Requirements

- PHP 8.2+
- Composer
- MySQL or compatible DB
- Node.js + npm (for Vite/assets)

## Installation

1. Clone and install dependencies:
```bash
composer install
npm install
```

2. Configure `.env`:
```dotenv
DB_CONNECTION=mysql
DB_DATABASE=queueing_system
BROADCAST_CONNECTION=reverb
PRINTER_ENABLED=true
PRINTER_TYPE=http
PRINTER_TARGET=http://WINDOWS_IP:3000/print
```

3. Run migrations and seed counters:
```bash
php artisan migrate
php artisan db:seed
```

4. Build front-end assets:
```bash
npm run build
```

## Running

Start three terminals:

**Terminal 1: Laravel Reverb (WebSocket server)**
```bash
php artisan reverb:start
```

**Terminal 2: Queue Worker (for broadcasting)**
```bash
php artisan queue:work
```

**Terminal 3: Laravel Dev Server**
```bash
php artisan serve
```

## Routes

- `/kiosk` - Kiosk interface (issue tickets)
- `/monitor` - TV Monitor (display now serving)
- `/counter` - Operator interface (select counter, manage queue)

## Testing

Run feature tests:
```bash
php artisan test
```

## Printer Setup

For EPSON TM-T82II:
- **Recommended/Supported**: Use Python HTTP print server on the Windows USB-printer host.
- Set `PRINTER_TYPE=http` and `PRINTER_TARGET=http://WINDOWS_IP:3000/print`.
- Install Python service dependencies on Windows: `flask`, `pywin32`, `pillow`.
- Follow `WINDOWS_PRINT_SERVER.md` for complete setup.

## Architecture

- **Models**: `QueueTicket`, `Counter`
- **Events**: `TicketUpdated` broadcasts on channels `queue.cashier`, `queue.registrar`
- **Controllers**: `KioskController`, `MonitorController`, `CounterController`
- **Front-end**: Bootstrap 5, Laravel Echo (Reverb), Web Speech API (TTS)
