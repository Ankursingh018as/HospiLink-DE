# HospiLink React Frontend

## 🚀 Quick Start

### 1. Install Dependencies
```bash
npm install
```

### 2. Configure Environment
The `.env` file is already configured for local development:
```env
REACT_APP_API_URL=http://localhost:5000/api
```

### 3. Start Development Server
```bash
npm start
```

Frontend will run on: **http://localhost:3000**

## 📦 What's Included

### ✅ Complete Setup
- **React 18** with functional components and hooks
- **React Router v6** for navigation
- **Axios** for API calls with interceptors
- **React Query** for data fetching and caching
- **AuthContext** for global authentication state
- **Protected Routes** with role-based access control

### ✅ Created Components & Pages

#### Authentication Pages
- **Login Page** (`src/pages/Login.js`) - Complete with demo accounts
- **Register Page** (`src/pages/Register.js`) - Full registration form

#### Core Components
- **ProtectedRoute** - Role-based route protection
- **AuthContext** - Global auth state management

#### API Service Layer
- **api.js** - Complete API integration for all endpoints:
  - ✅ Authentication (login, register, logout, profile)
  - ✅ Appointments (CRUD, claim, stats)
  - ✅ Patients (admit, discharge, history)
  - ✅ Beds (assign, release, stats)
  - ✅ Doctors, Staff, Admin
  - ✅ QR System, Chatbot

### ✅ Features Implemented

**Authentication Flow:**
- JWT token storage in localStorage
- Automatic token refresh
- Role-based redirects after login
- Protected routes for each user role

**API Integration:**
- Axios interceptors for token injection
- Automatic 401 handling (redirect to login)
- Error handling
- Proxy configuration for development

**Routing:**
- Public routes: `/login`, `/register`
- Patient routes: `/patient/dashboard`
- Doctor routes: `/doctor/dashboard`
- Staff routes: `/staff/dashboard`
- Admin routes: `/admin/dashboard`

## 📂 Project Structure

```
frontend/
├── public/
│   └── index.html           # HTML template
│
├── src/
│   ├── components/          # Reusable components
│   │   └── ProtectedRoute.js   # Route protection
│   │
│   ├── context/             # React Context
│   │   └── AuthContext.js      # Authentication state
│   │
│   ├── pages/               # Page components
│   │   ├── Login.js            # Login page ✅
│   │   └── Register.js         # Registration page ✅
│   │
│   ├── services/            # API service layer
│   │   └── api.js              # Complete API integration
│   │
│   ├── styles/              # CSS files
│   │   └── sign.css            # Login/Register styles
│   │
│   ├── utils/               # Helper functions
│   │
│   ├── App.js               # Main app with routing
│   └── index.js             # React entry point
│
├── .env                     # Environment variables
├── package.json             # Dependencies
└── README.md                # This file
```

## 🎯 Current Status

### ✅ Completed (Phase 1)
- [x] React project structure
- [x] Package.json with all dependencies
- [x] API service layer (complete)
- [x] AuthContext with login/register/logout
- [x] Protected routes with role checking
- [x] Login page (fully functional)
- [x] Register page (fully functional)
- [x] React Router setup
- [x] Axios interceptors

### ⏳ Next Steps (Phase 2)
- [ ] Patient Dashboard
- [ ] Doctor Dashboard
- [ ] Staff Dashboard
- [ ] Admin Dashboard
- [ ] Appointment booking page
- [ ] Bed management page
- [ ] QR scanning page
- [ ] Chatbot interface
- [ ] Profile pages

## 🧪 Testing the Login Flow

### 1. Start Backend
```bash
cd ../backend
npm run dev
```

### 2. Start Frontend
```bash
npm start
```

### 3. Test Login
Visit: http://localhost:3000

**Demo Accounts:**
- **Patient:** patient@hospilink.com / password123
- **Doctor:** doctor@hospilink.com / password123
- **Admin:** admin@hospilink.com / password123

Click any "Demo" button on login page to auto-fill credentials.

### 4. Test Registration
- Click "Sign Up" link
- Fill in the registration form
- Upon success, automatically logged in and redirected

## 📱 Features

### Login Page
- Email/password authentication
- "Remember me" checkbox
- Forgot password link
- Demo account quick-access buttons
- Responsive design
- Error handling
- Loading states

### Register Page
- Complete registration form
- First/Last name fields
- Email validation
- Password confirmation
- Phone number
- Date of birth
- Gender selection
- Real-time error messages
- Redirect to dashboard on success

### Protected Routes
- Automatic redirect to login if not authenticated
- Role-based access control
- Loading state during auth check
- Smooth navigation

### API Integration
- Centralized API service
- JWT token management
- Request/response interceptors
- Error handling
- TypeScript-ready structure

## 🔐 Security Features

- JWT tokens stored in localStorage
- Automatic token expiration handling
- CORS enabled for credentials
- Protected routes with role checking
- Input validation on forms
- Password confirmation on registration

## 🎨 UI/UX

- Maintains exact same CSS from PHP version
- Responsive design
- Modern gradient backgrounds
- Smooth animations
- Loading states
- Error messages
- Success feedback

## 📝 Available Scripts

```bash
# Start development server
npm start

# Build for production
npm run build

# Run tests
npm test

# Eject (not recommended)
npm run eject
```

## 🔧 Configuration

### API URL
Edit `.env` to change backend URL:
```env
REACT_APP_API_URL=http://localhost:5000/api
```

### Proxy
The `package.json` includes proxy configuration:
```json
"proxy": "http://localhost:5000"
```

This allows relative API URLs in development.

## 🚀 Deployment

### Build Production Bundle
```bash
npm run build
```

Creates optimized production build in `build/` folder.

### Deploy to Vercel
```bash
npm install -g vercel
vercel
```

### Deploy to Netlify
1. Connect GitHub repository
2. Build command: `npm run build`
3. Publish directory: `build`
4. Environment variable: `REACT_APP_API_URL`

## 📚 Next Development Steps

### Priority 1: Dashboards
1. Create Patient Dashboard with appointment list
2. Create Doctor Dashboard with patient queue
3. Create Staff Dashboard with bed management
4. Create Admin Dashboard with system stats

### Priority 2: Core Pages
1. Appointment booking page
2. Bed assignment page
3. Patient admission page
4. Medical history page

### Priority 3: Advanced Features
1. QR code scanner component
2. Chatbot interface
3. Real-time notifications
4. Charts and analytics

## 🤝 Integration with Backend

### API Endpoints Used
- `POST /api/auth/register` - User registration
- `POST /api/auth/login` - User login
- `POST /api/auth/logout` - User logout
- `GET /api/auth/me` - Get current user

### Token Flow
1. User logs in → Backend returns JWT token
2. Token stored in localStorage
3. Every API request includes token in Authorization header
4. Backend validates token and returns user data
5. On 401 error, redirect to login

## 🐛 Troubleshooting

### CORS Errors
- Ensure backend is running on port 5000
- Check CORS is enabled in backend with credentials

### API Connection Issues
- Verify `REACT_APP_API_URL` in `.env`
- Check backend is running: `curl http://localhost:5000/api/health`

### Login Not Working
- Open browser console for errors
- Check backend logs
- Verify demo accounts exist in database

## 📖 Documentation

- [React Documentation](https://react.dev/)
- [React Router](https://reactrouter.com/)
- [Axios](https://axios-http.com/)
- [React Query](https://tanstack.com/query/latest)

## 🎉 Success!

The React frontend foundation is complete! You can now:
1. ✅ Login with demo accounts
2. ✅ Register new users
3. ✅ See role-based redirects
4. ✅ Experience protected routes

**Next:** Build dashboard pages for each user role!

---

**Version:** 2.0.0
**Last Updated:** January 11, 2026
