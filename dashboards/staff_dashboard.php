<?php
session_start();
if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['user_role'], ['staff', 'nurse'])) {
    header("Location: ../sign_new.html");
    exit();
}

include '../php/db.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Get staff info
$staffQuery = "SELECT * FROM users WHERE user_id = ?";
$staffStmt = $conn->prepare($staffQuery);
$staffStmt->bind_param("i", $user_id);
$staffStmt->execute();
$staffInfo = $staffStmt->get_result()->fetch_assoc();

// Get admitted patients statistics
$statsQuery = "SELECT 
                COUNT(*) as total_admitted,
                SUM(CASE WHEN bed_id IS NULL THEN 1 ELSE 0 END) as awaiting_bed,
                SUM(CASE WHEN bed_id IS NOT NULL THEN 1 ELSE 0 END) as bed_assigned,
                SUM(CASE WHEN a.priority_level = 'high' THEN 1 ELSE 0 END) as critical_patients
              FROM patient_admissions pa
              LEFT JOIN appointments a ON pa.patient_id = a.patient_id AND a.status != 'cancelled'
              WHERE pa.status = 'active' AND pa.discharge_date IS NULL
              GROUP BY NULL";
$statsResult = $conn->query($statsQuery);
$stats = $statsResult ? $statsResult->fetch_assoc() : ['total_admitted' => 0, 'awaiting_bed' => 0, 'bed_assigned' => 0, 'critical_patients' => 0];

// Get available beds count
$bedsQuery = "SELECT COUNT(*) as available_beds FROM beds WHERE status = 'available'";
$bedsResult = $conn->query($bedsQuery);
$bedsStats = $bedsResult ? $bedsResult->fetch_assoc() : ['available_beds' => 0];

// Get admitted patients list with full details
$patientsQuery = "SELECT 
                    pa.admission_id,
                    pa.patient_id,
                    pa.admission_date,
                    pa.admission_reason as diagnosis,
                    COALESCE(MAX(a.priority_level), 'medium') as priority_level,
                    pa.status,
                    pa.qr_code_token,
                    CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                    p.phone,
                    p.email,
                    p.gender,
                    b.ward_name,
                    b.bed_number,
                    b.bed_type,
                    CONCAT(d.first_name, ' ', d.last_name) as doctor_name
                  FROM patient_admissions pa
                  JOIN users p ON pa.patient_id = p.user_id
                  LEFT JOIN beds b ON pa.bed_id = b.bed_id
                  LEFT JOIN users d ON pa.assigned_doctor_id = d.user_id
                  LEFT JOIN appointments a ON pa.patient_id = a.patient_id AND a.status != 'cancelled'
                  WHERE pa.status = 'active' AND pa.discharge_date IS NULL
                  GROUP BY pa.admission_id, pa.patient_id, pa.admission_date, pa.admission_reason, 
                           pa.status, pa.qr_code_token, p.first_name, p.last_name, p.phone, 
                           p.email, p.gender, b.ward_name, b.bed_number, b.bed_type, 
                           d.first_name, d.last_name
                  ORDER BY 
                    CASE COALESCE(MAX(a.priority_level), 'medium')
                        WHEN 'high' THEN 1
                        WHEN 'medium' THEN 2
                        WHEN 'low' THEN 3
                    END,
                    pa.admission_date DESC";
$patientsResult = $conn->query($patientsQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - HospiLink</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/doctor-dashboard-enhanced.css">
    <link rel="icon" href="../images/hosp_favicon.png" type="image/png">
    <style>
        /* Modern Stat Cards */
        .stats-grid-modern {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        @media (max-width: 1400px) {
            .stats-grid-modern {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 992px) {
            .stats-grid-modern {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .stats-grid-modern {
                grid-template-columns: 1fr;
            }
        }

        .stat-card-modern {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
            position: relative;
            overflow: hidden;
        }

        .stat-card-modern::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: currentColor;
            opacity: 0.05;
            border-radius: 50%;
            transform: translate(30px, -30px);
        }

        .stat-card-modern:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        }

        .stat-card-modern.purple {
            border-left-color: #7c3aed;
            color: #7c3aed;
        }

        .stat-card-modern.pink {
            border-left-color: #ec4899;
            color: #ec4899;
        }

        .stat-card-modern.blue {
            border-left-color: #3b82f6;
            color: #3b82f6;
        }

        .stat-card-modern.orange {
            border-left-color: #f59e0b;
            color: #f59e0b;
        }

        .stat-card-modern.teal {
            border-left-color: #14b8a6;
            color: #14b8a6;
        }

        .stat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .stat-icon-modern {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }

        .stat-card-modern.purple .stat-icon-modern {
            background: linear-gradient(135deg, #7c3aed, #a78bfa);
        }

        .stat-card-modern.pink .stat-icon-modern {
            background: linear-gradient(135deg, #ec4899, #f472b6);
        }

        .stat-card-modern.blue .stat-icon-modern {
            background: linear-gradient(135deg, #3b82f6, #60a5fa);
        }

        .stat-card-modern.orange .stat-icon-modern {
            background: linear-gradient(135deg, #f59e0b, #fbbf24);
        }

        .stat-card-modern.teal .stat-icon-modern {
            background: linear-gradient(135deg, #14b8a6, #2dd4bf);
        }

        .stat-number {
            font-size: 36px;
            font-weight: 700;
            color: #1f2937;
            line-height: 1;
            margin-bottom: 8px;
        }

        .stat-label {
            font-size: 14px;
            color: #6b7280;
            font-weight: 500;
            margin-bottom: 12px;
        }

        .stat-footer {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            color: #9ca3af;
        }

        .stat-footer i {
            font-size: 12px;
        }

        /* Patient Section */
        .patients-section {
            background: white;
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            margin-top: 30px;
        }

        .section-header-modern {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 2px solid #f3f4f6;
        }

        .section-title-modern {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 22px;
            font-weight: 700;
            color: #1f2937;
        }

        .section-title-modern i {
            color: #00adb5;
        }

        .btn-modern {
            padding: 10px 20px;
            border-radius: 10px;
            border: none;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
            line-height: 1;
        }

        .btn-modern i {
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }

        .btn-modern span {
            display: flex;
            align-items: center;
        }

        .btn-primary-modern {
            background: linear-gradient(135deg, #00adb5, #0e8389);
            color: white;
        }

        .btn-primary-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 173, 181, 0.3);
        }

        /* Admit New Button */
        .btn-admit-new {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            background: linear-gradient(135deg, #00adb5, #0e8389);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 13px;
            line-height: 1;
            box-shadow: 0 2px 8px rgba(0, 173, 181, 0.2);
            transition: all 0.3s ease;
        }

        .btn-admit-new i {
            font-size: 13px;
            line-height: 1;
        }

        .btn-admit-new span {
            line-height: 1;
        }

        .btn-admit-new:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 173, 181, 0.3);
        }

        /* Patient Grid */
        .patients-grid-modern {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 24px;
        }

        .patient-card-modern {
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            border-radius: 16px;
            padding: 24px;
            border: 2px solid #e5e7eb;
            transition: all 0.3s ease;
            position: relative;
        }

        .patient-card-modern:hover {
            border-color: #00adb5;
            box-shadow: 0 8px 24px rgba(0, 173, 181, 0.15);
            transform: translateY(-4px);
        }

        .patient-card-modern.critical-border {
            border-left: 5px solid #ef4444;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); }
            50% { box-shadow: 0 0 0 8px rgba(239, 68, 68, 0); }
        }

        .patient-header-modern {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 20px;
        }

        .patient-name-section {
            flex: 1;
        }

        .patient-name-modern {
            font-size: 20px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 4px;
        }

        .patient-id-modern {
            font-size: 13px;
            color: #6b7280;
            font-weight: 500;
        }

        .status-badge-modern {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .status-badge-modern.low {
            background: #d1fae5;
            color: #065f46;
        }

        .status-badge-modern.medium {
            background: #fed7aa;
            color: #92400e;
        }

        .status-badge-modern.high {
            background: #fee2e2;
            color: #991b1b;
            animation: pulse 2s infinite;
        }

        .patient-details-modern {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            margin-bottom: 20px;
        }

        .detail-row {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .detail-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
        }

        .detail-icon.disease {
            background: #fef3c7;
            color: #d97706;
        }

        .detail-icon.phone {
            background: #dbeafe;
            color: #2563eb;
        }

        .detail-icon.blood {
            background: #fee2e2;
            color: #dc2626;
        }

        .detail-icon.date {
            background: #e0e7ff;
            color: #4f46e5;
        }

        .detail-content {
            flex: 1;
            min-width: 0;
        }

        .detail-label-modern {
            font-size: 11px;
            color: #9ca3af;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .detail-value-modern {
            font-size: 14px;
            color: #374151;
            font-weight: 600;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .bed-info-modern {
            background: #f0fdfa;
            border: 2px dashed #14b8a6;
            border-radius: 12px;
            padding: 14px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .bed-info-modern.no-bed {
            background: #fef2f2;
            border-color: #ef4444;
        }

        .bed-info-modern i {
            font-size: 20px;
        }

        .bed-info-modern.no-bed i {
            color: #ef4444;
        }

        .bed-info-modern:not(.no-bed) i {
            color: #14b8a6;
        }

        .bed-info-text {
            font-weight: 600;
            color: #1f2937;
        }

        .patient-actions-modern {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-action {
            flex: 1;
            min-width: fit-content;
            padding: 10px 16px;
            border-radius: 10px;
            border: 2px solid;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .btn-action-primary {
            background: #00adb5;
            border-color: #00adb5;
            color: white;
        }

        .btn-action-primary:hover {
            background: #0e8389;
            transform: translateY(-2px);
        }

        .btn-action-secondary {
            background: white;
            border-color: #d1d5db;
            color: #6b7280;
        }

        .btn-action-secondary:hover {
            border-color: #00adb5;
            color: #00adb5;
            background: #f0fdfa;
        }

        .btn-action-info {
            background: white;
            border-color: #bfdbfe;
            color: #2563eb;
        }

        .btn-action-info:hover {
            background: #dbeafe;
            border-color: #2563eb;
        }

        .btn-action-danger {
            background: white;
            border-color: #fecaca;
            color: #dc2626;
        }

        .btn-action-danger:hover {
            background: #fee2e2;
            border-color: #dc2626;
        }

        /* Empty State */
        .empty-state-modern {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-state-modern i {
            font-size: 80px;
            color: #e5e7eb;
            margin-bottom: 20px;
        }

        .empty-state-modern h3 {
            font-size: 24px;
            color: #6b7280;
            margin-bottom: 12px;
        }

        .empty-state-modern p {
            color: #9ca3af;
            margin-bottom: 24px;
        }

        /* Search Bar */
        .search-modern {
            position: relative;
            margin-bottom: 30px;
        }

        .search-modern input {
            width: 100%;
            padding: 16px 20px 16px 50px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s;
            background: white;
        }

        .search-modern input:focus {
            outline: none;
            border-color: #00adb5;
            box-shadow: 0 0 0 4px rgba(0, 173, 181, 0.1);
        }

        .search-modern i {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 18px;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 0;
            border-radius: 16px;
            width: 90%;
            max-width: 550px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            padding: 24px 28px;
            border-bottom: 2px solid #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .modal-header h3 {
            font-size: 22px;
            color: #1f2937;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .modal-header h3 i {
            color: #00adb5;
        }

        .close {
            color: #9ca3af;
            font-size: 32px;
            font-weight: 300;
            cursor: pointer;
            transition: all 0.3s;
            line-height: 1;
        }

        .close:hover {
            color: #ef4444;
            transform: rotate(90deg);
        }

        .modal form {
            padding: 28px;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-group label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .form-group label i {
            color: #00adb5;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 14px;
            font-family: inherit;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #00adb5;
            box-shadow: 0 0 0 4px rgba(0, 173, 181, 0.1);
        }

        .form-group input.readonly-input {
            background: #f9fafb;
            color: #6b7280;
            cursor: not-allowed;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .modal-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            padding-top: 20px;
            border-top: 2px solid #f3f4f6;
        }

        .modal-actions button {
            padding: 12px 24px;
            border-radius: 10px;
            border: none;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #00adb5, #0e8389);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 173, 181, 0.3);
        }

        .btn-secondary {
            background: #f3f4f6;
            color: #6b7280;
        }

        .btn-secondary:hover {
            background: #e5e7eb;
            color: #374151;
        }

        .btn-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.3);
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="logo">
                <img src="../images/logo.png" alt="HospiLink">
            </div>
            <nav class="sidebar-nav">
                <a href="staff_dashboard.php" class="nav-item active">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="staff_patients.php" class="nav-item">
                    <i class="fas fa-users"></i>
                    <span>All Patients</span>
                </a>
                <a href="staff_beds.php" class="nav-item">
                    <i class="fas fa-bed"></i>
                    <span>Bed Management</span>
                </a>
                <a href="../admit.html" class="nav-item">
                    <i class="fas fa-user-plus"></i>
                    <span>Admit Patient</span>
                </a>
                <a href="staff_profile.php" class="nav-item">
                    <i class="fas fa-user-edit"></i>
                    <span>Edit Profile</span>
                </a>
                <a href="../php/auth.php?logout=true" class="nav-item logout">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="dashboard-header">
                <div>
                    <h1>Staff Dashboard</h1>
                    <p class="header-subtitle">Welcome back, <?php echo htmlspecialchars(explode(' ', $user_name)[0]); ?>! Here's today's overview</p>
                </div>
                <div class="user-info">
                    <span class="user-name"><?php echo htmlspecialchars($user_name); ?></span>
                    <span class="user-role"><i class="fas fa-user-nurse"></i> Hospital Staff</span>
                </div>
            </header>

            <!-- Statistics Cards -->
            <div class="stats-grid-modern">
                <div class="stat-card-modern purple">
                    <div class="stat-header">
                        <div class="stat-icon-modern">
                            <i class="fas fa-user-injured"></i>
                        </div>
                    </div>
                    <div class="stat-number"><?php echo $stats['total_admitted'] ?? 0; ?></div>
                    <div class="stat-label">Total Admitted</div>
                    <div class="stat-footer">
                        <i class="fas fa-users"></i>
                        <span>Active patients in system</span>
                    </div>
                </div>

                <div class="stat-card-modern pink">
                    <div class="stat-header">
                        <div class="stat-icon-modern">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                    <div class="stat-number"><?php echo $stats['awaiting_bed'] ?? 0; ?></div>
                    <div class="stat-label">Awaiting Bed</div>
                    <div class="stat-footer">
                        <i class="fas fa-hourglass-half"></i>
                        <span>Pending bed assignment</span>
                    </div>
                </div>

                <div class="stat-card-modern blue">
                    <div class="stat-header">
                        <div class="stat-icon-modern">
                            <i class="fas fa-bed"></i>
                        </div>
                    </div>
                    <div class="stat-number"><?php echo $stats['bed_assigned'] ?? 0; ?></div>
                    <div class="stat-label">Bed Assigned</div>
                    <div class="stat-footer">
                        <i class="fas fa-check-circle"></i>
                        <span>Patients with beds</span>
                    </div>
                </div>

                <div class="stat-card-modern orange">
                    <div class="stat-header">
                        <div class="stat-icon-modern">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                    </div>
                    <div class="stat-number"><?php echo $stats['critical_patients'] ?? 0; ?></div>
                    <div class="stat-label">Critical Cases</div>
                    <div class="stat-footer">
                        <i class="fas fa-heartbeat"></i>
                        <span>Require immediate attention</span>
                    </div>
                </div>

                <div class="stat-card-modern teal">
                    <div class="stat-header">
                        <div class="stat-icon-modern">
                            <i class="fas fa-bed"></i>
                        </div>
                    </div>
                    <div class="stat-number"><?php echo $bedsStats['available_beds'] ?? 0; ?></div>
                    <div class="stat-label">Available Beds</div>
                    <div class="stat-footer">
                        <i class="fas fa-door-open"></i>
                        <span>Ready for assignment</span>
                    </div>
                </div>
            </div>

            <!-- Search Bar -->
            <div class="search-modern">
                <i class="fas fa-search"></i>
                <input type="text" id="patientSearch" placeholder="Search patients by name, disease, or ID..." onkeyup="filterPatients()">
            </div>

            <!-- Patients Section -->
            <div class="patients-section">
                <div class="section-header-modern">
                    <h2 class="section-title-modern">
                        <i class="fas fa-hospital-user"></i>
                        <span>Admitted Patients</span>
                    </h2>
                    <a href="staff_patients.php" class="btn-modern btn-primary-modern">
                        <span>View All</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>

                <?php if ($patientsResult && $patientsResult->num_rows > 0): ?>
                    <div class="patients-grid-modern">
                        <?php while ($patient = $patientsResult->fetch_assoc()): ?>
                            <div class="patient-card-modern <?php echo $patient['priority_level'] === 'high' ? 'critical-border' : ''; ?>" 
                                 data-name="<?php echo strtolower(htmlspecialchars($patient['patient_name'])); ?>"
                                 data-disease="<?php echo strtolower(htmlspecialchars($patient['diagnosis'] ?? '')); ?>"
                                 data-id="<?php echo $patient['patient_id']; ?>">
                                
                                <div class="patient-header-modern">
                                    <div class="patient-name-section">
                                        <div class="patient-name-modern"><?php echo htmlspecialchars($patient['patient_name']); ?></div>
                                        <div class="patient-id-modern">Patient ID: #<?php echo $patient['patient_id']; ?></div>
                                    </div>
                                    <span class="status-badge-modern <?php echo $patient['priority_level']; ?>">
                                        <?php 
                                        $statusIcons = ['low' => 'fa-check-circle', 'medium' => 'fa-info-circle', 'high' => 'fa-exclamation-circle'];
                                        echo '<i class="fas ' . ($statusIcons[$patient['priority_level']] ?? 'fa-circle') . '"></i> ' . ucfirst($patient['priority_level']); 
                                        ?>
                                    </span>
                                </div>

                                <div class="patient-details-modern">
                                    <div class="detail-row">
                                        <div class="detail-icon disease">
                                            <i class="fas fa-notes-medical"></i>
                                        </div>
                                        <div class="detail-content">
                                            <div class="detail-label-modern">Diagnosis</div>
                                            <div class="detail-value-modern"><?php echo htmlspecialchars($patient['diagnosis'] ?? 'Not specified'); ?></div>
                                        </div>
                                    </div>

                                    <div class="detail-row">
                                        <div class="detail-icon phone">
                                            <i class="fas fa-phone"></i>
                                        </div>
                                        <div class="detail-content">
                                            <div class="detail-label-modern">Contact</div>
                                            <div class="detail-value-modern"><?php echo htmlspecialchars($patient['phone']); ?></div>
                                        </div>
                                    </div>

                                    <div class="detail-row">
                                        <div class="detail-icon blood">
                                            <i class="fas fa-user-md"></i>
                                        </div>
                                        <div class="detail-content">
                                            <div class="detail-label-modern">Doctor</div>
                                            <div class="detail-value-modern"><?php echo htmlspecialchars($patient['doctor_name'] ?? 'Not assigned'); ?></div>
                                        </div>
                                    </div>

                                    <div class="detail-row">
                                        <div class="detail-icon date">
                                            <i class="fas fa-calendar-day"></i>
                                        </div>
                                        <div class="detail-content">
                                            <div class="detail-label-modern">Admitted</div>
                                            <div class="detail-value-modern"><?php echo date('M d, Y', strtotime($patient['admission_date'])); ?></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="bed-info-modern <?php echo !$patient['bed_number'] ? 'no-bed' : ''; ?>">
                                    <i class="fas fa-<?php echo $patient['bed_number'] ? 'bed' : 'exclamation-circle'; ?>"></i>
                                    <span class="bed-info-text">
                                        <?php 
                                        if ($patient['bed_number']) {
                                            echo htmlspecialchars($patient['ward_name'] . ' - Bed ' . $patient['bed_number']);
                                        } else {
                                            echo 'No Bed Assigned Yet';
                                        }
                                        ?>
                                    </span>
                                </div>

                                <div class="patient-actions-modern">
                                    <?php if (!$patient['bed_number']): ?>
                                        <button class="btn-action btn-action-primary" onclick="openAssignBedModal(<?php echo $patient['admission_id']; ?>, '<?php echo htmlspecialchars($patient['patient_name']); ?>')">
                                            <i class="fas fa-bed"></i>
                                            <span>Assign Bed</span>
                                        </button>
                                    <?php else: ?>
                                        <button class="btn-action btn-action-secondary" onclick="openAssignBedModal(<?php echo $patient['admission_id']; ?>, '<?php echo htmlspecialchars($patient['patient_name']); ?>')">
                                            <i class="fas fa-exchange-alt"></i>
                                            <span>Change Bed</span>
                                        </button>
                                    <?php endif; ?>
                                    <button class="btn-action btn-action-info" onclick="window.location.href='../qr-print.php?admission_id=<?php echo $patient['admission_id']; ?>'">
                                        <i class="fas fa-qrcode"></i>
                                        <span>QR Code</span>
                                    </button>
                                    <button class="btn-action btn-action-danger" onclick="openDischargeModal(<?php echo $patient['patient_id']; ?>, '<?php echo htmlspecialchars($patient['patient_name']); ?>')">
                                        <i class="fas fa-sign-out-alt"></i>
                                        <span>Discharge</span>
                                    </button>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state-modern">
                        <i class="fas fa-user-injured"></i>
                        <h3>No Patients Admitted</h3>
                        <p>There are currently no admitted patients in the system.</p>
                        <a href="../admit.html" class="btn-admit-new">
                            <span>Admit New Patient</span>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Assign Bed Modal -->
    <div id="assignBedModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-bed"></i> Assign Bed</h3>
                <span class="close" onclick="closeModal('assignBedModal')">&times;</span>
            </div>
            <form id="assignBedForm">
                <input type="hidden" id="assignPatientId" name="patient_id">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Patient Name</label>
                    <input type="text" id="assignPatientName" readonly class="readonly-input">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-bed"></i> Select Bed</label>
                    <select id="bedSelect" name="bed_id" required>
                        <option value="">Loading available beds...</option>
                    </select>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-flag"></i> Priority Level</label>
                    <select name="priority" required>
                        <option value="normal">Normal</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-notes-medical"></i> Assignment Notes</label>
                    <textarea name="notes" rows="3" placeholder="Enter any special notes or requirements..."></textarea>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeModal('assignBedModal')">Cancel</button>
                    <button type="submit" class="btn-primary"><i class="fas fa-check"></i> Assign Bed</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Discharge Modal -->
    <div id="dischargeModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-sign-out-alt"></i> Discharge Patient</h3>
                <span class="close" onclick="closeModal('dischargeModal')">&times;</span>
            </div>
            <form id="dischargeForm">
                <input type="hidden" id="dischargePatientId" name="patient_id">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Patient Name</label>
                    <input type="text" id="dischargePatientName" readonly class="readonly-input">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-file-medical"></i> Discharge Summary</label>
                    <textarea name="discharge_summary" rows="4" placeholder="Enter discharge summary and instructions..." required></textarea>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeModal('dischargeModal')">Cancel</button>
                    <button type="submit" class="btn-danger"><i class="fas fa-check"></i> Discharge Patient</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Load available beds
        function loadAvailableBeds() {
            fetch('../php/get_available_beds.php?detailed=true')
                .then(response => response.json())
                .then(data => {
                    const select = document.getElementById('bedSelect');
                    if (data.success && data.beds && data.beds.length > 0) {
                        select.innerHTML = '<option value="">Select a bed...</option>';
                        data.beds.forEach(bed => {
                            select.innerHTML += `<option value="${bed.bed_id}">${bed.ward_name} - Bed ${bed.bed_number} (${bed.bed_type})</option>`;
                        });
                    } else {
                        select.innerHTML = '<option value="">No beds available</option>';
                    }
                })
                .catch(error => {
                    console.error('Error loading beds:', error);
                    document.getElementById('bedSelect').innerHTML = '<option value="">Error loading beds</option>';
                });
        }

        // Open assign bed modal
        function openAssignBedModal(patientId, patientName) {
            document.getElementById('assignPatientId').value = patientId;
            document.getElementById('assignPatientName').value = patientName;
            document.getElementById('assignBedModal').style.display = 'block';
            loadAvailableBeds();
        }

        // Open discharge modal
        function openDischargeModal(patientId, patientName) {
            document.getElementById('dischargePatientId').value = patientId;
            document.getElementById('dischargePatientName').value = patientName;
            document.getElementById('dischargeModal').style.display = 'block';
        }

        // Close modal
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Close modal on outside click
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }

        // Handle assign bed form
        document.getElementById('assignBedForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Assigning...';
            
            fetch('../php/assign_bed.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Bed assigned successfully!');
                    closeModal('assignBedModal');
                    // Refresh the page to show updated data
                    location.reload();
                } else {
                    alert('❌ Error: ' + data.message);
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Assign Bed';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('❌ An error occurred while assigning the bed.');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Assign Bed';
            });
        });

        // Handle discharge form
        document.getElementById('dischargeForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('../php/discharge_patient.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Patient discharged successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while discharging the patient.');
            });
        });

        // Filter patients
        function filterPatients() {
            const searchText = document.getElementById('patientSearch').value.toLowerCase();
            const cards = document.querySelectorAll('.patient-card-modern');
            
            cards.forEach(card => {
                const name = card.dataset.name || '';
                const disease = card.dataset.disease || '';
                const id = card.dataset.id || '';
                
                if (name.includes(searchText) || disease.includes(searchText) || id.includes(searchText)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        // View patient details (placeholder)
        function viewPatientDetails(patientId) {
            alert('Patient details view coming soon! Patient ID: ' + patientId);
        }

        // Auto-refresh dashboard every 30 seconds to show newly admitted patients
        let autoRefreshInterval;
        let countdown = 30;
        
        function updateRefreshCountdown() {
            const indicator = document.querySelector('.live-indicator span:last-child');
            if (indicator && countdown > 0) {
                indicator.textContent = `Live Updates (${countdown}s)`;
                countdown--;
            }
        }
        
        function startAutoRefresh() {
            // Update countdown every second
            setInterval(updateRefreshCountdown, 1000);
            
            // Refresh page every 30 seconds
            autoRefreshInterval = setInterval(() => {
                console.log('Auto-refreshing staff dashboard...');
                location.reload();
            }, 30000); // 30 seconds
        }

        // Start auto-refresh when page loads
        window.addEventListener('load', () => {
            console.log('Staff Dashboard loaded - Auto-refresh enabled (30s interval)');
            startAutoRefresh();
        });

        // Stop refresh when user is interacting with modals
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('show', () => {
                clearInterval(autoRefreshInterval);
                console.log('Auto-refresh paused - modal open');
            });
            modal.addEventListener('hide', () => {
                startAutoRefresh();
                console.log('Auto-refresh resumed');
            });
        });
    </script>
</body>
</html>
