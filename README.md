# HospiLink - AI-Powered Hospital Management System
## Installation & Setup Guide

### 🌟 Overview
HospiLink is an AI-powered hospital connectivity and patient care platform that modernizes hospital operations with intelligent appointment prioritization based on symptom analysis.

### 📋 Prerequisites
- **XAMPP/WAMP/MAMP** (Apache + MySQL + PHP 7.4+)
- **Web Browser** (Chrome, Firefox, Edge recommended)
- **Text Editor** (VS Code, Sublime Text, etc.) - Optional

---

## 🚀 Installation Steps

### Step 1: Setup XAMPP
1. **Download and Install XAMPP** from [https://www.apachefriends.org/](https://www.apachefriends.org/)
2. **Start Apache and MySQL** from XAMPP Control Panel
3. Verify XAMPP is running by visiting `http://localhost` in your browser

### Step 2: Deploy HospiLink Files
1. **Copy the HospiLink folder** to your XAMPP htdocs directory:
   ```
   C:\xampp\htdocs\HospiLink\  (Windows)
   /Applications/XAMPP/htdocs/HospiLink/  (Mac)
   /opt/lampp/htdocs/HospiLink/  (Linux)
   ```

### Step 3: Create Database
1. **Open phpMyAdmin** by visiting `http://localhost/phpmyadmin`
2. **Click on "Import"** tab in phpMyAdmin
3. **Choose the SQL file**: `HospiLink/database/hospilink_schema.sql`
4. **Click "Go"** to execute and create the database

   **OR** manually create the database:
   - Click "New" in phpMyAdmin
   - Database name: `hospilink`
   - Collation: `utf8mb4_general_ci`
   - Click "Create"
   - Select the database and go to "Import" tab
   - Import the `hospilink_schema.sql` file

### Step 4: Configure Database Connection
1. **Open** `HospiLink/php/db.php`
2. **Verify the database credentials**:
   ```php
   $servername = "localhost";
   $username = "root";      // Default XAMPP username
   $password = "";          // Default XAMPP password (empty)
   $dbname = "hospilink";   // Database name
   ```
3. **Save the file** if you made any changes

### Step 5: Access the Application
1. **Open your browser** and visit:
   ```
   http://localhost/HospiLink/index.html
   ```

2. **For the new login system** (with roles):
   ```
   http://localhost/HospiLink/sign_new.html
   ```

---

## 👥 Default User Accounts

### Admin Account
- **Email:** `admin@hospilink.com`
- **Password:** `admin123`
- **Role:** Admin
- **Dashboard:** Full system management, view all appointments, user management

### Doctor Accounts

**Dr. Ramesh Patel (Cardiology)**
- **Email:** `dr.patel@hospilink.com`
- **Password:** `doctor123`
- **Role:** Doctor
- **Dashboard:** AI-prioritized appointment queue, patient management

**Dr. Harsh Shah (General Medicine)**
- **Email:** `dr.shah@hospilink.com`
- **Password:** `doctor123`
- **Role:** Doctor

**Dr. Mehul Poonawala (Pediatrics)**
- **Email:** `dr.poonawala@hospilink.com`
- **Password:** `doctor123`
- **Role:** Doctor

### Patient Account
- **Email:** `patient@hospilink.com`
- **Password:** `patient123`
- **Role:** Patient
- **Dashboard:** View appointments, medical history, book new appointments

---

## 🎯 Core Features Implemented

### 1️⃣ AI Appointment Prioritizer
- **Automatic symptom analysis** using keyword matching
- **Priority levels:** Critical, High, Medium, Low
- **Priority scoring** (0-100) for fine-grained sorting
- **Real-time prioritization** of appointment queue

#### How It Works:
1. Patient describes symptoms when booking appointment
2. AI analyzes text for critical keywords (e.g., "chest pain", "difficulty breathing")
3. System assigns priority level and score
4. Doctors see appointments sorted by urgency
5. Critical cases appear first with visual alerts

### 2️⃣ Role-Based Authentication
- **Three user roles:** Patient, Doctor, Admin
- **Separate dashboards** for each role
- **Session management** with secure authentication
- **Role-specific features** and permissions

### 3️⃣ Patient Dashboard
- View all appointments with priority status
- Access medical history
- Book new appointments
- Real-time appointment status updates

### 4️⃣ Doctor Dashboard
- **AI-Prioritized queue** - Appointments sorted by urgency
- **Visual priority indicators** - Color-coded badges
- **Critical alerts** - Notifications for urgent cases
- **Patient management** - View symptoms, add notes
- **Confirm appointments** - Update status

### 5️⃣ Admin Dashboard
- **System overview** - Statistics and analytics
- **Priority distribution** - Visual charts
- **User management** - Manage all users
- **Activity logs** - Track all system activities
- **Appointment management** - Assign doctors, update status

---

## 🧪 Testing the AI Prioritizer

### Critical Priority Symptoms (Test Cases)
Book an appointment with these symptoms to see CRITICAL priority:
- "Severe chest pain and difficulty breathing"
- "I'm having a heart attack"
- "Unconscious patient needs immediate help"
- "Severe bleeding won't stop"
- "Can't breathe properly, choking"

### High Priority Symptoms
- "High fever of 104°F for 3 days"
- "Severe abdominal pain"
- "Vomiting blood"
- "Broken bone in my arm"

### Medium Priority Symptoms
- "Persistent cough and fever"
- "Stomach ache and diarrhea"
- "Sore throat and headache"

### Low Priority Symptoms
- "Routine checkup needed"
- "Need prescription refill"
- "Follow-up appointment"
- "General health screening"

---

## 📊 AI Priority Algorithm

### Scoring System:
- **Critical:** 100 points (🚨 Immediate emergency)
- **High:** 75 points (⚡ Urgent attention needed)
- **Medium:** 50 points (📋 Standard care)
- **Low:** 25 points (✓ Routine/preventive)

### Symptom Keywords Database:
- **70+ medical keywords** pre-loaded
- **Categorized by urgency**
- **Expandable** - Add more keywords via database

### Priority Features:
1. **Keyword Matching** - Scans symptom description
2. **Multi-keyword Detection** - Higher score for multiple keywords
3. **Highest Priority Wins** - Most urgent keyword determines level
4. **Queue Sorting** - Automatic reordering
5. **Visual Indicators** - Color-coded badges and alerts

---

## 🔧 File Structure

```
HospiLink/
├── index.html                  # Homepage
├── sign_new.html              # New login/registration with roles
├── appointment.html           # Appointment booking (updated with symptoms)
├── beds.html                  # Bed availability
├── contact.html               # Contact page
│
├── css/
│   ├── style.css             # Homepage styles
│   ├── sign_new.css          # Login/registration styles
│   ├── dashboard.css         # Dashboard styles (all roles)
│   └── appointment.css       # Appointment form styles
│
├── php/
│   ├── db.php                # Database connection
│   ├── auth.php              # Authentication handler
│   ├── symptom_analyzer.php  # AI symptom analysis engine
│   ├── appointment.php       # Appointment booking handler
│   └── update_appointment.php # Update appointment status/notes
│
├── dashboards/
│   ├── patient_dashboard.php  # Patient interface
│   ├── doctor_dashboard.php   # Doctor interface (AI queue)
│   └── admin_dashboard.php    # Admin interface
│
├── database/
│   └── hospilink_schema.sql  # Database schema with sample data
│
└── images/                    # All image assets
```

---

## 🎨 Usage Guide

### For Patients:
1. **Register** at `sign_new.html` with role "Patient"
2. **Login** with your credentials
3. **Book Appointment** - Describe symptoms in detail
4. **View Priority** - See your appointment priority level
5. **Track Status** - Monitor appointment status in dashboard

### For Doctors:
1. **Login** at `sign_new.html` with role "Doctor"
2. **View Queue** - See AI-prioritized appointments
3. **Review Critical Cases** - Urgent patients appear first
4. **Confirm Appointments** - Update status
5. **Add Notes** - Record medical observations

### For Admins:
1. **Login** with admin credentials
2. **Monitor System** - View all statistics
3. **Manage Users** - Add/edit doctors, patients
4. **View Analytics** - Priority distribution charts
5. **Activity Logs** - Track all system activities

---

## 🔐 Security Features

- **Password Hashing** - bcrypt encryption
- **Session Management** - Secure PHP sessions
- **SQL Injection Prevention** - Prepared statements
- **Role-Based Access Control** - Permission checking
- **Activity Logging** - Track all user actions
- **XSS Protection** - Input sanitization

---

## 🐛 Troubleshooting

### Issue: Can't connect to database
**Solution:** 
- Verify XAMPP MySQL is running
- Check database name is "hospilink"
- Verify credentials in `php/db.php`

### Issue: Login not working
**Solution:**
- Clear browser cache and cookies
- Ensure you selected the correct role
- Try default accounts listed above

### Issue: AI prioritization not working
**Solution:**
- Check if symptom_keywords table has data
- Run the SQL schema file again
- Include symptom description when booking

### Issue: Dashboard not loading
**Solution:**
- Check if you're logged in
- Verify session is active
- Clear browser cache

### Issue: Page not found (404)
**Solution:**
- Verify HospiLink folder is in htdocs
- Check URL: `http://localhost/HospiLink/` (case-sensitive)
- Restart Apache in XAMPP

---

## 📱 Browser Compatibility

- ✅ Google Chrome (Recommended)
- ✅ Mozilla Firefox
- ✅ Microsoft Edge
- ✅ Safari
- ⚠️ Internet Explorer (Not supported)

---

## 🔄 Database Backup

To backup your data:
1. Open phpMyAdmin
2. Select "hospilink" database
3. Click "Export" tab
4. Choose "Quick" export method
5. Format: SQL
6. Click "Go" to download

---

## 📈 Future Enhancements

- [ ] Real-time chat with doctors
- [ ] SMS/Email notifications
- [ ] Advanced AI with machine learning
- [ ] Telemedicine video consultations
- [ ] Mobile app development
- [ ] Prescription management
- [ ] Lab report integration
- [ ] Billing system
- [ ] Insurance integration

---

## 📞 Support

For issues or questions:
- Check this README first
- Review the troubleshooting section
- Contact: support@hospilink.com

---

## 📄 License

Copyright © 2025 HospiLink. All rights reserved.

---

## 🎓 Credits

Developed as part of the HospiLink project to modernize hospital operations using AI-powered tools.

**Technology Stack:**
- Frontend: HTML5, CSS3, JavaScript
- Backend: PHP 7.4+
- Database: MySQL
- AI: Rule-based symptom analysis
- Server: Apache (XAMPP)

---

**🌟 Start improving patient care with AI today! 🌟**
