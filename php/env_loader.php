<?php
/**
 * Environment Variable Loader for HospiLink
 * Loads configuration from .env file
 */

class EnvLoader {
    
    private static $loaded = false;
    private static $env = [];
    
    /**
     * Load environment variables from .env file
     */
    public static function load($path = null) {
        if (self::$loaded) {
            return; // Already loaded
        }
        
        if ($path === null) {
            $path = dirname(__DIR__) . '/.env';
        }
        
        if (!file_exists($path)) {
            throw new Exception('.env file not found at: ' . $path);
        }
        
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // Parse KEY=VALUE
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Remove quotes if present
                if (preg_match('/^(["\'])(.*)\1$/', $value, $matches)) {
                    $value = $matches[2];
                }
                
                // Set environment variable
                if (!array_key_exists($key, $_ENV)) {
                    putenv("$key=$value");
                    $_ENV[$key] = $value;
                    $_SERVER[$key] = $value;
                    self::$env[$key] = $value;
                }
            }
        }
        
        self::$loaded = true;
    }
    
    /**
     * Get environment variable
     */
    public static function get($key, $default = null) {
        if (!self::$loaded) {
            self::load();
        }
        
        // Check in order: $_ENV, $_SERVER, getenv(), stored values
        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }
        
        if (isset($_SERVER[$key])) {
            return $_SERVER[$key];
        }
        
        $value = getenv($key);
        if ($value !== false) {
            return $value;
        }
        
        if (isset(self::$env[$key])) {
            return self::$env[$key];
        }
        
        return $default;
    }
    
    /**
     * Check if environment variable exists
     */
    public static function has($key) {
        return self::get($key) !== null;
    }
}

// Helper function for easy access
if (!function_exists('env')) {
    function env($key, $default = null) {
        return EnvLoader::get($key, $default);
    }
}

// Auto-load on include
try {
    EnvLoader::load();
} catch (Exception $e) {
    error_log('Warning: ' . $e->getMessage());
}
?>
