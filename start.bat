@echo off
echo ====================================
echo   HospiLink MERN Stack Launcher
echo ====================================
echo.

REM Check if MongoDB is running
echo [1/3] Checking MongoDB...
tasklist /FI "IMAGENAME eq mongod.exe" 2>NUL | find /I /N "mongod.exe">NUL
if "%ERRORLEVEL%"=="0" (
    echo ✓ MongoDB is running
) else (
    echo ✗ MongoDB is NOT running!
    echo Please start MongoDB first: mongod
    echo.
    pause
    exit /b 1
)

echo.
echo [2/3] Starting Backend Server...
start cmd /k "cd backend && npm run dev"
timeout /t 3 /nobreak > NUL

echo.
echo [3/3] Starting Frontend Server...
start cmd /k "cd frontend && npm start"

echo.
echo ====================================
echo   HospiLink is starting...
echo ====================================
echo.
echo Backend:  http://localhost:5000
echo Frontend: http://localhost:3000
echo.
echo Two terminal windows will open:
echo   1. Backend (Express API)
echo   2. Frontend (React App)
echo.
echo Your browser will open automatically.
echo.
echo Press any key to exit this launcher...
pause > NUL
