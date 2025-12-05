# 🏥 HospiLink - AI-Powered Hospital Management System

> **Revolutionizing Healthcare Connectivity Through Intelligent Technology**

[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-blue)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-orange)](https://www.mysql.com/)
[![Status](https://img.shields.io/badge/status-active-success.svg)](https://github.com/Ankursingh018as/HospiLink-DE)

---

## 📖 Table of Contents
- [Overview](#-overview)
- [Purpose & Aim](#-purpose--aim)
- [Features](#-features)
- [Technology Stack](#-technology-stack)
- [End Users](#-end-users)
- [Future Enhancements](#-future-enhancements)
- [Installation & Setup](#-installation--setup)
- [Usage Guide](#-usage-guide)
- [Troubleshooting](#-troubleshooting)
- [Contributing](#-contributing)
- [License](#-license)

---

## 🌟 Overview

**HospiLink** is a comprehensive AI-powered hospital management platform designed to streamline healthcare operations and improve patient care delivery. The system leverages intelligent symptom analysis to prioritize appointments, ensuring critical cases receive immediate attention while optimizing resource allocation.

### What Makes HospiLink Special?

- **🤖 AI-Driven Prioritization:** Automatically analyzes patient symptoms and assigns priority levels
- **⚡ Real-Time Updates:** Instant appointment status tracking and notifications
- **🔐 Secure & Scalable:** Role-based access control with encrypted data management
- **📊 Smart Analytics:** Comprehensive dashboards for patients, doctors, and administrators
- **🎯 User-Centric Design:** Intuitive interface built for healthcare professionals and patients

---

## 🎯 Purpose & Aim

### The Problem

Traditional hospital systems often struggle with:
- Manual appointment prioritization leading to delayed emergency care
- Inefficient queue management causing longer wait times
- Lack of real-time visibility into patient urgency
- Paper-based processes prone to errors and delays
- Poor communication between patients, doctors, and staff

### Our Solution

HospiLink addresses these challenges by:

1. **Intelligent Triage:** AI analyzes patient symptoms in real-time, identifying critical cases automatically
2. **Dynamic Prioritization:** Appointments are sorted by urgency, ensuring life-threatening conditions get immediate attention
3. **Centralized Platform:** All stakeholders (patients, doctors, admins) access one unified system
4. **Data-Driven Decisions:** Analytics help administrators optimize resource allocation
5. **Enhanced Patient Experience:** Transparent appointment status and reduced wait times

### Core Objectives

✅ **Improve Emergency Response Time** - Critical cases identified within seconds  
✅ **Optimize Doctor Workflow** - Prioritized queues reduce decision fatigue  
✅ **Enhance Patient Safety** - Automated alerts prevent overlooked urgent cases  
✅ **Increase Efficiency** - Reduce administrative overhead by 40%  
✅ **Better Resource Management** - Real-time bed availability and ward tracking  

---

## ✨ Features

### 🔹 For Patients

#### Appointment Booking with AI Analysis
- **Smart Symptom Input:** Natural language symptom description
- **Automatic Prioritization:** AI assigns priority based on symptom severity
- **Real-Time Status:** Track appointment confirmation and scheduling
- **Medical History:** Access past appointments and diagnoses
- **Transparent Priority:** See your priority level and estimated wait time

#### Dashboard Features
- View all appointments with priority badges
- Access complete medical history
- Receive notifications for appointment updates
- Book follow-up appointments seamlessly

### 🔹 For Doctors

#### AI-Powered Appointment Queue
- **Critical Alerts:** High-priority cases highlighted in red
- **Smart Sorting:** Appointments automatically sorted by urgency
- **Symptom Analysis:** View AI-analyzed patient symptoms
- **Quick Actions:** Confirm, reschedule, or add notes with one click
- **Patient Context:** Access full medical history before consultation

#### Queue Management
- See all assigned appointments in priority order
- Filter by date, priority level, or status
- Add clinical notes and treatment plans
- Update appointment status in real-time

### 🔹 For Administrators

#### System Overview Dashboard
- **Live Statistics:** Total appointments, priority distribution, bed availability
- **User Management:** Add/edit doctors, staff, and patients
- **Activity Monitoring:** Track all system activities with detailed logs
- **Analytics:** Visual charts showing appointment trends and patterns
- **Resource Allocation:** Monitor ward-wise bed occupancy

#### Administrative Tools
- Assign doctors to appointments
- Manage user roles and permissions
- Generate system reports
- View audit trails and activity logs

### 🔹 Core System Features

#### AI Symptom Analyzer
- **70+ Medical Keywords** in knowledge base
- **4 Priority Levels:** Critical, High, Medium, Low
- **Smart Scoring:** 0-100 point priority scale
- **Multi-Keyword Detection:** Identifies complex symptom patterns
- **Expandable Database:** Easy to add new symptoms/conditions

#### Bed Management System
- **Real-Time Availability:** Live bed count per ward
- **Ward-Wise Tracking:** ICU, NICU, General, Emergency, Pediatric, Maternity
- **Occupancy Status:** Visual indicators for available/occupied beds
- **Quick Actions:** Direct links to admit patients or contact staff

#### Authentication & Security
- **Role-Based Access Control:** Three distinct user roles
- **Secure Sessions:** PHP session management with timeout
- **Password Encryption:** BCrypt hashing for all passwords
- **SQL Injection Prevention:** Prepared statements throughout
- **Activity Logging:** Complete audit trail of all actions

---

## 🛠️ Technology Stack

### Frontend
- **HTML5** - Semantic markup for accessibility
- **CSS3** - Modern responsive design with gradients and animations
- **JavaScript** - Dynamic interactions and form validation
- **RemixIcon** - Clean, professional icon library

### Backend
- **PHP 7.4+** - Server-side logic and API endpoints
- **MySQL 8.0** - Relational database management
- **Apache** - Web server (via XAMPP)

### AI/Algorithm
- **Rule-Based System** - Keyword matching for symptom analysis
- **Priority Scoring** - Weighted algorithm for urgency calculation
- **Real-Time Processing** - Instant priority assignment

### Development Tools
- **XAMPP** - Local development environment
- **phpMyAdmin** - Database administration
- **Git** - Version control
- **VS Code** - Code editor

---

## 👥 End Users

### 1. **Patients**
- **Primary Need:** Quick access to medical care, especially in emergencies
- **Benefits:** Automated prioritization ensures urgent cases aren't delayed
- **Use Case:** Patient with chest pain gets immediate priority over routine checkup

### 2. **Doctors**
- **Primary Need:** Efficient patient queue management
- **Benefits:** AI-sorted queue helps focus on most critical cases first
- **Use Case:** Doctor sees all critical patients before medium-priority cases

### 3. **Hospital Administrators**
- **Primary Need:** System oversight and resource management
- **Benefits:** Real-time analytics for data-driven decisions
- **Use Case:** Admin monitors bed availability and appointment trends

### 4. **Hospital Staff**
- **Primary Need:** Coordination and patient information access
- **Benefits:** Centralized platform for all patient data
- **Use Case:** Nurse checks bed availability before admitting patient

### 5. **Emergency Response Teams**
- **Primary Need:** Quick triage and resource allocation
- **Benefits:** Critical cases flagged automatically
- **Use Case:** Ambulance team sees priority level before patient arrival

---

## 🚀 Future Enhancements

### Phase 1 (Next 3 Months)
- [ ] **SMS/Email Notifications** - Automated appointment reminders
- [ ] **Payment Integration** - Online appointment fee payment
- [ ] **Prescription Management** - Digital prescription generation and tracking
- [ ] **Lab Reports Integration** - Upload and view test results

### Phase 2 (6 Months)
- [ ] **Telemedicine Module** - Video consultations with doctors
- [ ] **Mobile Apps** - iOS and Android native applications
- [ ] **Advanced AI** - Machine learning for better symptom prediction
- [ ] **Insurance Integration** - Direct insurance claim processing

### Phase 3 (12 Months)
- [ ] **Wearable Device Integration** - Real-time vitals from smartwatches
- [ ] **Multilingual Support** - 10+ language options
- [ ] **Blockchain Medical Records** - Secure, immutable patient data
- [ ] **Voice Assistant** - AI-powered voice booking system

### Long-Term Vision
- [ ] **National Health Network** - Inter-hospital data sharing
- [ ] **Predictive Analytics** - Disease outbreak prediction

---

## 📥 Installation & Setup

### Prerequisites

Before installing HospiLink, ensure you have:

- **XAMPP** (Apache + MySQL + PHP 7.4+)
  - Download: [https://www.apachefriends.org/](https://www.apachefriends.org/)
- **Web Browser** (Chrome, Firefox, or Edge recommended)
- **Git** (optional, for cloning repository)
  - Download: [https://git-scm.com/](https://git-scm.com/)

---

### Step 1: Clone the Repository

#### Option A: Using Git (Recommended)

```bash
# Clone the repository
git clone https://github.com/Ankursingh018as/HospiLink-DE.git

# Navigate to the project directory
cd HospiLink-DE
```

#### Option B: Download ZIP

1. Go to [https://github.com/Ankursingh018as/HospiLink-DE](https://github.com/Ankursingh018as/HospiLink-DE)
2. Click **Code** → **Download ZIP**
3. Extract the ZIP file to your desired location

---

### Step 2: Setup XAMPP

1. **Install XAMPP** from the downloaded installer
2. **Start Services:**
   - Open **XAMPP Control Panel**
   - Click **Start** next to **Apache**
   - Click **Start** next to **MySQL**
3. **Verify Installation:**
   - Open browser and visit `http://localhost`
   - You should see the XAMPP welcome page

---

### Step 3: Deploy HospiLink Files

Copy the HospiLink folder to your XAMPP htdocs directory:

**Windows:**
```
C:\xampp\htdocs\HospiLink-DE\
```

**macOS:**
```
/Applications/XAMPP/htdocs/HospiLink-DE/
```

**Linux:**
```
/opt/lampp/htdocs/HospiLink-DE/
```

---

### Step 4: Create Database

#### Option A: Automatic Import

1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Click **Import** tab
3. Choose file: `HospiLink-DE/database/hospilink_schema.sql`
4. Click **Go** to import

#### Option B: Manual Creation

1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Click **New** to create database
3. Database name: `hospilink`
4. Collation: `utf8mb4_general_ci`
5. Click **Create**
6. Select the database
7. Go to **Import** tab
8. Import `hospilink_schema.sql`

---

### Step 5: Configure Database Connection

1. Open `HospiLink-DE/php/db.php`
2. Verify these settings:

```php
$servername = "localhost";
$username = "root";        // Default XAMPP username
$password = "";            // Default XAMPP password (empty)
$dbname = "hospilink";     // Database name
```

3. Save the file if you made changes

---

### Step 6: Access the Application

Open your browser and visit:

**Homepage:**
```
http://localhost/HospiLink-DE/index.html
```

**Login/Registration:**
```
http://localhost/HospiLink-DE/sign_new.html
```

---

### Step 7: Login with Default Accounts

#### Admin Account
```
Email: admin@hospilink.com
Password: admin123
```

#### Doctor Accounts
```
Dr. Patel (Cardiology)
Email: dr.patel@hospilink.com
Password: doctor123

Dr. Shah (General Medicine)
Email: dr.shah@hospilink.com
Password: doctor123
```

#### Patient Account
```
Email: patient@hospilink.com
Password: patient123
```

---

## 📖 Usage Guide

### For Patients

1. **Register**
   - Go to `sign_new.html`
   - Fill registration form
   - Select role: **Patient**
   - Submit

2. **Book Appointment**
   - Login to dashboard
   - Click "Book Appointment"
   - Fill form with symptom details
   - System automatically assigns priority
   - Submit and track status

3. **View Dashboard**
   - See all your appointments
   - Check priority levels
   - Access medical history

### For Doctors

1. **Login**
   - Use doctor credentials
   - Access doctor dashboard

2. **Review Queue**
   - See AI-prioritized appointments
   - Critical cases appear first (red badge)
   - Click to view patient details

3. **Manage Appointments**
   - Confirm appointments
   - Add clinical notes
   - Update status

### For Administrators

1. **System Monitoring**
   - Login with admin credentials
   - View system statistics
   - Check priority distribution

2. **User Management**
   - Add new doctors/staff
   - Manage user roles
   - View activity logs

3. **Resource Management**
   - Monitor bed availability
   - Assign doctors to appointments
   - Generate reports

---

## 🐛 Troubleshooting

### Database Connection Issues
```
Error: Could not connect to database

Solution:
1. Verify MySQL is running in XAMPP
2. Check database name is "hospilink"
3. Confirm credentials in php/db.php
```

### Login Problems
```
Issue: Can't login with credentials

Solution:
1. Clear browser cache and cookies
2. Verify correct role is selected
3. Try default accounts listed above
4. Check if sessions are enabled in PHP
```

### AI Prioritization Not Working
```
Issue: All appointments show same priority

Solution:
1. Ensure symptom_keywords table has data
2. Re-import hospilink_schema.sql
3. Include detailed symptoms when booking
```

### Page Not Found (404)
```
Issue: 404 error when accessing pages

Solution:
1. Verify folder is in htdocs: htdocs/HospiLink-DE/
2. Check URL capitalization (case-sensitive on Linux)
3. Restart Apache in XAMPP
```

---


## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---

## 👨‍💻 Development Team

Our talented team of developers who brought HospiLink to life:

<div align="center">

| 🎯 Team Member | 💼 Role & Expertise | 🔧 Core Contributions |
|:---------------|:--------------------|:----------------------|
| **Aman Yadav** | Full-Stack Developer | Frontend Development, Backend Integration, UI/UX Design |
| **Shantanu Chaubey** | Database Administrator | Database Architecture, Query Optimization, Data Modeling |
| **Ankur Singh** | Backend Developer | Server-Side Logic, Database Integration, API Development |
| **Jenish Solanki** | Frontend Developer | User Interface Design, Client-Side Functionality, Responsive Design |

</div>

**Project Repository:** [HospiLink-DE](https://github.com/Ankursingh018as/HospiLink-DE) by [@Ankursingh018as](https://github.com/Ankursingh018as)

---

<div align="center">

### 🌟 Star this repository if HospiLink helps improve healthcare! 🌟

**Made with ❤️ for better patient care**

[⬆ Back to Top](#-hospilink---ai-powered-hospital-management-system)

</div>
