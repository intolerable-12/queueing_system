# Queueing System Documentation

## 1. Project Overview

The Queueing System is a Laravel 12 web application for managing service queues across three interfaces:

1. Kiosk interface for ticket generation
2. Counter/operator interface for serving tickets
3. Monitor interface for live queue display, announcements, and media

The system supports Cashier and Registrar queues, role-based operator access, real-time updates through Laravel Reverb, and receipt printing through the Windows Python HTTP print server.

---

## 2. Core Features

### 2.1 Kiosk

1. Service selection: cashier or registrar
2. Priority selection:
   - pwd_senior_pregnant
   - student
   - parent
3. Ticket code generation with daily reset
4. Optional printer health validation before ticket issuance
5. Receipt printing support with logo and ESC/POS formatting

### 2.2 Counter / Operator

1. Authenticated users are bound to assigned counters
2. Actions:
   - next
   - hold
   - call again
   - remove hold
3. Alternating serving strategy between student and non-student queues
4. Auto-removal of oldest on-hold ticket every 3 Next presses
5. Rate limiting/cooldown on critical actions

### 2.3 Monitor (TV)

1. Live now-serving updates per counter
2. Counter availability state updates
3. Speech synthesis announcements
4. Notification beep on call
5. Media slideshow (images, videos, GIF)
6. Configurable marquee text

### 2.4 Admin Session Control

1. List active sessions
2. Terminate specific session
3. Terminate all sessions of a user
4. Release claimed counter when session is terminated

---

## 3. Technology Stack and Tools Used

## 3.1 Backend

1. PHP 8.2+
2. Laravel Framework 12
3. MySQL (primary runtime database)
4. Laravel Reverb (WebSocket broadcasting)
5. Laravel Queue (database driver)
6. Pest + PHPUnit (testing)

## 3.2 Frontend

1. Blade templates
2. Vite 7
3. Tailwind CSS 4 (build tooling available)
4. Bootstrap 5 (CDN in views)
5. Axios
6. Laravel Echo + Pusher JS client (for Reverb transport)
7. SortableJS (media reordering)

## 3.3 Printing

1. mike42/escpos-php (PHP ESC/POS printer support)
2. Python print server (standard and supported):
   - Flask
   - pywin32
   - Pillow

## 3.4 Development and Operations Tools

1. Composer
2. npm
3. Artisan CLI
4. PowerShell (Windows automation and printer checks)
5. systemd (Linux deployment for Reverb and workers)

---

## 4. High-Level Architecture

### 4.1 Runtime Components

1. Laravel app serves HTTP routes for kiosk, counter, monitor, admin
2. MySQL stores users, counters, tickets, sessions, media metadata
3. Reverb pushes real-time events to browsers
4. Optional queue worker processes queued jobs
5. Printer integration uses HTTP to the Windows Python print server endpoint

### 4.2 Event-Driven Flow

1. Kiosk issues ticket -> TicketUpdated(created)
2. Counter serves/holds/completes ticket -> TicketUpdated(serving/on_hold/done)
3. Login/logout/session termination changes counter availability -> CounterStatusChanged
4. Media/marquee updates -> MediaUpdated

### 4.3 Broadcast Channels

1. queue.cashier
2. queue.registrar
3. counter.status
4. monitor.media

---

## 5. Project Structure

Important directories and files:

1. app/Http/Controllers
   - KioskController.php
   - CounterController.php
   - MonitorController.php
   - MediaController.php
   - RestartQueueController.php
   - AuthController.php
   - Admin/SessionController.php
2. app/Events
   - TicketUpdated.php
   - CounterStatusChanged.php
   - MediaUpdated.php
3. app/Models
   - QueueTicket.php
   - Counter.php
   - User.php
   - MonitorMedia.php
   - MonitorSetting.php
4. database/migrations
   - counters, users, queue_tickets, monitor_media, monitor_settings, role/program updates
5. database/seeders
   - DatabaseSeeder.php
   - AdminUserSeeder.php
   - QueueTicketSeeder.php
6. resources/views
   - kiosk, monitor, operator, media, auth, admin
7. routes
   - web.php
   - channels.php
8. Print server assets
   - PrintServer/print-server.py

---

## 6. Prerequisites

## 6.1 Minimum

1. PHP 8.2+
2. Composer
3. Node.js + npm
4. MySQL 8+ (or compatible)
5. Git

## 6.2 If Using HTTP Print Server (Windows machine)

Use Python 3 environment with Flask, pywin32, and Pillow for PrintServer/print-server.py.

---

## 7. Environment Configuration

Use .env as your main runtime config.

Key categories:

1. Application
   - APP_ENV
   - APP_DEBUG
   - APP_URL
2. Database
   - DB_CONNECTION, DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD
3. Session and cache
   - SESSION_DRIVER=database
   - CACHE_STORE=database
4. Queues
   - QUEUE_CONNECTION=database
5. Broadcasting / Reverb
   - BROADCAST_CONNECTION=reverb
   - REVERB_APP_ID, REVERB_APP_KEY, REVERB_APP_SECRET
   - REVERB_HOST, REVERB_PORT, REVERB_SCHEME
   - VITE_REVERB_* variables for frontend Echo
6. Printing
   - PRINTER_ENABLED=true|false
   - SKIP_PRINTER_VALIDATION=true|false
   - PRINTER_TYPE=http
   - PRINTER_TARGET (http endpoint to Windows Python print server)
   - PRINTER_PORT (kept for compatibility; not used by HTTP mode)

Security note:

1. Do not commit production secrets
2. Keep APP_DEBUG=false in production
3. Rotate leaked credentials immediately

---

## 8. Local Setup (Windows/Linux)

## 8.1 Install Dependencies

```bash
composer install
npm install
```

## 8.2 Create Environment File

```bash
cp .env.example .env
```

Then update database, broadcast, and printing values.

## 8.3 Generate App Key

```bash
php artisan key:generate
```

## 8.4 Run Migrations and Seeders

```bash
php artisan migrate
php artisan db:seed
```

This seeds:

1. 4 cashier counters and 4 registrar counters
2. cashier1..cashier4 users
3. registrar1..registrar4 users
4. admin user (username: admin, password: admin12345)
5. default monitor marquee text

## 8.5 Create Public Storage Link (for monitor media)

```bash
php artisan storage:link
```

## 8.6 Build or Run Frontend Assets

```bash
npm run build
```

or for development:

```bash
npm run dev
```

---

## 9. Running the System

Recommended processes:

1. Laravel app server
2. Reverb server
3. Queue worker (optional but recommended)
4. Vite dev server (only in development)

### 9.1 Commands

Terminal A:

```bash
php artisan serve
```

Terminal B:

```bash
php artisan reverb:start
```

Terminal C:

```bash
php artisan queue:work
```

Terminal D (optional for hot reload):

```bash
npm run dev
```

Windows convenience script:

```bat
start.bat
```

---

## 10. Access Points

Default local URLs:

1. Kiosk: /kiosk
2. Monitor: /monitor
3. Counter: /counter
4. Login: /login
5. Admin sessions: /admin/sessions
6. Media manager:
   - /media (counter context)
   - /admin/media (admin context)

---

## 11. Authentication and Roles

Roles:

1. cashier
2. registrar
3. admin

Behavior:

1. Counter users are redirected to assigned counter after login
2. Admin users are redirected to admin sessions control
3. Duplicate active session for same username is blocked at login
4. Logout releases claimed counter and broadcasts availability change
5. CSRF is excluded for /logout route

---

## 12. Queue Logic

## 12.1 Ticket Code Format

Pattern:

SERVICE_LETTER + PRIORITY_BUCKET + sequence

Examples:

1. CS-001: Cashier Student
2. CP-001: Cashier non-student bucket
3. RS-001: Registrar Student
4. RP-001: Registrar non-student bucket

Rules:

1. Sequence is daily (resets each day)
2. Student has separate count bucket
3. Non-student priorities share one bucket

## 12.2 Serving Strategy

For each service type:

1. Alternate between student and non-student queues when both exist
2. If one bucket is empty, serve from the other
3. Uses service-level lock to reduce race conditions across multiple counters

## 12.3 Hold and Recall

1. Hold sets ticket to on_hold and serves next pending ticket automatically
2. Call again can bring on_hold ticket back to serving
3. Remove hold marks ticket done

## 12.4 Auto-Cleanup Rule

1. Every 3 Next presses, oldest on_hold ticket is marked done

Note:

1. Current implementation uses an in-memory static counter. This counter resets with process restart and is not globally persisted across workers.

---

## 13. Printing System

## 13.1 Supported Mode

1. http
   - Sends print jobs to the Windows Python print server (PrintServer/print-server.py)

## 13.2 Health Validation

When PRINTER_ENABLED=true and SKIP_PRINTER_VALIDATION=false:

1. http mode:
   - kiosk checks print server /health
   - expects can_print=true or status=online fallback

If printer health fails, ticket issuance is blocked to avoid unprinted tickets.

## 13.3 HTTP Print Server Implementation

Supported file:

1. PrintServer/print-server.py (includes can_print and issue reporting)

## 13.4 Sample HTTP Printer .env

```dotenv
PRINTER_ENABLED=true
SKIP_PRINTER_VALIDATION=false
PRINTER_TYPE=http
PRINTER_TARGET=http://WINDOWS_IP:3000/print
PRINTER_PORT=9100
```

## 13.5 Windows Print Server Quick Start (Python)

On Windows machine connected to printer:

```powershell
pip install flask pywin32 pillow
python PrintServer/print-server.py
```

For full Windows USB printer setup (Python installation, virtual environment, firewall, and auto-run at boot), see WINDOWS_PRINT_SERVER.md.

Health test:

```powershell
curl http://localhost:3000/health
```

Print test:

```powershell
curl -X POST http://localhost:3000/print -H "Content-Type: application/json" -d "{\"ticket\":{\"code\":\"CS-001\",\"service_type\":\"cashier\",\"priority\":\"student\",\"created_at\":\"2026-01-01T08:00:00+08:00\"}}"
```

---

## 14. Real-Time Broadcasting

The project uses Laravel Reverb and Echo.

### 14.1 Backend broadcast setup

1. BROADCAST_CONNECTION=reverb
2. Events implement ShouldBroadcastNow
3. Channel definitions are in routes/channels.php

### 14.2 Frontend subscription

1. resources/js/echo.js initializes Echo with VITE_REVERB_* variables
2. Monitor and counter pages subscribe to channels and refresh or update UI

---

## 15. Database Schema Summary

## 15.1 counters

1. id
2. name
3. type: cashier|registrar
4. claimed: boolean
5. timestamps

## 15.2 users

1. name, username, email
2. password
3. role: cashier|registrar|admin
4. counter_id (nullable FK)
5. remember_token
6. timestamps

## 15.3 queue_tickets

1. code
2. service_type: cashier|registrar
3. program (nullable)
4. priority
5. status
6. counter_id (nullable FK)
7. designated_counter_id (nullable FK)
8. hold_count
9. called_times
10. timestamps

## 15.4 monitor_media

1. filename
2. original_filename
3. type: image|video|gif
4. path
5. order
6. is_active
7. timestamps

## 15.5 monitor_settings

1. marquee_text
2. timestamps

## 15.6 sessions

1. id
2. user_id
3. ip_address
4. user_agent
5. payload
6. last_activity

---

## 16. Daily Operations Guide

## 16.1 Start of Day

1. Ensure services are running:
   - app server
   - reverb
   - worker
2. Verify printer readiness (/health or Windows status)
3. Open monitor screen on display device
4. Operators log in to assigned counters

## 16.2 During Operation

1. Use /admin/sessions to monitor active operator sessions
2. Use media manager to update playlist or marquee without downtime
3. Watch laravel logs for printer/network/broadcast issues

## 16.3 End of Day

1. Optionally run queue restart (operator action)
2. Backup database
3. Rotate/archive logs if needed

---

## 17. Maintenance and Update Guide

## 17.1 Application Updates

1. Pull latest code
2. Install/update dependencies:

```bash
composer install
npm install
```

3. Apply migrations:

```bash
php artisan migrate --force
```

4. Rebuild assets:

```bash
npm run build
```

5. Refresh caches in production:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

6. Restart long-running services:
   - reverb
   - queue worker

## 17.2 Dependency Upgrades

1. Backend:

```bash
composer update
```

2. Frontend:

```bash
npm update
```

3. Re-run tests after each upgrade

## 17.3 Data Maintenance

1. Backup MySQL before schema changes
2. Periodically prune old sessions and logs
3. Validate storage symlink and media files

## 17.4 Printer Maintenance

1. Confirm exact printer name in Windows
2. Keep paper stocked and check paper out/door open status
3. Keep Windows print server set to auto-run at boot using shell:startup
4. Test /health and /print endpoint after updates

## 17.5 Counter/User Maintenance

1. Add counter:
   - create in counters table
   - assign user counter_id and matching role
2. Reassign operator:
   - update users.counter_id
3. Ensure claimed flags are reset when force-terminating sessions

## 17.6 Registrar Program Routing Maintenance

Registrar program mapping exists in KioskController::REGISTRAR_PROGRAMS.

Important current behavior:

1. Program is validated in kiosk flow
2. Current issueTicket implementation stores program as null
3. designated_counter_id is currently left null for registrar tickets

If strict program-to-counter routing is needed, update issueTicket logic to persist program and designated counter.

---

## 18. Deployment (Ubuntu LAMP)

Use DEPLOYMENT.md as base. Production checklist:

1. Clone into /var/www/html/queueing_system
2. Set ownership to web user
3. composer install --no-dev --optimize-autoloader
4. npm install && npm run build
5. Configure .env with production values
6. php artisan key:generate
7. php artisan migrate --force
8. php artisan storage:link
9. Configure Apache vhost to public/
10. Enable proxy modules for Reverb websocket forwarding
11. Configure and enable systemd services:
    - reverb
    - queue worker (if needed)
12. Configure firewall rules
13. Cache Laravel config/routes/views

---

## 19. Testing and Quality Assurance

Current automated tests are minimal boilerplate tests.

Run tests:

```bash
php artisan test
```

Recommended additional tests:

1. Ticket issuance and daily sequence reset
2. Alternating queue behavior across multiple counters
3. Hold/call-again/remove-hold transitions
4. Admin session termination releases counters
5. Printer health blocking logic
6. Monitor broadcast event handling

---

## 20. Monitoring and Logs

## 20.1 Laravel Logs

Path:

1. storage/logs/laravel.log

Watch in real-time:

```bash
tail -f storage/logs/laravel.log
```

## 20.2 Reverb and Worker Logs

If running with systemd:

```bash
sudo journalctl -u reverb -f
sudo journalctl -u laravel-worker -f
```

## 20.3 Print Server Logs

1. Console output for Python print server process
2. Windows Event Viewer if run as service

---

## 21. Troubleshooting

## 21.1 Tickets not updating on monitor

1. Verify reverb process is running
2. Verify BROADCAST_CONNECTION=reverb
3. Verify VITE_REVERB_* values match backend host/port/scheme
4. Check browser console websocket errors

## 21.2 Printer blocks ticket creation

1. Check PRINTER_ENABLED and SKIP_PRINTER_VALIDATION
2. For HTTP mode, verify /health returns can_print=true
3. Check printer paper, door, offline state
4. Check Laravel log entries for health check and print errors

## 21.3 Operators cannot log in

1. Verify username/password and user role
2. Check for active session lock conflict in sessions table
3. Ensure user has counter_id (non-admin users)

## 21.4 Media not showing

1. Ensure php artisan storage:link exists
2. Verify file exists in storage/app/public/media
3. Confirm media item is_active=true
4. Verify monitor received media.updated event

## 21.5 Queue action race conditions

1. Confirm single database and cache consistency
2. Keep one app instance during debugging
3. Review service lock behavior around counter next/hold actions

---

## 22. Security and Hardening Checklist

1. Use HTTPS for production
2. Set APP_DEBUG=false in production
3. Restrict print server network access to trusted hosts
4. Use strong operator/admin passwords and rotate regularly
5. Limit firewall exposure (app ports and print server ports)
6. Restrict CORS on print server in production
7. Review CSRF exclusions and keep minimal

---

## 23. Known Gaps and Improvement Opportunities

1. Automated tests are currently minimal
2. queue worker may be unnecessary for ShouldBroadcastNow events but still useful for future queued jobs
3. Program-to-designated-counter registrar mapping exists but is not currently persisted in issueTicket
4. Auto-remove-on-hold counter uses process memory and is not persistent across restarts
5. Remove or archive legacy print server files to avoid confusion; standard runtime is PrintServer/print-server.py

---

## 24. Quick Reference Commands

### 24.1 Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan storage:link
npm run build
```

### 24.2 Run (Development)

```bash
php artisan serve
php artisan reverb:start
php artisan queue:work
npm run dev
```

### 24.3 Test

```bash
php artisan test
```

### 24.4 Production Cache Ops

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 24.5 Clear Caches (Troubleshooting)

```bash
php artisan optimize:clear
```

---

## 25. Related Project Docs

1. SETUP.md
2. DEPLOYMENT.md
3. PRINTING_SETUP.md
4. WINDOWS_PRINT_SERVER.md

This document is intended to be the complete operational reference; the files above provide focused guides for specific environments.
