# LOGBOOK 2025-11-12_18-30 - IP Whitelist Manager Service Implementation

## Abstract

Implemented a standalone web service for managing IP whitelist configuration via web interface and webhook API. The service runs on port 9999 and provides a simple Bootstrap-based UI for adding/removing IP addresses, with automatic synchronization to the Photobooth configuration file.

## Implementation Steps

### 1. Service Directory Structure
- Created `/var/www/html/whitelist-manager/` directory
- Created `data/` subdirectory for JSON storage and backups
- Set up proper file permissions for scripts

### 2. Configuration Module (`config.php`)
- Defined service configuration:
  - Port: 9999
  - Host: 0.0.0.0
  - Photobooth config path: `/var/www/html/config/my.config.inc.php`
  - Data file: `data/ips.json`
  - Backup directory: `data/backups/`
  - API token: Persistent token stored in `data/api_token.txt` (generated on first run)
  - Web password hash: Stored in `data/web_password.txt` (default: 'admin')
- Token and password persistence: Tokens are stored in files to ensure consistency across requests

### 3. Configuration Sync Module (`sync.php`)
- Implemented `ConfigSync` class for managing IP whitelist synchronization
- Features:
  - Load IPs from JSON file
  - Load Photobooth configuration
  - Create automatic backups before modifications
  - Update Photobooth `my.config.inc.php` with new IP list
  - Initialize JSON from Photobooth config
- Backup files are timestamped: `my.config.inc.php.YYYY-MM-DD_HH-MM-SS.bak`

### 4. Webhook API (`webhook.php`)
- RESTful API endpoints:
  - `GET /webhook.php?token=TOKEN` - Get current IP list
  - `POST /webhook.php?token=TOKEN&action=add&ip=IP` - Add IP address
  - `POST /webhook.php?token=TOKEN&action=remove&ip=IP` - Remove IP address
  - `POST /webhook.php?token=TOKEN&action=reload` - Reload from Photobooth config
- Token-based authentication
- IP validation using `filter_var()`
- JSON responses with success/error status
- Automatic sync to Photobooth config after changes

### 5. Web Interface (`index.php`)
- Bootstrap 5.3.0 UI with modern design
- Features:
  - Password-protected login (session-based)
  - Display all whitelisted IPs with remove buttons
  - Add new IP form with validation
  - Reload configuration button
  - Success/error notifications
  - Responsive design with gradient background
- IP validation on client and server side
- Confirmation dialogs for destructive actions

### 6. Service Management Scripts
- `start.sh`: Starts PHP built-in server on port 9999
  - PID file management
  - Log file creation
  - Background process handling
- `stop.sh`: Stops the server gracefully
  - PID file cleanup
  - Process verification

### 7. Data Initialization
- Created `data/ips.json` with initial IP from Photobooth config: `["192.168.11.123"]`
- Initialized JSON file from existing Photobooth configuration

### 8. Documentation
- Created `README.md` with:
  - Quick start guide
  - API documentation
  - Configuration instructions
  - Security notes
  - File structure overview

## Technical Decisions

1. **Storage Format**: JSON file for IP list (simple, human-readable)
2. **Sync Mechanism**: Direct PHP file modification with backup system
3. **Authentication**: 
   - Web interface: Password-based session authentication
   - API: Token-based authentication
4. **Server**: PHP built-in server (simple, no additional dependencies)
5. **UI Framework**: Bootstrap 5.3.0 CDN (no local dependencies)
6. **Port**: 9999 (as requested by user)

## Security Considerations

- Default password and API token should be changed in production
- File permissions should be set appropriately
- Backup system prevents data loss
- Input validation for IP addresses
- Session-based authentication for web interface

## Files Created

- `/var/www/html/whitelist-manager/config.php`
- `/var/www/html/whitelist-manager/index.php`
- `/var/www/html/whitelist-manager/webhook.php`
- `/var/www/html/whitelist-manager/sync.php`
- `/var/www/html/whitelist-manager/start.sh`
- `/var/www/html/whitelist-manager/stop.sh`
- `/var/www/html/whitelist-manager/data/ips.json`
- `/var/www/html/whitelist-manager/data/api_token.txt` (auto-generated)
- `/var/www/html/whitelist-manager/data/web_password.txt` (auto-generated)
- `/var/www/html/whitelist-manager/README.md`

## Usage

Start service:
```bash
cd /var/www/html/whitelist-manager
./start.sh
```

Access web interface: `http://localhost:9999`
Default password: `admin`

API endpoint: `http://localhost:9999/webhook.php?token=YOUR_TOKEN`

