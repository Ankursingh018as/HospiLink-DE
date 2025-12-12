<?php
/**
 * Test Google Calendar Integration
 * This file demonstrates how the calendar invite is generated
 */

require_once 'calendar_helper.php';

// Sample appointment data
$testAppointment = [
    'appointment_id' => 1001,
    'full_name' => 'John Doe',
    'email' => 'john.doe@example.com',
    'appointment_date' => '2025-12-20',
    'appointment_time' => '10:30:00',
    'symptoms' => 'Fever, cough, and headache for the past 3 days',
    'priority_level' => 'high',
    'doctor_name' => 'Dr. Sarah Johnson - Cardiology'
];

echo "<h1>Google Calendar Integration Test</h1>";
echo "<h2>Appointment Details:</h2>";
echo "<pre>";
print_r($testAppointment);
echo "</pre>";

echo "<h2>Generated iCalendar (.ics) Content:</h2>";
echo "<div style='background: #f4f4f4; padding: 20px; border: 1px solid #ddd; border-radius: 5px;'>";
echo "<pre style='white-space: pre-wrap; word-wrap: break-word;'>";

$icsContent = CalendarHelper::generateICS($testAppointment);
echo htmlspecialchars($icsContent);

echo "</pre>";
echo "</div>";

echo "<h2>Download Calendar File:</h2>";
$filename = CalendarHelper::getICSFilename($testAppointment['appointment_id']);

// Option to download the ICS file
if (isset($_GET['download'])) {
    header('Content-Type: text/calendar; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo $icsContent;
    exit;
}

echo "<p><a href='?download=1' style='display: inline-block; background: #00adb5; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Download " . $filename . "</a></p>";

echo "<h2>How to Test:</h2>";
echo "<ol>";
echo "<li>Click the 'Download' button above to save the .ics file</li>";
echo "<li>Open the downloaded file with your calendar application (Google Calendar, Outlook, etc.)</li>";
echo "<li>The appointment should be added to your calendar with reminders</li>";
echo "</ol>";

echo "<h2>Features Included:</h2>";
echo "<ul>";
echo "<li>✅ Appointment details (date, time, patient, doctor)</li>";
echo "<li>✅ Priority level: " . strtoupper($testAppointment['priority_level']) . "</li>";
echo "<li>✅ Location: HospiLink Hospital, Dahod, Gujarat, India</li>";
echo "<li>✅ Reminder 24 hours before</li>";
echo "<li>✅ Reminder 2 hours before</li>";
echo "<li>✅ Status: CONFIRMED</li>";
echo "</ul>";

echo "<h2>Compatible With:</h2>";
echo "<div style='display: flex; gap: 20px; flex-wrap: wrap;'>";
echo "<div style='padding: 10px; background: #e7f3ff; border-radius: 5px;'>📅 Google Calendar</div>";
echo "<div style='padding: 10px; background: #e7f3ff; border-radius: 5px;'>📧 Outlook</div>";
echo "<div style='padding: 10px; background: #e7f3ff; border-radius: 5px;'>🍎 Apple Calendar</div>";
echo "<div style='padding: 10px; background: #e7f3ff; border-radius: 5px;'>🌐 Yahoo Calendar</div>";
echo "<div style='padding: 10px; background: #e7f3ff; border-radius: 5px;'>⚡ Thunderbird</div>";
echo "</div>";

echo "<hr>";
echo "<p style='color: #666;'><strong>Note:</strong> In production, this .ics file is automatically attached to appointment confirmation emails.</p>";
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar Integration Test - HospiLink</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f8f9fa;
        }
        h1 {
            color: #0e545f;
            border-bottom: 3px solid #00adb5;
            padding-bottom: 10px;
        }
        h2 {
            color: #00adb5;
            margin-top: 30px;
        }
        pre {
            font-family: 'Courier New', monospace;
            font-size: 12px;
        }
    </style>
</head>
<body>
</body>
</html>
