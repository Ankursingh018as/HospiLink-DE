<?php
/**
 * Test endpoint for AI Prioritizer
 */
header('Content-Type: application/json');

require_once 'ai_prioritizer.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $symptoms = isset($_POST['symptoms']) ? trim($_POST['symptoms']) : '';
    $age = isset($_POST['age']) && !empty($_POST['age']) ? intval($_POST['age']) : null;
    $conditions = isset($_POST['conditions']) ? trim($_POST['conditions']) : null;
    
    if (empty($symptoms)) {
        echo json_encode([
            'error' => 'Symptoms are required'
        ]);
        exit;
    }
    
    $prioritizer = new AIPrioritizer();
    $result = $prioritizer->getDetailedAssessment($symptoms, $age, $conditions);
    
    echo json_encode($result);
} else {
    echo json_encode([
        'error' => 'POST request required'
    ]);
}
?>
