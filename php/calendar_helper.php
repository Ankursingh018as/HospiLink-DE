<?php
/**
 * Google Calendar Integration Helper
 * Generates .ics calendar files for email attachments
 */

class CalendarHelper {
    
    /**
     * Generate iCalendar (.ics) content for appointment
     * This creates a calendar event that can be imported into Google Calendar, Outlook, etc.
     * 
     * @param array $appointmentData - Contains appointment details
     * @return string - iCalendar formatted content
     */
    public static function generateICS($appointmentData) {
        $appointment_id = $appointmentData['appointment_id'];
        $name = $appointmentData['full_name'];
        $email = $appointmentData['email'];
        $date = $appointmentData['appointment_date'];
        $time = $appointmentData['appointment_time'];
        $symptoms = isset($appointmentData['symptoms']) ? $appointmentData['symptoms'] : '';
        $doctor = isset($appointmentData['doctor_name']) ? $appointmentData['doctor_name'] : 'To be assigned';
        $priority = isset($appointmentData['priority_level']) ? strtoupper($appointmentData['priority_level']) : 'MEDIUM';
        
        // Create start and end datetime
        $startDateTime = new DateTime($date . ' ' . $time);
        $endDateTime = clone $startDateTime;
        $endDateTime->modify('+1 hour'); // Default 1 hour appointment
        
        // Format dates for iCalendar (YYYYMMDDTHHMMSS)
        $dtStart = $startDateTime->format('Ymd\THis');
        $dtEnd = $endDateTime->format('Ymd\THis');
        $dtStamp = gmdate('Ymd\THis\Z');
        $uid = 'appointment-' . $appointment_id . '@hospilink.com';
        
        // Escape special characters for iCalendar format
        $summary = self::escapeICS("HospiLink Appointment - $name");
        $description = self::escapeICS(
            "Appointment Details:\n\n" .
            "Appointment ID: #$appointment_id\n" .
            "Patient: $name\n" .
            "Doctor: $doctor\n" .
            "Priority: $priority\n" .
            "Symptoms: $symptoms\n\n" .
            "Important Information:\n" .
            "- Please arrive 15 minutes before your appointment time\n" .
            "- Bring a valid ID and any previous medical records\n" .
            "- Wear a mask and maintain social distancing\n\n" .
            "Contact: hospilink@gmail.com | +91-9856594589"
        );
        $location = self::escapeICS("HospiLink Hospital, Dahod, Gujarat, India");
        
        // Priority mapping for calendar
        $calendarPriority = [
            'CRITICAL' => 1,
            'HIGH' => 3,
            'MEDIUM' => 5,
            'LOW' => 9
        ];
        $priority_value = $calendarPriority[$priority] ?? 5;
        
        // Build iCalendar content
        $ics = "BEGIN:VCALENDAR\r\n";
        $ics .= "VERSION:2.0\r\n";
        $ics .= "PRODID:-//HospiLink//Hospital Management System//EN\r\n";
        $ics .= "CALSCALE:GREGORIAN\r\n";
        $ics .= "METHOD:REQUEST\r\n";
        $ics .= "X-WR-CALNAME:HospiLink Appointments\r\n";
        $ics .= "X-WR-TIMEZONE:Asia/Kolkata\r\n";
        
        $ics .= "BEGIN:VEVENT\r\n";
        $ics .= "UID:$uid\r\n";
        $ics .= "DTSTAMP:$dtStamp\r\n";
        $ics .= "DTSTART:$dtStart\r\n";
        $ics .= "DTEND:$dtEnd\r\n";
        $ics .= "SUMMARY:$summary\r\n";
        $ics .= "DESCRIPTION:$description\r\n";
        $ics .= "LOCATION:$location\r\n";
        $ics .= "STATUS:CONFIRMED\r\n";
        $ics .= "SEQUENCE:0\r\n";
        $ics .= "PRIORITY:$priority_value\r\n";
        $ics .= "ORGANIZER;CN=HospiLink Hospital:mailto:hospilink@gmail.com\r\n";
        $ics .= "ATTENDEE;CN=$name;RSVP=TRUE:mailto:$email\r\n";
        
        // Add reminders
        $ics .= "BEGIN:VALARM\r\n";
        $ics .= "TRIGGER:-PT24H\r\n";
        $ics .= "ACTION:DISPLAY\r\n";
        $ics .= "DESCRIPTION:Appointment reminder - Tomorrow at " . $startDateTime->format('g:i A') . "\r\n";
        $ics .= "END:VALARM\r\n";
        
        $ics .= "BEGIN:VALARM\r\n";
        $ics .= "TRIGGER:-PT2H\r\n";
        $ics .= "ACTION:DISPLAY\r\n";
        $ics .= "DESCRIPTION:Appointment in 2 hours\r\n";
        $ics .= "END:VALARM\r\n";
        
        $ics .= "END:VEVENT\r\n";
        $ics .= "END:VCALENDAR\r\n";
        
        return $ics;
    }
    
    /**
     * Escape special characters for iCalendar format
     */
    private static function escapeICS($text) {
        $text = str_replace('\\', '\\\\', $text);
        $text = str_replace(',', '\\,', $text);
        $text = str_replace(';', '\\;', $text);
        $text = str_replace("\n", '\\n', $text);
        return $text;
    }
    
    /**
     * Generate base64 encoded ICS for email attachment
     */
    public static function generateICSBase64($appointmentData) {
        $icsContent = self::generateICS($appointmentData);
        return base64_encode($icsContent);
    }
    
    /**
     * Get ICS filename for appointment
     */
    public static function getICSFilename($appointment_id) {
        return "appointment-" . $appointment_id . ".ics";
    }
}
?>
