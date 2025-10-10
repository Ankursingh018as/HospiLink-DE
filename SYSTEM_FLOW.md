# 🔄 HospiLink System Flow Diagram

## 📋 User Journey Maps

### 1️⃣ PATIENT JOURNEY

```
START
  │
  ├─→ Visit sign_new.html
  │     │
  │     ├─→ New User? → REGISTER
  │     │     │
  │     │     ├─→ Fill details (name, email, password)
  │     │     ├─→ Select Role: "Patient"
  │     │     ├─→ Submit → auth.php processes
  │     │     └─→ Account created ✓
  │     │
  │     └─→ Existing User? → LOGIN
  │           │
  │           ├─→ Enter email, password
  │           ├─→ Select Role: "Patient"
  │           └─→ Submit → auth.php validates
  │
  ├─→ Redirected to patient_dashboard.php
  │     │
  │     ├─→ View appointments
  │     ├─→ Check medical history
  │     └─→ Statistics overview
  │
  ├─→ Book New Appointment
  │     │
  │     ├─→ Click "Book Appointment"
  │     ├─→ Fill form (name, date, time)
  │     ├─→ DESCRIBE SYMPTOMS ⚡ (AI analyzes here!)
  │     ├─→ Optional: Select preferred doctor
  │     └─→ Submit
  │
  ├─→ AI Processing (symptom_analyzer.php)
  │     │
  │     ├─→ Scans symptom text
  │     ├─→ Matches keywords in database
  │     ├─→ Calculates priority score
  │     ├─→ Assigns level: Critical/High/Medium/Low
  │     └─→ Saves to appointments table
  │
  ├─→ Confirmation (appointment_success.php)
  │     │
  │     ├─→ Shows appointment ID
  │     ├─→ Displays priority level 🚨⚡📋✓
  │     ├─→ Shows AI score (0-100)
  │     ├─→ Expected wait time
  │     └─→ Next steps info
  │
  └─→ Back to Dashboard
        │
        ├─→ Track appointment status
        └─→ View in "My Appointments" section
```

---

### 2️⃣ DOCTOR JOURNEY

```
START
  │
  ├─→ Visit sign_new.html
  │     │
  │     ├─→ LOGIN as Doctor
  │     │     │
  │     │     ├─→ Enter email: dr.patel@hospilink.com
  │     │     ├─→ Enter password: doctor123
  │     │     ├─→ Select Role: "Doctor"
  │     │     └─→ Submit → auth.php validates
  │     │
  │     └─→ OR REGISTER new doctor
  │           │
  │           ├─→ Fill basic details
  │           ├─→ Select Role: "Doctor"
  │           ├─→ Fill specialization
  │           ├─→ Fill department
  │           ├─→ Fill license number
  │           └─→ Submit
  │
  ├─→ Redirected to doctor_dashboard.php
  │     │
  │     ├─→ TODAY'S OVERVIEW
  │     │     ├─→ Total appointments
  │     │     ├─→ Critical count 🚨
  │     │     ├─→ High priority count ⚡
  │     │     └─→ Pending count
  │     │
  │     └─→ CRITICAL ALERT BANNER (if critical cases exist)
  │           "🚨 URGENT: You have X critical patients!"
  │
  ├─→ View AI-Prioritized Queue
  │     │
  │     ├─→ Appointments sorted by:
  │     │     1. Critical (Score: 100) 🚨
  │     │     2. High (Score: 75) ⚡
  │     │     3. Medium (Score: 50) 📋
  │     │     4. Low (Score: 25) ✓
  │     │
  │     ├─→ Each row shows:
  │     │     ├─→ Priority badge (color-coded)
  │     │     ├─→ Patient info (name, email, phone)
  │     │     ├─→ Date & time
  │     │     ├─→ Symptoms description
  │     │     ├─→ AI score
  │     │     └─→ Status
  │     │
  │     └─→ Critical cases have RED background
  │
  ├─→ Take Actions
  │     │
  │     ├─→ 👁️ View Details
  │     │     └─→ Full patient information
  │     │
  │     ├─→ ✅ Confirm Appointment
  │     │     ├─→ Click confirm button
  │     │     ├─→ Status changes to "Confirmed"
  │     │     └─→ Patient notified
  │     │
  │     └─→ 📝 Add Medical Notes
  │           ├─→ Click notes button
  │           ├─→ Enter observations
  │           └─→ Saves to appointment record
  │
  └─→ Auto-refresh (every 2 minutes if critical cases)
```

---

### 3️⃣ ADMIN JOURNEY

```
START
  │
  ├─→ Visit sign_new.html
  │     │
  │     ├─→ LOGIN as Admin
  │     │     │
  │     │     ├─→ Email: admin@hospilink.com
  │     │     ├─→ Password: admin123
  │     │     ├─→ Role: "Admin"
  │     │     └─→ Submit
  │     │
  │     └─→ auth.php validates & creates admin session
  │
  ├─→ Redirected to admin_dashboard.php
  │     │
  │     ├─→ SYSTEM OVERVIEW
  │     │     ├─→ Total users (patients/doctors/admins)
  │     │     ├─→ Total appointments
  │     │     ├─→ Critical cases count
  │     │     └─→ Active doctors count
  │     │
  │     ├─→ PRIORITY DISTRIBUTION CHART
  │     │     ├─→ Visual bar chart
  │     │     ├─→ Shows % of each priority level
  │     │     └─→ Color-coded (Red/Orange/Yellow/Green)
  │     │
  │     ├─→ ALL APPOINTMENTS TABLE
  │     │     ├─→ Every appointment in system
  │     │     ├─→ Patient & doctor info
  │     │     ├─→ Priority level & score
  │     │     ├─→ Status tracking
  │     │     └─→ Action buttons
  │     │
  │     └─→ ACTIVITY LOGS
  │           ├─→ User registrations
  │           ├─→ Login events
  │           ├─→ Appointment updates
  │           └─→ System changes
  │
  ├─→ Manage Users
  │     │
  │     ├─→ View all patients
  │     ├─→ View all doctors
  │     ├─→ Add new users
  │     ├─→ Edit user details
  │     └─→ Deactivate accounts
  │
  ├─→ Manage Appointments
  │     │
  │     ├─→ View appointment details
  │     ├─→ Assign doctors
  │     ├─→ Update status
  │     └─→ Generate reports
  │
  └─→ System Settings
        │
        ├─→ Configure AI parameters
        ├─→ Manage symptom keywords
        ├─→ Database backup
        └─→ Notification settings
```

---

## 🧠 AI PRIORITIZATION FLOW

```
PATIENT BOOKS APPOINTMENT
  │
  ├─→ Patient enters symptoms text
  │     Example: "Severe chest pain and difficulty breathing"
  │
  ├─→ appointment.php receives form
  │
  ├─→ Calls: symptom_analyzer.php
  │
  ├─→ AI ANALYSIS STARTS
  │     │
  │     ├─→ Step 1: Convert text to lowercase
  │     │     "severe chest pain and difficulty breathing"
  │     │
  │     ├─→ Step 2: Query symptom_keywords table
  │     │     Gets all 70+ keywords with priority levels
  │     │
  │     ├─→ Step 3: Keyword Matching Loop
  │     │     │
  │     │     ├─→ Check if "chest pain" in text → FOUND! ✓
  │     │     │   Priority: CRITICAL (100 points)
  │     │     │
  │     │     ├─→ Check if "difficulty breathing" in text → FOUND! ✓
  │     │     │   Priority: CRITICAL (100 points)
  │     │     │
  │     │     ├─→ Check if "fever" in text → NOT FOUND ✗
  │     │     │
  │     │     └─→ Continue for all keywords...
  │     │
  │     ├─→ Step 4: Calculate Score
  │     │     │
  │     │     ├─→ Matched keywords: ["chest pain", "difficulty breathing"]
  │     │     ├─→ Both are CRITICAL (100 points each)
  │     │     ├─→ Highest score: 100
  │     │     └─→ Average score: 100
  │     │
  │     ├─→ Step 5: Determine Priority Level
  │     │     │
  │     │     ├─→ Score >= 100 → CRITICAL 🚨
  │     │     ├─→ Score >= 75 → HIGH ⚡
  │     │     ├─→ Score >= 50 → MEDIUM 📋
  │     │     └─→ Score < 50 → LOW ✓
  │     │
  │     ├─→ Step 6: Generate Analysis Message
  │     │     "⚠️ CRITICAL: Your symptoms (chest pain, difficulty breathing)
  │     │      indicate a medical emergency. You will be prioritized for
  │     │      immediate attention."
  │     │
  │     └─→ Step 7: Return Results
  │           {
  │             priority_level: "critical",
  │             priority_score: 100,
  │             matched_keywords: [...],
  │             analysis: "..."
  │           }
  │
  ├─→ appointment.php saves to database
  │     │
  │     ├─→ appointments table
  │     │     ├─→ patient_id
  │     │     ├─→ symptoms (original text)
  │     │     ├─→ priority_level = "critical"
  │     │     ├─→ priority_score = 100
  │     │     └─→ status = "pending"
  │     │
  │     └─→ activity_logs table
  │           └─→ "Patient booked critical appointment"
  │
  ├─→ Display to patient
  │     │
  │     └─→ appointment_success.php
  │           ├─→ Shows priority badge 🚨 CRITICAL
  │           ├─→ Shows score: 100/100
  │           ├─→ Shows urgent message
  │           └─→ Next steps instructions
  │
  └─→ DOCTOR SEES IT IN QUEUE
        │
        ├─→ Appears at TOP of list (highest priority)
        ├─→ Red background highlight
        ├─→ Critical alert banner
        └─→ Can take immediate action
```

---

## 📊 DATABASE FLOW

```
USER REGISTERS
  │
  ├─→ Form data → auth.php
  │
  ├─→ Password hashed (bcrypt)
  │     Plain: "patient123"
  │     Hashed: "$2y$10$92IXUNpkjO0rOQ5byMi.Ye..."
  │
  ├─→ INSERT INTO users table
  │     ├─→ user_id (auto-increment)
  │     ├─→ first_name
  │     ├─→ last_name
  │     ├─→ email (unique)
  │     ├─→ password (hashed)
  │     ├─→ role (patient/doctor/admin)
  │     └─→ created_at (timestamp)
  │
  └─→ Activity logged in activity_logs

USER LOGS IN
  │
  ├─→ Credentials → auth.php
  │
  ├─→ SELECT FROM users WHERE email = ? AND role = ?
  │
  ├─→ Verify password (password_verify)
  │     Compares hashed password with input
  │
  ├─→ Create SESSION
  │     ├─→ $_SESSION['user_id']
  │     ├─→ $_SESSION['user_name']
  │     ├─→ $_SESSION['user_email']
  │     ├─→ $_SESSION['user_role']
  │     └─→ $_SESSION['logged_in'] = true
  │
  └─→ Redirect to role-specific dashboard

APPOINTMENT BOOKED
  │
  ├─→ Form data → appointment.php
  │
  ├─→ AI analyzes symptoms
  │
  ├─→ INSERT INTO appointments table
  │     ├─→ appointment_id (auto-increment)
  │     ├─→ patient_id (from session or new user)
  │     ├─→ doctor_id (optional)
  │     ├─→ full_name
  │     ├─→ symptoms
  │     ├─→ priority_level (from AI)
  │     ├─→ priority_score (from AI)
  │     ├─→ status = "pending"
  │     └─→ created_at
  │
  └─→ Confirmation shown

DOCTOR VIEWS QUEUE
  │
  ├─→ Query appointments:
  │     SELECT * FROM appointments
  │     WHERE doctor_id = ? OR doctor_id IS NULL
  │     ORDER BY
  │       CASE priority_level
  │         WHEN 'critical' THEN 1
  │         WHEN 'high' THEN 2
  │         WHEN 'medium' THEN 3
  │         WHEN 'low' THEN 4
  │       END,
  │       priority_score DESC
  │
  └─→ Display sorted by urgency

DOCTOR CONFIRMS
  │
  ├─→ Button click → update_appointment.php
  │
  ├─→ UPDATE appointments
  │     SET status = 'confirmed'
  │     WHERE appointment_id = ?
  │
  └─→ Activity logged
```

---

## 🎨 Visual Priority Indicators

```
CRITICAL 🚨
  ├─→ Badge: Red (#f44336)
  ├─→ Background: Light red
  ├─→ Icon: ⚠️ warning triangle
  ├─→ Animation: Pulsing
  └─→ Position: TOP of queue

HIGH ⚡
  ├─→ Badge: Orange (#FF9800)
  ├─→ Background: Light orange
  ├─→ Icon: ⚡ bolt
  └─→ Position: Upper section

MEDIUM 📋
  ├─→ Badge: Yellow (#FFC107)
  ├─→ Background: White
  ├─→ Icon: 📋 clipboard
  └─→ Position: Middle section

LOW ✓
  ├─→ Badge: Green (#4CAF50)
  ├─→ Background: White
  ├─→ Icon: ✓ check
  └─→ Position: Bottom section
```

---

## 🔐 Security Flow

```
PASSWORD HANDLING
  │
  ├─→ Registration:
  │     Plain password → password_hash(bcrypt)
  │     → Stored in database
  │
  └─→ Login:
        Database hash → password_verify(input)
        → True/False

SESSION SECURITY
  │
  ├─→ Login creates session
  ├─→ Session stored server-side
  ├─→ Session cookie sent to browser
  ├─→ Every page checks: if (!isset($_SESSION['logged_in']))
  └─→ Logout destroys session

SQL INJECTION PREVENTION
  │
  ├─→ All queries use prepared statements
  ├─→ Example:
  │     $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
  │     $stmt->bind_param("s", $email);
  └─→ Input automatically escaped

XSS PROTECTION
  │
  ├─→ All output uses htmlspecialchars()
  ├─→ Example:
  │     echo htmlspecialchars($user_name);
  └─→ Prevents script injection
```

---

## 🚀 System Startup Checklist

```
□ XAMPP Apache running
□ XAMPP MySQL running
□ Database "hospilink" created
□ Tables imported from SQL file
□ Sample data loaded
□ php/db.php configured
□ Can access http://localhost/HospiLink
□ Can login as admin
□ Can login as doctor
□ Can login as patient
□ AI prioritizes correctly
□ Dashboards load properly
```

---

## 📈 Data Flow Summary

```
PATIENT → Symptoms → AI → Priority → DOCTOR → Action → DATABASE
   ↓         ↓        ↓       ↓         ↓         ↓         ↓
Register  Describe  Analyze  Score   View    Confirm   Update
   ↓         ↓        ↓       ↓         ↓         ↓         ↓
  DB      Process  Keywords Level   Queue   Status    Logs
```

---

**This visual guide helps understand how all components work together!**
