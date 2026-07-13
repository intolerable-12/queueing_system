# Windows Print Server Setup Guide (Python Only)

This guide configures the Windows PC that has the USB-connected EPSON TM-T82II printer.

Supported print server for this project:
- PrintServer/print-server.py

Do not use Node.js print server variants. The Laravel app is documented for the Python service below.

## 1. Architecture

- Linux Laravel server sends HTTP print requests
- Windows PC runs Python Flask print server
- USB-connected EPSON TM-T82II prints receipts

Flow:

Linux (Laravel) -> http://WINDOWS_IP:3000/print -> Windows Python print server -> USB printer

## 2. Requirements on Windows PC

1. Windows 10 or Windows 11
2. Printer driver installed and printer visible in Windows Printers
3. Python 3.11+ (recommended)
4. Network access from Linux server to Windows port 3000

## 3. Install Python on Windows

1. Download Python from https://www.python.org/downloads/windows/
2. Run installer
3. Enable these installer options:
   - Add python.exe to PATH
   - Install launcher for all users (recommended)
   - Disable path length limit (recommended)
4. Verify in PowerShell:

```powershell
python --version
pip --version
```

If python is not recognized, reopen PowerShell and try again.

## 4. Prepare Print Server Folder

1. Create folder:

```powershell
New-Item -ItemType Directory -Path C:\PrintServer -Force
```

2. Copy these files from the project to C:\PrintServer:
   - PrintServer/print-server.py
   - public/images/Lourdes_final.png (optional but recommended for logo printing)

3. Verify files:

```powershell
Get-ChildItem C:\PrintServer
```

## 5. Create Virtual Environment (Recommended)

```powershell
cd C:\PrintServer
python -m venv .venv
.\.venv\Scripts\Activate.ps1
```

If execution policy blocks activation:

```powershell
Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass
.\.venv\Scripts\Activate.ps1
```

## 6. Install Python Packages

```powershell
python -m pip install --upgrade pip
pip install flask pywin32 pillow
```

Package purpose:
- flask: HTTP API server
- pywin32: Windows print spooler access
- pillow: image processing/logo rasterization

## 7. Confirm Printer Name

The script default is:

```python
PRINTER_NAME = "EPSON TM-T82II Receipt"
```

Check actual installed name:

```powershell
Get-Printer | Select-Object Name
```

If different, edit C:\PrintServer\print-server.py and update PRINTER_NAME exactly.

## 8. Start the Python Print Server

```powershell
cd C:\PrintServer
.\.venv\Scripts\Activate.ps1
python print-server.py
```

Expected service endpoint:
- Health: http://localhost:3000/health
- Print: POST http://localhost:3000/print

## 9. Test Endpoints on Windows

Health test:

```powershell
curl http://localhost:3000/health
```

Expected fields include can_print, issues, raw_status.

Print test:

```powershell
curl -Method Post -Uri http://localhost:3000/print -ContentType "application/json" -Body '{"ticket":{"code":"CS-001","service_type":"cashier","priority":"student","created_at":"2026-01-01T08:00:00+08:00"}}'
```

## 10. Open Windows Firewall Port 3000

Allow only the Laravel server IP when possible:

```powershell
New-NetFirewallRule -DisplayName "Queue Print Server 3000" -Direction Inbound -Protocol TCP -LocalPort 3000 -Action Allow -RemoteAddress 192.168.138.30
```

If you need temporary broad access for testing:

```powershell
New-NetFirewallRule -DisplayName "Queue Print Server 3000 (Any)" -Direction Inbound -Protocol TCP -LocalPort 3000 -Action Allow
```

## 11. Configure Auto-Run on Windows Boot (shell:startup)

This project uses the Startup folder approach.

### Startup Folder Setup

1. Create C:\PrintServer\start-print-server.bat with this content:

```bat
@echo off
cd /d C:\PrintServer
call C:\PrintServer\.venv\Scripts\activate.bat
python C:\PrintServer\print-server.py
```

2. Open Startup folder:

```powershell
explorer shell:startup
```

3. Put a shortcut of C:\PrintServer\start-print-server.bat in that Startup folder.
4. Reboot Windows and verify the process is listening on port 3000.

## 12. Test from Linux Server

```bash
curl http://WINDOWS_IP:3000/health

curl -X POST http://WINDOWS_IP:3000/print \
  -H "Content-Type: application/json" \
  -d '{"ticket":{"code":"CS-001","service_type":"cashier","priority":"student","created_at":"2026-01-01T08:00:00+08:00"}}'
```

## 13. Laravel .env (Linux)

```dotenv
PRINTER_ENABLED=true
SKIP_PRINTER_VALIDATION=false
PRINTER_TYPE=http
PRINTER_TARGET=http://WINDOWS_IP:3000/print
PRINTER_PORT=9100
```

## 14. Troubleshooting

### A. Health says can_print=false

1. Check printer power and USB connection
2. Check paper state and cover state
3. Confirm PRINTER_NAME matches Get-Printer output
4. Restart Windows Print Spooler service

### B. Linux cannot reach Windows

1. Verify Windows IP with ipconfig
2. Verify firewall rule and remote address scope
3. Test ping and curl from Linux
4. Ensure both devices are on same network/subnet

### C. Print endpoint returns error

1. Run script in foreground and inspect console errors
2. Verify pywin32 installed in active environment
3. Ensure printer is not paused/offline in Windows queue

### D. Auto-run at boot does not start

1. Validate python path and working directory in your Startup config
2. Start script manually first to confirm runtime dependencies
3. If using Startup folder, ensure shortcut points to start-print-server.bat

## 15. Maintenance

1. Stop startup task/process
2. Replace C:\PrintServer\print-server.py
3. Start startup task/process
4. Re-test /health and one sample /print

Backup these files:
- C:\PrintServer\print-server.py
- C:\PrintServer\Lourdes_final.png (if used)
- Startup folder shortcut/configuration notes
