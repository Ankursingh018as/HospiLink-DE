<?php
// admin_api.php - Administration API for HospiLink

// Set headers
header('Content-Type: application/json');

// Include DB connection
require_once 'db.php';

// Ensure the user is logged in as an administrator
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Admin privileges required.']);
    exit();
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'get_users':
        getUsers($conn);
        break;
        
    case 'add_user':
        addUser($conn);
        break;
        
    case 'edit_user':
        editUser($conn);
        break;
        
    case 'toggle_status':
        toggleUserStatus($conn);
        break;
        
    case 'delete_user':
        deleteUser($conn);
        break;
        
    case 'get_keywords':
        getKeywords($conn);
        break;
        
    case 'add_keyword':
        addKeyword($conn);
        break;
        
    case 'edit_keyword':
        editKeyword($conn);
        break;
        
    case 'delete_keyword':
        deleteKeyword($conn);
        break;
        
    case 'get_db_stats':
        getDbStats($conn);
        break;
        
    case 'get_logs':
        getActivityLogs($conn);
        break;
        
    case 'get_appointment_details':
        getAppointmentDetails($conn);
        break;
        
    case 'optimize_db':
        optimizeDb($conn);
        break;
        
    case 'clear_logs':
        clearLogs($conn);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action specified: ' . htmlspecialchars($action)]);
        break;
}

$conn->close();

// --- Functions Implementation ---

function getUsers($conn) {
    $role = $_GET['role'] ?? '';
    $search = trim($_GET['q'] ?? '');
    
    $query = "SELECT user_id, first_name, last_name, email, role, phone, age, gender, address, specialization, department, license_number, status, created_at FROM users WHERE 1=1";
    $params = [];
    $types = "";
    
    if (!empty($role)) {
        $query .= " AND role = ?";
        $params[] = $role;
        $types .= "s";
    }
    
    if (!empty($search)) {
        $query .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= "ssss";
    }
    
    $query .= " ORDER BY user_id DESC";
    
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        echo json_encode(['success' => true, 'data' => $users]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to retrieve users: ' . $stmt->error]);
    }
    $stmt->close();
}

function addUser($conn) {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'patient';
    $phone = trim($_POST['phone'] ?? '');
    $age = !empty($_POST['age']) ? intval($_POST['age']) : null;
    $gender = $_POST['gender'] ?? '';
    $address = trim($_POST['address'] ?? '');
    $specialization = trim($_POST['specialization'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $license_number = trim($_POST['license_number'] ?? '');
    
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'First name, last name, email, and password are required']);
        return;
    }
    
    // Check if email already exists
    $checkEmail = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $checkEmail->bind_param("s", $email);
    $checkEmail->execute();
    if ($checkEmail->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Email address already in use']);
        $checkEmail->close();
        return;
    }
    $checkEmail->close();
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    $query = "INSERT INTO users (first_name, last_name, email, password, role, phone, age, gender, address, specialization, department, license_number, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssssssisssss", $first_name, $last_name, $email, $hashedPassword, $role, $phone, $age, $gender, $address, $specialization, $department, $license_number);
    
    if ($stmt->execute()) {
        $newUserId = $stmt->insert_id;
        
        // Log action
        $log = $conn->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, 'User Registered', ?)");
        $details = "Admin created new $role: $first_name $last_name (#$newUserId)";
        $adminId = $_SESSION['user_id'];
        $log->bind_param("is", $adminId, $details);
        $log->execute();
        $log->close();
        
        echo json_encode(['success' => true, 'message' => 'User added successfully', 'user_id' => $newUserId]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create user: ' . $stmt->error]);
    }
    $stmt->close();
}

function editUser($conn) {
    $userId = intval($_POST['user_id'] ?? 0);
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $age = !empty($_POST['age']) ? intval($_POST['age']) : null;
    $gender = $_POST['gender'] ?? null;
    $address = trim($_POST['address'] ?? '');
    $specialization = trim($_POST['specialization'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $license_number = trim($_POST['license_number'] ?? '');
    $status = $_POST['status'] ?? 'active';
    $password = $_POST['password'] ?? '';
    
    if ($userId <= 0 || empty($first_name) || empty($last_name) || empty($email) || empty($role)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        return;
    }
    
    // Check email uniqueness
    $checkEmail = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
    $checkEmail->bind_param("si", $email, $userId);
    $checkEmail->execute();
    if ($checkEmail->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Email address already in use by another account']);
        $checkEmail->close();
        return;
    }
    $checkEmail->close();
    
    if (!empty($password)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $query = "UPDATE users SET first_name = ?, last_name = ?, email = ?, password = ?, role = ?, phone = ?, age = ?, gender = ?, address = ?, specialization = ?, department = ?, license_number = ?, status = ? WHERE user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssssissssssi", $first_name, $last_name, $email, $hashedPassword, $role, $phone, $age, $gender, $address, $specialization, $department, $license_number, $status, $userId);
    } else {
        $query = "UPDATE users SET first_name = ?, last_name = ?, email = ?, role = ?, phone = ?, age = ?, gender = ?, address = ?, specialization = ?, department = ?, license_number = ?, status = ? WHERE user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssssissssssi", $first_name, $last_name, $email, $role, $phone, $age, $gender, $address, $specialization, $department, $license_number, $status, $userId);
    }
    
    if ($stmt->execute()) {
        // Log action
        $log = $conn->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, 'Profile Updated', ?)");
        $details = "Admin updated account details for user #$userId ($first_name $last_name)";
        $adminId = $_SESSION['user_id'];
        $log->bind_param("is", $adminId, $details);
        $log->execute();
        $log->close();
        
        echo json_encode(['success' => true, 'message' => 'User updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update user: ' . $stmt->error]);
    }
    $stmt->close();
}

function toggleUserStatus($conn) {
    $userId = intval($_POST['user_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    
    if ($userId <= 0 || !in_array($status, ['active', 'inactive'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid user ID or status']);
        return;
    }
    
    $query = "UPDATE users SET status = ? WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $status, $userId);
    
    if ($stmt->execute()) {
        // Log action
        $log = $conn->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, 'User Status Changed', ?)");
        $details = "Admin toggled user #$userId status to $status";
        $adminId = $_SESSION['user_id'];
        $log->bind_param("is", $adminId, $details);
        $log->execute();
        $log->close();
        
        echo json_encode(['success' => true, 'message' => 'User status updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update status: ' . $stmt->error]);
    }
    $stmt->close();
}

function deleteUser($conn) {
    $userId = intval($_POST['user_id'] ?? 0);
    
    if ($userId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
        return;
    }
    
    // Prevent admin deleting themselves
    if ($userId == $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'Cannot delete your own administrator account']);
        return;
    }
    
    $query = "DELETE FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    
    if ($stmt->execute()) {
        // Log action
        $log = $conn->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, 'User Deleted', ?)");
        $details = "Admin deleted user #$userId";
        $adminId = $_SESSION['user_id'];
        $log->bind_param("is", $adminId, $details);
        $log->execute();
        $log->close();
        
        echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete user: ' . $stmt->error]);
    }
    $stmt->close();
}

function getKeywords($conn) {
    $query = "SELECT keyword_id, keyword, priority_level, description FROM symptom_keywords ORDER BY keyword";
    $result = $conn->query($query);
    
    if ($result) {
        $keywords = [];
        while ($row = $result->fetch_assoc()) {
            $keywords[] = $row;
        }
        echo json_encode(['success' => true, 'data' => $keywords]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to retrieve keywords: ' . $conn->error]);
    }
}

function addKeyword($conn) {
    $keyword = trim($_POST['keyword'] ?? '');
    $priority = $_POST['priority_level'] ?? 'medium';
    $description = trim($_POST['description'] ?? '');
    
    if (empty($keyword)) {
        echo json_encode(['success' => false, 'message' => 'Keyword is required']);
        return;
    }
    
    // Check duplication
    $check = $conn->prepare("SELECT keyword_id FROM symptom_keywords WHERE keyword = ?");
    $check->bind_param("s", $keyword);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Symptom keyword already exists']);
        $check->close();
        return;
    }
    $check->close();
    
    $query = "INSERT INTO symptom_keywords (keyword, priority_level, description) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sss", $keyword, $priority, $description);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Keyword added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add keyword: ' . $stmt->error]);
    }
    $stmt->close();
}

function editKeyword($conn) {
    $keywordId = intval($_POST['keyword_id'] ?? 0);
    $keyword = trim($_POST['keyword'] ?? '');
    $priority = $_POST['priority_level'] ?? '';
    $description = trim($_POST['description'] ?? '');
    
    if ($keywordId <= 0 || empty($keyword) || !in_array($priority, ['high', 'medium', 'low'])) {
        echo json_encode(['success' => false, 'message' => 'Missing or invalid fields']);
        return;
    }
    
    $query = "UPDATE symptom_keywords SET keyword = ?, priority_level = ?, description = ? WHERE keyword_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssi", $keyword, $priority, $description, $keywordId);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Keyword updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update keyword: ' . $stmt->error]);
    }
    $stmt->close();
}

function deleteKeyword($conn) {
    $keywordId = intval($_POST['keyword_id'] ?? 0);
    
    if ($keywordId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid keyword ID']);
        return;
    }
    
    $query = "DELETE FROM symptom_keywords WHERE keyword_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $keywordId);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Keyword deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete keyword: ' . $stmt->error]);
    }
    $stmt->close();
}

function getDbStats($conn) {
    $stats = [];
    
    // User counts by role
    $res = $conn->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
    $stats['users'] = [];
    $totalUsers = 0;
    while ($row = $res->fetch_assoc()) {
        $stats['users'][$row['role']] = intval($row['count']);
        $totalUsers += intval($row['count']);
    }
    $stats['users']['total'] = $totalUsers;
    
    // Appointment counts
    $res = $conn->query("SELECT status, COUNT(*) as count FROM appointments GROUP BY status");
    $stats['appointments'] = ['total' => 0];
    while ($row = $res->fetch_assoc()) {
        $stats['appointments'][$row['status']] = intval($row['count']);
        $stats['appointments']['total'] += intval($row['count']);
    }
    
    // Bed counts
    $res = $conn->query("SELECT status, COUNT(*) as count FROM beds GROUP BY status");
    $stats['beds'] = ['total' => 0];
    while ($row = $res->fetch_assoc()) {
        $stats['beds'][$row['status']] = intval($row['count']);
        $stats['beds']['total'] += intval($row['count']);
    }
    
    // Keyword count
    $res = $conn->query("SELECT COUNT(*) as count FROM symptom_keywords");
    $row = $res->fetch_assoc();
    $stats['keywords_count'] = intval($row['count']);
    
    // Database size
    $dbName = 'hospilink';
    $sizeQuery = "SELECT SUM(data_length + index_length) / 1024 / 1024 AS size_mb FROM information_schema.TABLES WHERE table_schema = ?";
    $stmt = $conn->prepare($sizeQuery);
    $stmt->bind_param("s", $dbName);
    $stmt->execute();
    $sizeRes = $stmt->get_result()->fetch_assoc();
    $stats['db_size_mb'] = round(floatval($sizeRes['size_mb'] ?? 0.0), 2);
    $stmt->close();
    
    echo json_encode(['success' => true, 'data' => $stats]);
}

function optimizeDb($conn) {
    $tables = ['users', 'appointments', 'activity_logs', 'beds', 'symptom_keywords'];
    $success = true;
    
    foreach ($tables as $table) {
        $res = $conn->query("OPTIMIZE TABLE `$table`");
        if (!$res) {
            $success = false;
        }
    }
    
    if ($success) {
        // Log action
        $log = $conn->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, 'Database Optimization', 'Admin optimized main database tables')");
        $adminId = $_SESSION['user_id'];
        $log->bind_param("i", $adminId);
        $log->execute();
        $log->close();
        
        echo json_encode(['success' => true, 'message' => 'Database tables optimized successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Some tables failed to optimize']);
    }
}

function clearLogs($conn) {
    // Delete logs older than 30 days
    $query = "DELETE FROM activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)";
    
    if ($conn->query($query)) {
        $deletedRows = $conn->affected_rows;
        
        // Log action
        $log = $conn->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, 'Logs Cleared', ?)");
        $details = "Admin cleared $deletedRows activity logs older than 30 days";
        $adminId = $_SESSION['user_id'];
        $log->bind_param("is", $adminId, $details);
        $log->execute();
        $log->close();
        
        echo json_encode(['success' => true, 'message' => "Successfully cleared $deletedRows activity logs older than 30 days"]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to clear activity logs: ' . $conn->error]);
    }
}

function getActivityLogs($conn) {
    $search = trim($_GET['q'] ?? '');
    $role = $_GET['role'] ?? '';
    
    $query = "SELECT al.*, CONCAT(u.first_name, ' ', u.last_name) as user_name, u.role
              FROM activity_logs al
              LEFT JOIN users u ON al.user_id = u.user_id
              WHERE 1=1";
    $params = [];
    $types = "";
    
    if (!empty($role)) {
        $query .= " AND u.role = ?";
        $params[] = $role;
        $types .= "s";
    }
    
    if (!empty($search)) {
        $query .= " AND (al.action LIKE ? OR al.details LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= "ssss";
    }
    
    $query .= " ORDER BY al.created_at DESC LIMIT 100";
    
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $logs = [];
        while ($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }
        echo json_encode(['success' => true, 'data' => $logs]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to retrieve logs: ' . $stmt->error]);
    }
    $stmt->close();
}

function getAppointmentDetails($conn) {
    $appointment_id = intval($_GET['appointment_id'] ?? 0);
    if ($appointment_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid appointment ID']);
        return;
    }
    
    $query = "SELECT a.*, 
        CONCAT(p.first_name, ' ', p.last_name) as patient_name,
        CONCAT(d.first_name, ' ', d.last_name) as doctor_name,
        d.specialization,
        a.ai_analysis
    FROM appointments a
    JOIN users p ON a.patient_id = p.user_id
    LEFT JOIN users d ON a.doctor_id = d.user_id
    WHERE a.appointment_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $appointment_id);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $data = $result->fetch_assoc();
            $data['ai'] = $data['ai_analysis'] ? json_decode($data['ai_analysis'], true) : null;
            echo json_encode(['success' => true, 'data' => $data]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Appointment not found']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to load details: ' . $stmt->error]);
    }
    $stmt->close();
}
?>
