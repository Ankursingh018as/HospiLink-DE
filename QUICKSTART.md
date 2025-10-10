# 🚀 HospiLink Quick Start Guide

## ⚡ Get Started in 5 Minutes!

### 1️⃣ Import Database (1 minute)
```
1. Open http://localhost/phpmyadmin
2. Click "Import"
3. Choose file: database/hospilink_schema.sql
4. Click "Go"
```

### 2️⃣ Access Application (1 minute)
```
Open browser → http://localhost/HospiLink/sign_new.html
```

### 3️⃣ Login & Test (3 minutes)

#### 🔐 Quick Login Credentials

**ADMIN:**
- Email: `admin@hospilink.com`
- Password: `admin123`
- Role: Admin

**DOCTOR:**
- Email: `dr.patel@hospilink.com`
- Password: `doctor123`
- Role: Doctor

**PATIENT:**
- Email: `patient@hospilink.com`
- Password: `patient123`
- Role: Patient

---

## 🧪 Test AI Prioritizer (2 minutes)

### Step 1: Book Appointment as Patient
1. Login as patient
2. Click "Book New Appointment"
3. Fill form with symptom: **"Severe chest pain and difficulty breathing"**
4. Submit

### Step 2: View AI Priority
1. Logout
2. Login as Doctor (`dr.patel@hospilink.com`)
3. See appointment at TOP of queue with 🚨 CRITICAL priority

### Step 3: Check Admin Dashboard
1. Logout
2. Login as Admin
3. View priority distribution chart
4. See all appointments sorted by AI score

---

## 🎯 Priority Examples

### 🚨 CRITICAL (Score: 100)
```
"chest pain", "heart attack", "can't breathe", "unconscious"
→ Immediate emergency response
```

### ⚡ HIGH (Score: 75)
```
"high fever 104", "severe pain", "vomiting blood", "broken bone"
→ Urgent within 24 hours
```

### 📋 MEDIUM (Score: 50)
```
"persistent cough", "fever", "stomach ache", "sore throat"
→ Standard 3-5 days
```

### ✓ LOW (Score: 25)
```
"routine checkup", "prescription refill", "follow-up"
→ Routine scheduling
```

---

## 📊 What Each Dashboard Shows

### Patient Dashboard:
- ✅ My appointments with priority
- 📋 Medical history
- ➕ Book new appointment
- 🛏️ Check bed availability

### Doctor Dashboard:
- 🧠 AI-Prioritized queue (sorted by urgency)
- 🚨 Critical patient alerts
- ✔️ Confirm appointments
- 📝 Add medical notes

### Admin Dashboard:
- 📊 System statistics
- 📈 Priority distribution charts
- 👥 User management
- 📜 Activity logs

---

## 🔗 Important URLs

```
Homepage:        http://localhost/HospiLink/index.html
New Login:       http://localhost/HospiLink/sign_new.html
Book Appointment: http://localhost/HospiLink/appointment.html
Beds:            http://localhost/HospiLink/beds.html
phpMyAdmin:      http://localhost/phpmyadmin
```

---

## ⚙️ Common Tasks

### Create New Doctor:
1. Go to `sign_new.html`
2. Click "Sign Up"
3. Select Role: "Doctor"
4. Fill specialization, department, license
5. Register

### Add Symptom Keywords:
```sql
INSERT INTO symptom_keywords (keyword, priority_level, description)
VALUES ('your keyword', 'critical', 'Description');
```

### Check Activity Logs:
1. Login as Admin
2. Go to "Activity Logs" section
3. View all user actions

---

## 🆘 Quick Fixes

**Can't login?**
→ Check you selected correct role dropdown

**AI not prioritizing?**
→ Describe symptoms in detail with medical terms

**Dashboard blank?**
→ Clear cache, logout, login again

**Database error?**
→ Re-import hospilink_schema.sql

---

## ✅ Verification Checklist

- [ ] XAMPP Apache running
- [ ] XAMPP MySQL running
- [ ] Database "hospilink" created
- [ ] Can login as admin
- [ ] Can login as doctor
- [ ] Can login as patient
- [ ] AI prioritizes appointments
- [ ] Dashboards load correctly

---

## 🎉 You're Ready!

All set! Start testing the AI appointment prioritizer.

**Next Steps:**
1. Explore each dashboard
2. Book test appointments
3. See AI prioritization in action
4. Customize for your needs

---

**Need help?** Check README.md for detailed documentation.
