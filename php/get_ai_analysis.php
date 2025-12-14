<?php
header('Content-Type: application/json');
require_once 'db.php';

if (!isset($_GET['appointment_id'])) {
    echo json_encode(['error' => 'appointment_id required']);
    exit;
}

$appointment_id = intval($_GET['appointment_id']);

$stmt = $conn->prepare("SELECT ai_analysis, priority_level, priority_score FROM appointments WHERE appointment_id = ?");
$stmt->bind_param('i', $appointment_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo json_encode(['error' => 'Appointment not found']);
    exit;
}

$row = $res->fetch_assoc();
$ai = $row['ai_analysis'] ? json_decode($row['ai_analysis'], true) : null;

echo json_encode([
    'appointment_id' => $appointment_id,
    'priority_level' => $row['priority_level'],
    'priority_score' => $row['priority_score'],
    'ai' => $ai
]);

$stmt->close();
$conn->close();
?>
