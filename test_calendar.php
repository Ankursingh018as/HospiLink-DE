<?php
require_once 'php/db.php';
require_once 'php/env_loader.php';
require_once 'php/calendar_helper.php';

echo "=== Testing Calendar Helper ===\n\n";

// Test appointment data
$testAppointment = [
    'appointment_id' => 999,
    'full_name' => 'Test Patient',
    'email' => 'test@example.com',
    'appointment_date' => '2025-12-20',
    'appointment_time' => '14:30:00',
    'symptoms' => 'Regular checkup',
    'doctor_name' => 'Dr. Test Doctor',
    'priority_level' => 'medium'
];

echo "1. Testing ICS generation...\n";
try {
    $icsContent = CalendarHelper::generateICS($testAppointment);
    
    if (!empty($icsContent)) {
        echo "   ✓ ICS content generated successfully!\n";
        echo "   Length: " . strlen($icsContent) . " bytes\n";
        
        // Check for required components
        $checks = [
            'BEGIN:VCALENDAR' => strpos($icsContent, 'BEGIN:VCALENDAR') !== false,
            'BEGIN:VTIMEZONE' => strpos($icsContent, 'BEGIN:VTIMEZONE') !== false,
            'TZID:Asia/Kolkata' => strpos($icsContent, 'TZID:Asia/Kolkata') !== false,
            'BEGIN:VEVENT' => strpos($icsContent, 'BEGIN:VEVENT') !== false,
            'SUMMARY' => strpos($icsContent, 'SUMMARY:') !== false,
            'DTSTART' => strpos($icsContent, 'DTSTART') !== false,
            'DTEND' => strpos($icsContent, 'DTEND') !== false,
            'LOCATION' => strpos($icsContent, 'LOCATION:') !== false,
            'DESCRIPTION' => strpos($icsContent, 'DESCRIPTION:') !== false,
            'VALARM (Reminder)' => strpos($icsContent, 'BEGIN:VALARM') !== false,
            'END:VCALENDAR' => strpos($icsContent, 'END:VCALENDAR') !== false
        ];
        
        echo "\n2. Verifying ICS components:\n";
        foreach ($checks as $component => $status) {
            echo "   " . ($status ? "✓" : "✗") . " $component\n";
        }
        
        // Save to file for testing
        $filename = 'test_calendar.ics';
        file_put_contents($filename, $icsContent);
        echo "\n3. ICS file saved: $filename\n";
        echo "   You can open this file to test in your calendar app!\n";
        
        // Show preview
        echo "\n4. ICS Content Preview:\n";
        echo str_repeat("=", 60) . "\n";
        $lines = explode("\r\n", $icsContent);
        foreach (array_slice($lines, 0, 30) as $line) {
            echo $line . "\n";
        }
        echo "... (truncated)\n";
        echo str_repeat("=", 60) . "\n";
        
    } else {
        echo "   ✗ Failed to generate ICS content\n";
    }
    
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
?>
