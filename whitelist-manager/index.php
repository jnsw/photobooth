<?php

/**
 * Web Interface for IP Whitelist Management
 * Simple Bootstrap-based UI for managing IP whitelist
 */

session_start();

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/sync.php';

$config = require __DIR__ . '/config.php';
$sync = new ConfigSync();

// Simple password authentication
$loginRequired = true;
$loginError = '';

if (isset($_POST['login'])) {
    if (password_verify($_POST['password'] ?? '', $config['web_password'])) {
        $_SESSION['authenticated'] = true;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $loginError = 'Invalid password';
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if (!isset($_SESSION['authenticated']) && $loginRequired) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>IP Whitelist Manager - Login</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="bg-light">
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-4">
                    <div class="card shadow">
                        <div class="card-body">
                            <h4 class="card-title text-center mb-4">IP Whitelist Manager</h4>
                            <?php if ($loginError): ?>
                                <div class="alert alert-danger"><?= htmlspecialchars($loginError) ?></div>
                            <?php endif; ?>
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                                <button type="submit" name="login" class="btn btn-primary w-100">Login</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Handle actions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $ip = trim($_POST['ip'] ?? '');
                if (empty($ip) || !filter_var($ip, FILTER_VALIDATE_IP)) {
                    $message = 'Invalid IP address';
                    $messageType = 'danger';
                } else {
                    $ips = $sync->loadIpsFromJson();
                    if (!in_array($ip, $ips, true)) {
                        $ips[] = $ip;
                        file_put_contents($config['data_file'], json_encode($ips, JSON_PRETTY_PRINT), LOCK_EX);
                        $sync->syncToPhotobooth($ips);
                        $message = "IP {$ip} added successfully";
                        $messageType = 'success';
                    } else {
                        $message = 'IP already exists';
                        $messageType = 'warning';
                    }
                }
                break;

            case 'remove':
                $ip = $_POST['ip'] ?? '';
                $ips = $sync->loadIpsFromJson();
                $key = array_search($ip, $ips, true);
                if ($key !== false) {
                    unset($ips[$key]);
                    $ips = array_values($ips);
                    file_put_contents($config['data_file'], json_encode($ips, JSON_PRETTY_PRINT), LOCK_EX);
                    $sync->syncToPhotobooth($ips);
                    $message = "IP {$ip} removed successfully";
                    $messageType = 'success';
                } else {
                    $message = 'IP not found';
                    $messageType = 'warning';
                }
                break;

            case 'reload':
                $sync->initFromPhotobooth();
                $message = 'Configuration reloaded from photobooth config';
                $messageType = 'info';
                break;
        }
    }
}

// Load current IP list
$ips = $sync->loadIpsFromJson();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IP Whitelist Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px 0;
        }
        .main-card {
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .ip-item {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 12px 15px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s;
        }
        .ip-item:hover {
            background: #e9ecef;
            transform: translateX(5px);
        }
        .btn-action {
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card main-card">
                    <div class="card-header bg-primary text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="mb-0"><i class="bi bi-shield-check"></i> IP Whitelist Manager</h4>
                            <a href="?logout=1" class="btn btn-sm btn-light">Logout</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                                <?= htmlspecialchars($message) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Add IP Form -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="card-title"><i class="bi bi-plus-circle"></i> Add IP Address</h5>
                                <form method="POST" class="row g-3">
                                    <input type="hidden" name="action" value="add">
                                    <div class="col-md-8">
                                        <input type="text" class="form-control" name="ip" placeholder="e.g., 192.168.1.100" required pattern="^(?:[0-9]{1,3}\.){3}[0-9]{1,3}$">
                                    </div>
                                    <div class="col-md-4">
                                        <button type="submit" class="btn btn-success w-100"><i class="bi bi-plus"></i> Add</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Current IP List -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="card-title mb-0"><i class="bi bi-list-ul"></i> Current IP Whitelist</h5>
                                    <span class="badge bg-primary"><?= count($ips) ?> IP<?= count($ips) !== 1 ? 's' : '' ?></span>
                                </div>
                                
                                <?php if (empty($ips)): ?>
                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle"></i> No IP addresses in whitelist
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($ips as $ip): ?>
                                        <div class="ip-item">
                                            <div>
                                                <i class="bi bi-ip"></i> <strong><?= htmlspecialchars($ip) ?></strong>
                                            </div>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to remove <?= htmlspecialchars($ip) ?>?');">
                                                <input type="hidden" name="action" value="remove">
                                                <input type="hidden" name="ip" value="<?= htmlspecialchars($ip) ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="bi bi-trash"></i> Remove
                                                </button>
                                            </form>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Reload Configuration -->
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title"><i class="bi bi-arrow-clockwise"></i> Configuration</h5>
                                <p class="text-muted">Reload IP whitelist from photobooth configuration file</p>
                                <form method="POST" onsubmit="return confirm('This will reload the IP list from the photobooth config. Continue?');">
                                    <input type="hidden" name="action" value="reload">
                                    <button type="submit" class="btn btn-info">
                                        <i class="bi bi-arrow-clockwise"></i> Reload from Photobooth Config
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- API Info -->
                        <div class="mt-4 p-3 bg-light rounded">
                            <h6><i class="bi bi-code-slash"></i> API Information</h6>
                            <small class="text-muted">
                                <strong>API Endpoint:</strong> <code>/webhook.php?token=YOUR_TOKEN</code><br>
                                <strong>Port:</strong> <?= $config['port'] ?><br>
                                <strong>Your API Token:</strong> <code class="text-break"><?= htmlspecialchars($config['api_token']) ?></code>
                                <button class="btn btn-sm btn-outline-secondary ms-2" onclick="navigator.clipboard.writeText('<?= htmlspecialchars($config['api_token']) ?>'); alert('Token copied to clipboard!');">
                                    <i class="bi bi-clipboard"></i> Copy
                                </button>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

