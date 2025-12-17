<?php
/**
 * AI Preview Endpoint
 * Provides real-time AI analysis of symptoms during appointment booking
 * Returns lightweight urgency assessment for immediate user feedback
 */

header('Content-Type: application/json');
require_once 'db.php';
require_once 'symptom_analyzer.php';

// Enable error reporting for debugging (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $symptoms = isset($_POST['symptoms']) ? trim($_POST['symptoms']) : '';
    
    if (empty($symptoms)) {
        echo json_encode(['success' => false, 'message' => 'No symptoms provided']);
        exit;
    }

    // Use lightweight keyword-based analysis for instant feedback
    // This is faster than calling Gemini API and provides immediate UX
    $analyzer = new SymptomAnalyzer($conn);
    $analysis = $analyzer->analyzeSymptoms($symptoms, 0, ''); // age and conditions not needed for preview
    
    // Map priority levels
    $priorityMap = [
        'critical' => 'critical',
        'high' => 'high',
        'medium' => 'medium',
        'low' => 'low',
        'routine' => 'low'
    ];
    
    $priority = isset($analysis['priority']) ? strtolower($analysis['priority']) : 'medium';
    $mappedPriority = isset($priorityMap[$priority]) ? $priorityMap[$priority] : 'medium';
    
    // Determine time sensitivity
    $timeSensitivity = 'routine';
    if ($mappedPriority === 'critical') {
        $timeSensitivity = 'immediate';
    } elseif ($mappedPriority === 'high') {
        $timeSensitivity = 'urgent';
    } elseif ($mappedPriority === 'medium') {
        $timeSensitivity = 'routine';
    }
    
    // Get urgency reason
    $urgencyReason = isset($analysis['reason']) ? $analysis['reason'] : 'Based on your symptoms, we recommend medical consultation.';
    
    // Get recommended specialist and map to available doctors
    $specialization = isset($analysis['specialization']) ? strtolower($analysis['specialization']) : '';
    $recommendedDoctor = '';
    
    // Map specializations to available doctors
    $doctorMap = [
        'cardiology' => 'Dr. Ramesh Patel - Cardiology',
        'cardiac' => 'Dr. Ramesh Patel - Cardiology',
        'heart' => 'Dr. Ramesh Patel - Cardiology',
        'pediatrics' => 'Dr. Mehul Poonawala - Pediatrics',
        'pediatric' => 'Dr. Mehul Poonawala - Pediatrics',
        'child' => 'Dr. Mehul Poonawala - Pediatrics',
        'general' => 'Dr. Harsh Shah - General Medicine',
        'medicine' => 'Dr. Harsh Shah - General Medicine'
    ];
    
    // Check for keyword matches in specialization
    foreach ($doctorMap as $keyword => $doctor) {
        if (strpos($specialization, $keyword) !== false) {
            $recommendedDoctor = $doctor;
            break;
        }
    }
    
    // If no specific match, recommend based on priority
    if (empty($recommendedDoctor)) {
        if ($mappedPriority === 'critical' || $mappedPriority === 'high') {
            $recommendedDoctor = 'Dr. Harsh Shah - General Medicine (Emergency care)';
        } else {
            $recommendedDoctor = 'Any Available Doctor';
        }
    }
    
    $recommendedSpecialist = !empty($specialization) 
        ? ucfirst($specialization) . ' specialist'
        : 'General Medicine';
    
    // Prepare response
    $response = [
        'success' => true,
        'priority_level' => $mappedPriority,
        'priority_score' => isset($analysis['score']) ? $analysis['score'] : 50,
        'time_sensitivity' => $timeSensitivity,
        'urgency_reason' => $urgencyReason,
        'recommended_specialist' => $recommendedSpecialist,
        'recommended_doctor' => $recommendedDoctor,
        'specialization' => $specialization,
        'analysis_method' => 'keyword-based' // Let frontend know this is preview, not full AI
    ];
    
    echo json_encode($response);

} catch (Exception $e) {
    error_log('AI Preview Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Analysis temporarily unavailable',
        'error' => $e->getMessage()
    ]);
}
?>
