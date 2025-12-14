<?php
header('Content-Type: application/json');
require_once 'db.php';
require_once 'ai_prioritizer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'POST required']);
    exit;
}

$appointment_id = isset($_POST['appointment_id']) ? intval($_POST['appointment_id']) : 0;
if ($appointment_id <= 0) {
    echo json_encode(['error' => 'appointment_id required']);
    exit;
}

$stmt = $conn->prepare("SELECT symptoms FROM appointments WHERE appointment_id = ?");
$stmt->bind_param('i', $appointment_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    echo json_encode(['error' => 'Appointment not found']);
    exit;
}

$row = $res->fetch_assoc();
$symptoms = $row['symptoms'];

$prioritizer = new AIPrioritizer();
$analysis = $prioritizer->getDetailedAssessment($symptoms);

// Ensure ai_analysis column exists
$colCheck = $conn->query("SHOW COLUMNS FROM appointments LIKE 'ai_analysis'");
if ($colCheck && $colCheck->num_rows === 0) {
    $conn->query("ALTER TABLE appointments ADD COLUMN ai_analysis TEXT NULL");
}

$ai_json = $conn->real_escape_string(json_encode($analysis));
$priorityLevel = $analysis['priority_level'];
$priorityScore = intval($analysis['priority_score']);

$update = $conn->prepare("UPDATE appointments SET priority_level = ?, priority_score = ?, ai_analysis = ?, updated_at = NOW() WHERE appointment_id = ?");
$update->bind_param('sisi', $priorityLevel, $priorityScore, $ai_json, $appointment_id);
$success = $update->execute();

if ($success) {
    echo json_encode(['success' => true, 'analysis' => $analysis]);
} else {
    echo json_encode(['error' => 'Failed to update appointment']);
}

$stmt->close();
$update->close();
$conn->close();
?>
