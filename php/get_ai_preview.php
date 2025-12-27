<?php
/**
 * AI Preview Endpoint
 * Provides real-time AI analysis of symptoms during appointment booking
 * Uses Google Gemini API for accurate medical assessment
 */

header('Content-Type: application/json');
require_once 'db.php';
require_once 'ai_prioritizer.php';

// Enable error reporting for debugging (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $symptoms = isset($_POST['symptoms']) ? trim($_POST['symptoms']) : '';
    $age = isset($_POST['age']) ? intval($_POST['age']) : null;
    $conditions = isset($_POST['existing_conditions']) ? trim($_POST['existing_conditions']) : null;
    
    if (empty($symptoms)) {
        echo json_encode(['success' => false, 'message' => 'No symptoms provided']);
        exit;
    }

    // Use Gemini AI for accurate medical analysis
    $aiPrioritizer = new AIPrioritizer();
    $analysis = $aiPrioritizer->analyzeSymptomsWithAI($symptoms, $age, $conditions);
    
    // Map priority levels to standard format
    $priorityMap = [
        'critical' => 'high',
        'high' => 'high',
        'medium' => 'medium',
        'low' => 'low'
    ];
    
    $priority = isset($analysis['priority_level']) ? strtolower($analysis['priority_level']) : 'medium';
    $mappedPriority = isset($priorityMap[$priority]) ? $priorityMap[$priority] : 'medium';
    
    // Get urgency reason
    $urgencyReason = isset($analysis['urgency_reason']) ? $analysis['urgency_reason'] : 'Medical consultation recommended based on your symptoms.';
    
    // Get recommended specialist and fetch matching doctor from database
    $specialization = isset($analysis['recommended_specialist']) ? $analysis['recommended_specialist'] : 'General Medicine';
    $recommendedDoctor = '';
    
    // Fetch doctor from database based on specialization
    $doctorQuery = "SELECT CONCAT(first_name, ' ', last_name, ' - ', specialization) as doctor_name 
                    FROM users 
                    WHERE role = 'doctor' 
                    AND status = 'active' 
                    AND specialization LIKE ?
                    LIMIT 1";
    
    $stmt = $conn->prepare($doctorQuery);
    $searchTerm = "%{$specialization}%";
    $stmt->bind_param("s", $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $doctor = $result->fetch_assoc();
        $recommendedDoctor = $doctor['doctor_name'];
    } else {
        // Fallback to any available doctor
        $fallbackQuery = "SELECT CONCAT(first_name, ' ', last_name, ' - ', specialization) as doctor_name 
                          FROM users 
                          WHERE role = 'doctor' 
                          AND status = 'active' 
                          LIMIT 1";
        $fallbackResult = $conn->query($fallbackQuery);
        if ($fallbackResult && $fallbackResult->num_rows > 0) {
            $doctor = $fallbackResult->fetch_assoc();
            $recommendedDoctor = $doctor['doctor_name'];
        }
    }
    
    $stmt->close();
    
    // Get time sensitivity from analysis
    $timeSensitivity = isset($analysis['time_sensitivity']) ? $analysis['time_sensitivity'] : 'routine';
    
    // Get priority score
    $priorityScore = isset($analysis['priority_score']) ? $analysis['priority_score'] : 50;
    
    // Get suspected conditions
    $suspectedConditions = isset($analysis['suspected_conditions']) ? $analysis['suspected_conditions'] : [];
    
    // Get warning signs
    $warningSigns = isset($analysis['warning_signs']) ? $analysis['warning_signs'] : [];
    
    // Prepare response
    $response = [
        'success' => true,
        'priority_level' => $mappedPriority,
        'priority_score' => $priorityScore,
        'time_sensitivity' => $timeSensitivity,
        'urgency_reason' => $urgencyReason,
        'recommended_specialist' => $specialization,
        'recommended_doctor' => $recommendedDoctor,
        'suspected_conditions' => $suspectedConditions,
        'warning_signs' => $warningSigns,
        'analysis_method' => 'gemini-ai',
        'ai_analyzed' => isset($analysis['ai_analyzed']) ? $analysis['ai_analyzed'] : true
    ];
    
    echo json_encode($response);

} catch (Exception $e) {
    error_log('AI Preview Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Analysis temporarily unavailable. Please try again.',
        'error' => $e->getMessage()
    ]);
}
?>
