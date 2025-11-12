<?php

/**
 * Configuration sync module
 * Syncs IP whitelist from JSON storage to photobooth configuration file
 */

require_once __DIR__ . '/config.php';

class ConfigSync
{
    private string $configFile;
    private string $dataFile;
    private string $backupDir;

    public function __construct()
    {
        $config = require __DIR__ . '/config.php';
        $this->configFile = $config['photobooth_config'];
        $this->dataFile = $config['data_file'];
        $this->backupDir = $config['backup_dir'];
        
        // Create backup directory if it doesn't exist
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }

    /**
     * Load IP whitelist from JSON file
     */
    public function loadIpsFromJson(): array
    {
        if (!file_exists($this->dataFile)) {
            return [];
        }
        
        $content = file_get_contents($this->dataFile);
        $ips = json_decode($content, true);
        
        return is_array($ips) ? $ips : [];
    }

    /**
     * Load current photobooth configuration
     */
    public function loadPhotoboothConfig(): array
    {
        if (!file_exists($this->configFile)) {
            throw new Exception("Photobooth config file not found: {$this->configFile}");
        }
        
        return require $this->configFile;
    }

    /**
     * Create backup of current configuration
     */
    public function createBackup(): string
    {
        if (!file_exists($this->configFile)) {
            throw new Exception("Config file not found for backup");
        }
        
        $backupFile = $this->backupDir . '/my.config.inc.php.' . date('Y-m-d_H-i-s') . '.bak';
        copy($this->configFile, $backupFile);
        
        return $backupFile;
    }

    /**
     * Update photobooth configuration with new IP whitelist
     */
    public function syncToPhotobooth(array $ipList): bool
    {
        try {
            // Create backup
            $this->createBackup();
            
            // Load current config
            $config = $this->loadPhotoboothConfig();
            
            // Update IP whitelist
            if (!isset($config['protect'])) {
                $config['protect'] = [];
            }
            $config['protect']['ip_whitelist'] = $ipList;
            
            // Write updated config
            return $this->writeConfigFile($config);
            
        } catch (Exception $e) {
            error_log("Sync error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Write configuration array to PHP file
     */
    private function writeConfigFile(array $config): bool
    {
        $content = "<?php\n\nreturn " . var_export($config, true) . ";\n";
        
        // Format the output nicely
        $content = str_replace(['array (', ')', '  '], ['[', ']', '    '], $content);
        $content = preg_replace('/=>\s+\[/', '=> [', $content);
        $content = preg_replace('/\s+\[/', ' [', $content);
        
        // Write to file
        $result = file_put_contents($this->configFile, $content, LOCK_EX);
        
        return $result !== false;
    }

    /**
     * Initialize JSON file from photobooth config
     */
    public function initFromPhotobooth(): bool
    {
        try {
            $config = $this->loadPhotoboothConfig();
            $ips = $config['protect']['ip_whitelist'] ?? [];
            
            file_put_contents($this->dataFile, json_encode($ips, JSON_PRETTY_PRINT), LOCK_EX);
            
            return true;
        } catch (Exception $e) {
            error_log("Init error: " . $e->getMessage());
            return false;
        }
    }
}

