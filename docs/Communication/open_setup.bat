@echo off
echo ============================================
echo  Social Media Setup - Quick Launcher
echo ============================================
echo.
echo This will open:
echo 1. phpMyAdmin (for SQL import)
echo 2. Test page (for verification)
echo 3. Communication page (main interface)
echo.
pause

start http://localhost/phpmyadmin
timeout /t 2 /nobreak > nul

start http://localhost/Church_Management_System/admin_dashboard/Communication/test_social_api.php
timeout /t 2 /nobreak > nul

start http://localhost/Church_Management_System/admin_dashboard/Communication/communication.html

echo.
echo ============================================
echo All pages opened in your browser!
echo ============================================
echo.
echo NEXT STEPS:
echo 1. In phpMyAdmin - Select 'church_management' database
echo 2. Click SQL tab and paste contents of social_media_setup.sql
echo 3. Click GO button
echo 4. Check test page for green status indicators
echo 5. Go to Communication page and click 'Social Media' tab
echo.
echo See QUICK_START.txt for detailed instructions
echo ============================================
pause
