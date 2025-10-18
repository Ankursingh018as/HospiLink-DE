@echo off
echo ===============================================
echo    HospiLink Database Setup Script
echo    Automated Database Import for Windows
echo ===============================================
echo.

:: Check if MySQL is running
echo [1/4] Checking MySQL status...
tasklist /FI "IMAGENAME eq mysqld.exe" 2>NUL | find /I /N "mysqld.exe">NUL
if "%ERRORLEVEL%"=="0" (
    echo [OK] MySQL is running
) else (
    echo [ERROR] MySQL is not running!
    echo Please start MySQL from XAMPP Control Panel
    pause
    exit /b 1
)

echo.
echo [2/4] MySQL is ready. Preparing to import database...
echo.

:: Set MySQL path - Try multiple common locations
set MYSQL_PATH=C:\xampp\mysql\bin
set MYSQL_PATH_D=D:\xampp\mysql\bin
set DB_FILE=%~dp0database\hospilink_schema.sql

:: Check if database file exists
if not exist "%DB_FILE%" (
    echo [ERROR] Database file not found!
    echo Expected location: %DB_FILE%
    pause
    exit /b 1
)

echo [3/4] Database file found: %DB_FILE%
echo.

:: Find the correct MySQL path
set MYSQL_EXE=
if exist "%MYSQL_PATH%\mysql.exe" (
    set MYSQL_EXE=%MYSQL_PATH%\mysql.exe
    echo Found MySQL at: %MYSQL_PATH%
) else if exist "%MYSQL_PATH_D%\mysql.exe" (
    set MYSQL_EXE=%MYSQL_PATH_D%\mysql.exe
    echo Found MySQL at: %MYSQL_PATH_D%
) else (
    echo [ERROR] MySQL not found in common locations!
    echo Trying system PATH...
    where mysql.exe >nul 2>&1
    if errorlevel 1 (
        echo MySQL not found in system PATH either.
        echo Please ensure MySQL is installed and in your PATH.
        pause
        exit /b 1
    )
    set MYSQL_EXE=mysql.exe
)

echo.
echo [4/4] Importing database...
echo.

:: Import the database (no password required for XAMPP default)
"%MYSQL_EXE%" -u root < "%DB_FILE%"

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ===============================================
    echo    SUCCESS! Database imported successfully!
    echo ===============================================
    echo.
    echo Next steps:
    echo 1. Open your browser
    echo 2. Go to: http://localhost/HospiLink/sign_new.html
    echo 3. Login with test accounts:
    echo.
    echo    ADMIN:
    echo    Email: admin@hospilink.com
    echo    Password: admin123
    echo.
    echo    DOCTOR:
    echo    Email: dr.patel@hospilink.com
    echo    Password: doctor123
    echo.
    echo    PATIENT:
    echo    Email: patient@hospilink.com
    echo    Password: patient123
    echo.
    echo ===============================================
) else (
    echo.
    echo ===============================================
    echo    ERROR! Database import failed
    echo ===============================================
    echo.
    echo Possible reasons:
    echo 1. Wrong MySQL password
    echo 2. MySQL not running
    echo 3. Database file corrupted
    echo.
    echo Please check and try again.
    echo ===============================================
)

echo.
pause
