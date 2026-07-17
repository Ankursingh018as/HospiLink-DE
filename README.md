# HospiLink - AI-Powered Hospital Management System

> **Revolutionizing Healthcare Connectivity Through Intelligent Technology**

[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-blue)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-orange)](https://www.mysql.com/)
[![Status](https://img.shields.io/badge/status-active-success.svg)](https://github.com/Ankursingh018as/HospiLink-DE)

---

## 📖 Table of Contents

- [Flow of the Platform](#-flow-of-the-platform)
- [Overview](#-overview)
- [Purpose & Aim](#-purpose--aim)
- [Core Features](#-core-features)
- [System Architecture & Capabilities](#-system-architecture--capabilities)
- [Technology Stack](#-technology-stack)
- [End Users](#-end-users)
- [Future Enhancements](#-future-enhancements)
- [Installation & Setup](#-installation--setup)
- [Usage Guide](#-usage-guide)
- [Troubleshooting](#-troubleshooting)
- [License](#-license)
- [Development Team](#-development-team)

---

### 📋 Platform Workflow Step-by-Step

To ensure the clinical process is clear, here is a detailed breakdown of the platform flow:

1. **Patient Appointment & AI Triage**:
   - Patients request appointments by writing their symptoms in natural language.
   - The **AI Triage Engine** parses the symptoms against a knowledge base of 70+ medical keywords, calculates an urgency score, and flags the patient with a priority level (Critical, High, Medium, or Low).

2. **Prioritized Care & Bed Allocation**:
   - The queue displays on the Doctor and Staff dashboards, automatically sorted to place critical cases at the top.
   - Clinical staff click **Admit Patient**, which loads the admission dashboard. The patient's details, demographics, and clinical descriptions are automatically pre-filled.
   - Staff assign a ward bed, select the doctor, and submit the admission. This changes the appointment status to **Completed** and generates a unique secure bedside token.

3. **Bedside Care Tracking (QR Codes)**:
   - A physical QR code tag linking to the bedside token is placed by the bed.
   - Doctors or nurses scan this code using their mobile cameras, which redirects them to the live chart page (`patient-status.php`).
   - From the chart, they can directly log clinical events—adding medicines, prescribing IV drips, ordering test reports, or recording vitals.

4. **Background Event Monitors & Alerts**:
   - A background drip monitor scans running IVs. If a drip falls below 100ml, it triggers a warning alert.
   - All events (low drip alarms, new prescriptions, scheduled procedures) feed into the **Floating Notification Tray** at nursing stations in real-time. Emojis are sanitized client-side, and Remix Icons are loaded dynamically for a clean clinical appearance.

---

## 🌟 Overview

**HospiLink** is a comprehensive, intelligent hospital management platform designed to streamline medical workflows, improve patient tracking, and optimize emergency response times. By merging role-based clinical dashboards, automated triage analysis, bedside QR code integration, and real-time event notifications, HospiLink bridges communication gaps in fast-paced healthcare environments.

---

## 🎯 Purpose & Aim

### The Challenge in Healthcare Operations

Traditional hospital information systems often operate with disconnected modules, causing:

- Inefficient triage queue sorting, where urgent emergency cases wait behind routine visits.
- Lack of real-time monitoring of critical resources, such as patient vitals or IV drip completion rates.
- Slow handoffs between appointment confirmation, patient admission, and bedside bed allocation.
- Inconvenient workflows for clinical staff who need quick access to chart history at the bedside.

### The HospiLink Answer

HospiLink addresses these bottlenecks with:

1. **Intelligent Triage Engine:** Prioritizes clinical queues dynamically using symptom keyword mapping.
2. **Bedside QR Code Charts:** Allows physicians and staff to scan a bed's QR code and instantly view live medical status charts.
3. **Automated Admission Pipeline:** Instantly imports patient demographics and disease records from appointments directly into the admission forms.
4. **Real-Time Notification Tray:** Centrally alerts staff regarding critical events, such as when an IV drip volume runs low or when a doctor prescribes new medications.

---

## ✨ Core Features

### 🔹 For Patients

- **Smart Appointment Booking:** Natural language symptom input automatically processed to calculate patient priority scores.
- **Transparent Status Badges:** Real-time visibility into queue updates, appointment statuses, and estimated wait times.
- **Medical Dashboard:** View past clinical findings, prescribed medicine records, checkup history, and scheduled tasks.

### 🔹 For Doctors

- **AI-Sorted Consultations Queue:** Highest-priority critical and urgent emergency cases are automatically sorted to the top.
- **Personalized Activity Logs:** Fully integrated dashboard tracking only the actions, admits, checkups, and prescription additions performed by the logged-in physician.
- **Bedside Chart Editing:** Quick form additions to prescribe medicine, start IV drips, order tests, record vitals, and add progress checkup notes.

### 🔹 For Administrators

- **System Monitoring Panels:** Live statistics showing active patient counts, bed occupancy, doctor availability, and triage score distributions.
- **Staff & Bed Allocation:** Simple directory management to register new doctors/nurses, track ward bed availability, and assign admitting staff.

### 🔹 For Nurses & Clinical Staff

- **Asynchronous Discharge Console:** Streamlined patient discharge dialogs that save progress checkups and release beds instantly via AJAX.
- **Real-Time Active Indicators:** Green-dot indicator badges signaling patient status updates on dashboards instantly.

---

## 🔐 System Architecture & Capabilities

### 1. Bedside QR Code Scanning (`scan.php` & `patient-status.php`)

- **Direct Camera Triage:** Integrated HTML5-QRCode scanner accesses physical device cameras, allowing nurses and doctors to scan a bedside barcode tag and view clinical progress.
- **Secure Public Chart Access:** Allows direct viewing of active patient records (`patient-status.php`) through tokenized parameters without requiring credentials, facilitating instant bedside triage updates.

### 2. Event Notifications & Alert System (`js/notificationPanel.js` & `php/hospi_notify.php`)

- **RemixIcon Integration:** Floating notification tray built using professional Remix Icons, automatically styled and dynamically loaded on all layout pages.
- **Client-Side Emoji Sanitization:** Programmatically strips emojis from notification logs utilizing Unicode property escapes (`\p{Extended_Pictographic}`) for a premium, distraction-free medical dashboard.
- **IV Drip Level Alerts:** Periodically scans running IVs and triggers high-priority notifications to nursing stations when drip volumes drop below 100ml.

### 3. Visual Layout Makeover

- **Outfit Typography:** The entire dashboard suite features the clean **Outfit** typography system for optimal readability.
- **Sleek Light Gradients:** Standardized on clean slate backgrounds (`linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%)`) with active teal gradients (`linear-gradient(135deg, #00adb5, #0e8389)`) replacing plain colors.

---

## 🛠️ Technology Stack

- **Frontend:** HTML5 (Semantic Structure), CSS3 (Modern Responsive Flexbox/Grid, transitions), JavaScript (ES6+, asynchronous APIs)
- **Backend:** PHP 7.4+ (API routes, session triage management, background mail queue processor)
- **Database:** MySQL 8.0 (Indexed tables, foreign key constraints, stored triggers)
- **Icons & Fonts:** RemixIcon (UI framework symbols), Outfit (Google Fonts)

---

## 📥 Installation & Setup

### Prerequisites

1. **XAMPP Control Panel** (Apache + MySQL + PHP 7.4+).
2. Modern Web Browser (Chrome / Edge / Firefox).
3. **Git** installed (optional).

### Step 1: Deploy Files

Clone the project repository or download the ZIP, and copy the directory folder to your local server directory:

- **Windows:** `C:\xampp\htdocs\HospiLink-DE\`
- **Linux:** `/opt/lampp/htdocs/HospiLink-DE/`

### Step 2: Import Database Schema

1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Create a new database named `hospilink3` with collation `utf8mb4_general_ci`.
3. Go to the **Import** tab.
4. Select the file: `HospiLink-DE/database/current_db_schema.sql` (the updated current schema representing all recent tables and field additions).
5. Click **Import**.

### Step 3: Configure Environment

Ensure your database credentials match in the `.env` configuration file in the project root folder:

```ini
DB_HOST=localhost
DB_USERNAME=root
DB_PASSWORD=""
DB_NAME=hospilink3
```

### Step 4: Run Application

Open your browser and navigate to:

- **Homepage:** `http://localhost/HospiLink-DE/index.html`
- **Login Portal:** `http://localhost/HospiLink-DE/sign_new.html`

---

## 🔐 Credentials & Default Accounts

### Admin Account

- **Email:** `admin@hospilink.com`
- **Password:** `admin123`

### Doctor Accounts

- **Dr. Patel (Cardiology):** `dr.patel@hospilink.com` / `doctor123`
- **Dr. Shah (General Medicine):** `dr.shah@hospilink.com` / `doctor123`

### Patient Account

- **Email:** `patient@hospilink.com`
- **Password:** `patient123`

---

## 👨‍💻 Development Team

Our talented team of developers who brought HospiLink to life:

<div align="center">

| 🎯 Team Member       | 💼 Role & Expertise    | 🔧 Core Contributions                                               |
| :------------------- | :--------------------- | :------------------------------------------------------------------ |
| **Aman Yadav**       | Full-Stack Developer   | Frontend Development, Backend Integration, UI/UX Design             |
| **Shantanu Chaubey** | Database Administrator | Database Architecture, Query Optimization, Data Modeling            |
| **Ankur Singh**      | Backend Developer      | Server-Side Logic, Database Integration, API Development            |
| **Jenish Solanki**   | Frontend Developer     | User Interface Design, Client-Side Functionality, Responsive Design |

</div>

---

**Project Repository:** [HospiLink-DE](https://github.com/Ankursingh018as/HospiLink-DE) by [@Ankursingh018as](https://github.com/Ankursingh018as)

<div align="center">

### 🌟 Star this repository if HospiLink helps improve healthcare! 🌟

**Made with ❤️ for better patient care**

[⬆ Back to Top](#hospilink---ai-powered-hospital-management-system)

</div>
