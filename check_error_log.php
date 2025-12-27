<?php
// Check Apache error log for Gemini API errors
$errorLogPath = 'C:/xampp/apache/logs/error.log';

echo "<h2>Apache Error Log - Last 50 Lines</h2>";
echo "<p>Looking for Gemini API errors...</p><hr>";

if (file_exists($errorLogPath)) {
    $lines = file($errorLogPath);
    $recentLines = array_slice($lines, -50);
    
    echo "<pre style='background: #f5f5f5; padding: 15px; border-radius: 5px; max-height: 500px; overflow-y: auto;'>";
    
    foreach ($recentLines as $line) {
        // Highlight Gemini-related errors
        if (stripos($line, 'gemini') !== false || stripos($line, 'api') !== false) {
            echo "<span style='color: red; font-weight: bold;'>$line</span>";
        } else {
            echo htmlspecialchars($line);
        }
    }
    
    echo "</pre>";
} else {
    echo "<p style='color: red;'>Error log not found at: $errorLogPath</p>";
    echo "<p>Try: C:\\xampp\\apache\\logs\\error.log</p>";
}

// Also check PHP error log
$phpErrorLog = 'C:/xampp/php/logs/php_error_log.txt';
if (file_exists($phpErrorLog)) {
    echo "<hr><h2>PHP Error Log - Last 20 Lines</h2>";
    $phpLines = file($phpErrorLog);
    $recentPhpLines = array_slice($phpLines, -20);
    
    echo "<pre style='background: #fff3cd; padding: 15px; border-radius: 5px; max-height: 300px; overflow-y: auto;'>";
    foreach ($recentPhpLines as $line) {
        if (stripos($line, 'gemini') !== false || stripos($line, 'api') !== false) {
            echo "<span style='color: red; font-weight: bold;'>$line</span>";
        } else {
            echo htmlspecialchars($line);
        }
    }
    echo "</pre>";
}
?>
