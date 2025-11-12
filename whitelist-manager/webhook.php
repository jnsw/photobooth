<?php

/**
 * Webhook API for IP Whitelist Management
 * Endpoints:
 *   GET  /webhook.php?token=TOKEN - Get current IP list
 *   POST /webhook.php?token=TOKEN&action=add&ip=IP - Add IP
 *   POST /webhook.php?token=TOKEN&action=remove&ip=IP - Remove IP
 *   POST /webhook.php?token=TOKEN&action=reload - Reload configuration
 */

header('Content-Type: application/json');

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/sync.php';

$config = require __DIR__ . '/config.php';
$sync = new ConfigSync();

// Check authentication token
$token = $_GET['token'] ?? $_POST['token'] ?? '';
if ($token !== $config['api_token']) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized - Invalid token']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($method) {
        case 'GET':
            // Get current IP list
            $ips = $sync->loadIpsFromJson();
            echo json_encode([
                'success' => true,
                'ips' => $ips,
                'count' => count($ips)
            ]);
            break;

        case 'POST':
            switch ($action) {
                case 'add':
                    $ip = $_POST['ip'] ?? $_GET['ip'] ?? '';
                    if (empty($ip) || !filter_var($ip, FILTER_VALIDATE_IP)) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Invalid IP address']);
                        exit;
                    }
                    
                    $ips = $sync->loadIpsFromJson();
                    if (!in_array($ip, $ips, true)) {
                        $ips[] = $ip;
                        file_put_contents($config['data_file'], json_encode($ips, JSON_PRETTY_PRINT), LOCK_EX);
                        
                        // Sync to photobooth config
                        $sync->syncToPhotobooth($ips);
                        
                        echo json_encode([
                            'success' => true,
                            'message' => 'IP added successfully',
                            'ip' => $ip,
                            'ips' => $ips
                        ]);
                    } else {
                        echo json_encode([
                            'success' => false,
                            'message' => 'IP already exists',
                            'ip' => $ip
                        ]);
                    }
                    break;

                case 'remove':
                    $ip = $_POST['ip'] ?? $_GET['ip'] ?? '';
                    if (empty($ip)) {
                        http_response_code(400);
                        echo json_encode(['error' => 'IP address required']);
                        exit;
                    }
                    
                    $ips = $sync->loadIpsFromJson();
                    $key = array_search($ip, $ips, true);
                    
                    if ($key !== false) {
                        unset($ips[$key]);
                        $ips = array_values($ips); // Re-index array
                        file_put_contents($config['data_file'], json_encode($ips, JSON_PRETTY_PRINT), LOCK_EX);
                        
                        // Sync to photobooth config
                        $sync->syncToPhotobooth($ips);
                        
                        echo json_encode([
                            'success' => true,
                            'message' => 'IP removed successfully',
                            'ip' => $ip,
                            'ips' => $ips
                        ]);
                    } else {
                        echo json_encode([
                            'success' => false,
                            'message' => 'IP not found',
                            'ip' => $ip
                        ]);
                    }
                    break;

                case 'reload':
                    // Reload from photobooth config
                    $sync->initFromPhotobooth();
                    $ips = $sync->loadIpsFromJson();
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Configuration reloaded',
                        'ips' => $ips
                    ]);
                    break;

                default:
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid action']);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

