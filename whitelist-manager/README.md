# IP Whitelist Manager Service

A simple web service for managing IP whitelist configuration for Photobooth. Runs on port 9999.

## Features

- **Web Interface**: Beautiful Bootstrap-based UI for managing IP addresses
- **Webhook API**: RESTful API endpoints for programmatic access
- **Configuration Sync**: Automatically syncs with Photobooth `my.config.inc.php`
- **Backup System**: Creates backups before modifying configuration
- **Reload Functionality**: Reload IP list from Photobooth configuration

## Quick Start

### Start the Service

```bash
cd /var/www/html/whitelist-manager
./start.sh
```

The service will be available at: `http://localhost:9999`

### Stop the Service

```bash
./stop.sh
```

## Web Interface

Access the web interface at `http://localhost:9999`

**Default Password**: `admin` (change in `config.php`)

### Features:
- View all whitelisted IP addresses
- Add new IP addresses
- Remove IP addresses
- Reload configuration from Photobooth config file

## Webhook API

### Authentication
All API requests require a token parameter:
```
?token=YOUR_TOKEN
```

Get your token from `config.php` (default: `changeme_...`)

### Endpoints

#### GET - List IPs
```
GET /webhook.php?token=YOUR_TOKEN
```

Response:
```json
{
  "success": true,
  "ips": ["192.168.1.100", "192.168.1.101"],
  "count": 2
}
```

#### POST - Add IP
```
POST /webhook.php?token=YOUR_TOKEN&action=add&ip=192.168.1.100
```

#### POST - Remove IP
```
POST /webhook.php?token=YOUR_TOKEN&action=remove&ip=192.168.1.100
```

#### POST - Reload Configuration
```
POST /webhook.php?token=YOUR_TOKEN&action=reload
```

## Configuration

Edit `config.php` to customize:
- Port (default: 9999)
- API token
- Web interface password
- Photobooth config file path

## Security Notes

⚠️ **Important**: Change the default password and API token in `config.php` before using in production!

## File Structure

```
whitelist-manager/
├── config.php          # Service configuration
├── index.php           # Web interface
├── webhook.php         # API endpoints
├── sync.php            # Configuration sync module
├── start.sh            # Start script
├── stop.sh             # Stop script
├── data/
│   ├── ips.json        # IP whitelist storage
│   └── backups/        # Configuration backups
└── README.md           # This file
```

## How It Works

1. IP addresses are stored in `data/ips.json`
2. When modified, changes are synced to Photobooth `config/my.config.inc.php`
3. Backups are created automatically before modifications
4. The service runs independently on port 9999

