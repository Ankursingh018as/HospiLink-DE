# HospiLink Backend API (MERN Stack)

## 🚀 Overview
Complete Node.js/Express backend for HospiLink Hospital Management System with MongoDB database, JWT authentication, and AI-powered features.

## 📋 Features
- ✅ JWT-based Authentication & Authorization
- ✅ Role-based Access Control (Patient, Doctor, Staff, Nurse, Admin)
- ✅ AI-powered Appointment Prioritization
- ✅ Google Gemini AI Health Chatbot
- ✅ QR Code Patient Tracking
- ✅ Email Notifications (Nodemailer)
- ✅ Google Calendar Integration
- ✅ Real-time Bed Management
- ✅ Medical History Tracking
- ✅ Activity Logging & Audit Trail

## 🛠️ Tech Stack
- **Runtime:** Node.js 18+
- **Framework:** Express.js 4.18
- **Database:** MongoDB 6.0+ (Mongoose ODM)
- **Authentication:** JWT + bcrypt
- **APIs:** Google Gemini AI, Google Calendar
- **Email:** Nodemailer
- **Security:** Helmet, CORS, Rate Limiting

## 📦 Installation

### 1. Install Dependencies
```bash
cd backend
npm install
```

### 2. Environment Setup
Create `.env` file (copy from `.env.example`):
```env
# MongoDB
MONGODB_URI=mongodb://localhost:27017/hospilink

# Server
PORT=5000
NODE_ENV=development

# JWT
JWT_SECRET=your-super-secret-key
JWT_EXPIRE=7d

# Google Gemini AI
GEMINI_API_KEY=your-gemini-api-key

# Email (Gmail)
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=your-email@gmail.com
SMTP_PASSWORD=your-app-password

# Google Calendar (Optional)
GOOGLE_CLIENT_ID=your-client-id
GOOGLE_CLIENT_SECRET=your-client-secret
GOOGLE_REFRESH_TOKEN=your-refresh-token

# Frontend URL
CLIENT_URL=http://localhost:3000
```

### 3. Start MongoDB
```bash
# Windows (if using MongoDB Community)
mongod

# macOS/Linux
sudo systemctl start mongod
```

### 4. Migrate Data (Optional)
If you have existing MySQL data:
```bash
npm run migrate
```

### 5. Start Server
```bash
# Development (with nodemon)
npm run dev

# Production
npm start
```

Server will run on: **http://localhost:5000**

## 📡 API Endpoints

### Authentication (`/api/auth`)
| Method | Endpoint | Description | Access |
|--------|----------|-------------|--------|
| POST | `/register` | Register new user | Public |
| POST | `/login` | User login | Public |
| GET | `/me` | Get current user | Private |
| POST | `/logout` | Logout user | Private |
| PUT | `/profile` | Update profile | Private |
| PUT | `/change-password` | Change password | Private |

### Appointments (`/api/appointments`)
| Method | Endpoint | Description | Access |
|--------|----------|-------------|--------|
| GET | `/` | Get all appointments | Private |
| POST | `/` | Create appointment | Patient/Admin |
| GET | `/stats/summary` | Get statistics | Doctor/Admin |
| GET | `/:id` | Get single appointment | Private |
| PUT | `/:id` | Update appointment | Doctor/Admin |
| DELETE | `/:id` | Delete appointment | Admin/Patient |
| PUT | `/:id/claim` | Doctor claims appointment | Doctor |

### Patients (`/api/patients`)
| Method | Endpoint | Description | Access |
|--------|----------|-------------|--------|
| POST | `/admit` | Admit patient | Staff/Nurse/Doctor |
| GET | `/` | Get all patients | Staff/Doctor/Admin |
| GET | `/stats/summary` | Get patient stats | Staff/Admin |
| GET | `/:id` | Get patient details | Private |
| PUT | `/:id` | Update patient admission | Staff/Nurse/Doctor |
| PUT | `/:id/discharge` | Discharge patient | Doctor/Staff |
| GET | `/:id/history` | Get medical history | Private |

### Doctors (`/api/doctors`)
| Method | Endpoint | Description | Access |
|--------|----------|-------------|--------|
| GET | `/` | Get all doctors | Private |
| GET | `/:id` | Get doctor profile | Private |
| GET | `/:id/appointments` | Get doctor appointments | Private |

### Beds (`/api/beds`)
| Method | Endpoint | Description | Access |
|--------|----------|-------------|--------|
| GET | `/` | Get all beds | Private |
| POST | `/` | Create bed | Admin |
| GET | `/stats/occupancy` | Get bed statistics | Private |
| GET | `/:id` | Get bed details | Private |
| PUT | `/:id` | Update bed | Admin/Staff |
| DELETE | `/:id` | Delete bed | Admin |
| PUT | `/:id/assign` | Assign bed | Staff/Nurse |
| PUT | `/:id/release` | Release bed | Staff/Nurse |

### QR System (`/api/qr`)
| Method | Endpoint | Description | Access |
|--------|----------|-------------|--------|
| POST | `/generate` | Generate QR code | Staff/Nurse |
| POST | `/scan` | Scan QR code | Staff/Nurse/Doctor |
| POST | `/verify` | Verify QR validity | Public |
| GET | `/scans/:admissionId` | Get scan history | Private |

### Chatbot (`/api/chatbot`)
| Method | Endpoint | Description | Access |
|--------|----------|-------------|--------|
| POST | `/message` | Get AI response | Public |
| GET | `/history` | Get conversation history | Private |

### Staff (`/api/staff`)
| Method | Endpoint | Description | Access |
|--------|----------|-------------|--------|
| GET | `/` | Get all staff | Admin/Staff |
| GET | `/:id` | Get staff profile | Private |

### Admin (`/api/admin`)
| Method | Endpoint | Description | Access |
|--------|----------|-------------|--------|
| GET | `/stats` | Get system statistics | Admin |
| GET | `/users` | Get all users | Admin |
| PUT | `/users/:id` | Update user | Admin |
| DELETE | `/users/:id` | Delete user | Admin |
| GET | `/logs` | Get activity logs | Admin |

## 🔐 Authentication

### Login Request
```bash
POST /api/auth/login
Content-Type: application/json

{
  "email": "doctor@hospilink.com",
  "password": "password123"
}
```

### Response
```json
{
  "success": true,
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "user": {
    "_id": "507f1f77bcf86cd799439011",
    "firstName": "John",
    "lastName": "Doe",
    "email": "doctor@hospilink.com",
    "role": "doctor",
    "specialization": "Cardiology"
  }
}
```

### Using Token
Add token to requests:
```bash
Authorization: Bearer <token>
```

## 🤖 AI Features

### Appointment Priority Calculation
- **Algorithm:** Symptom keyword matching
- **Scoring:** HIGH=30pts, MEDIUM=15pts, LOW=5pts
- **Threshold:** ≥70=High, ≥40=Medium, <40=Low

### Gemini AI Chatbot
- **Model:** gemini-flash-latest
- **Temperature:** 0.8
- **Max Tokens:** 1500
- **Emergency Detection:** Automatic flagging

## 📊 Database Schema

### Collections
- `users` - All user accounts (patients, doctors, staff)
- `appointments` - Appointment bookings with AI priority
- `patientadmissions` - Hospital admissions with QR codes
- `beds` - Bed inventory and assignments
- `medicalhistories` - Patient medical records
- `symptomkeywords` - AI priority keywords
- `chatbotlogs` - Chatbot conversation history
- `qrscans` - QR code scan audit trail
- `activitylogs` - System activity logging

## 🔄 Data Migration

Migrate from MySQL to MongoDB:

```bash
# Edit utils/migrate.js with MySQL credentials
npm run migrate
```

Migration preserves:
- ✅ All user accounts with hashed passwords
- ✅ Appointment history and priorities
- ✅ Patient admissions and bed assignments
- ✅ Medical records and history
- ✅ Chatbot conversation logs
- ✅ Activity audit trail

## 📧 Email Templates

### Appointment Confirmation
- Sent on appointment creation
- Includes priority level, doctor info, date/time

### Discharge Notification
- Sent on patient discharge
- Includes discharge summary, follow-up instructions

### OTP Verification
- 6-digit code, 10-minute validity
- Used for password reset, email verification

## 📅 Google Calendar Integration

### Setup OAuth2
1. Create project in Google Cloud Console
2. Enable Google Calendar API
3. Create OAuth2 credentials
4. Set redirect URI: `http://localhost:5000/api/calendar/callback`
5. Add credentials to `.env`

### Features
- Auto-create calendar events on appointment booking
- Send invites to patient email
- 24-hour email reminder
- 30-minute popup reminder

## 🔒 Security Features

- **Helmet:** Security headers
- **CORS:** Cross-origin protection
- **Rate Limiting:** 100 requests/15min per IP
- **JWT:** Stateless authentication
- **bcrypt:** Password hashing (salt factor 10)
- **Input Validation:** express-validator

## 📈 Testing

### Health Check
```bash
GET /api/health
```

### Test with Postman/Thunder Client
Import [MERN_CONVERSION_GUIDE.md](MERN_CONVERSION_GUIDE.md) for sample requests.

## 🚀 Deployment

### MongoDB Atlas
1. Create cluster at mongodb.com/cloud/atlas
2. Update `MONGODB_URI` in `.env`

### Heroku
```bash
heroku create hospilink-api
heroku config:set NODE_ENV=production
heroku config:set JWT_SECRET=...
git push heroku main
```

### DigitalOcean
```bash
# Install PM2
npm install -g pm2

# Start server
pm2 start server.js --name hospilink-api

# Save process list
pm2 save
pm2 startup
```

## 📝 Scripts

```bash
npm start       # Start production server
npm run dev     # Start development server (nodemon)
npm run migrate # Migrate MySQL data to MongoDB
npm run seed    # Seed database with sample data
```

## 🐛 Troubleshooting

### MongoDB Connection Error
- Ensure MongoDB is running: `mongod`
- Check `MONGODB_URI` in `.env`

### JWT Token Invalid
- Check `JWT_SECRET` matches between sessions
- Token expires after 7 days (configurable)

### Email Not Sending
- Use Gmail App Password (not regular password)
- Enable "Less secure app access" in Gmail settings

### Gemini API Error
- Verify `GEMINI_API_KEY` is valid
- Check API quota at console.cloud.google.com

## 📚 Documentation

- [MERN Conversion Guide](../MERN_CONVERSION_GUIDE.md)
- [API Testing Guide](../docs/API_TESTING.md)
- [Deployment Guide](../docs/DEPLOYMENT.md)

## 🤝 Support

For issues or questions:
- Email: support@hospilink.com
- GitHub: [hospilink/backend](https://github.com/hospilink/backend)

## 📄 License

MIT License - See LICENSE file for details

---

**Built with ❤️ for HospiLink**
