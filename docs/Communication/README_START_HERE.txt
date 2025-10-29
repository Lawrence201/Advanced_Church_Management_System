================================================================================
           📧 COMMUNICATION SYSTEM - START HERE! 
================================================================================

Welcome to your new Email & SMS Communication System!

This system allows you to send messages to:
✅ ALL MEMBERS
✅ SPECIFIC GROUPS (Youth, Prayer Team, etc.)
✅ DEPARTMENTS
✅ MINISTRIES  
✅ INDIVIDUAL MEMBERS

Via:
✅ EMAIL
✅ SMS (optional)

================================================================================
📁 WHAT YOU HAVE
================================================================================

DATABASE SETUP:
✅ communication_system_setup.sql - Run this first!

BACKEND FILES:
✅ send_message.php - Main API for sending messages
✅ email_handler.php - Email delivery
✅ sms_handler.php - SMS delivery  
✅ get_recipients.php - Get available recipients
✅ manage_groups.php - Manage custom groups
✅ process_scheduled_messages.php - For cron jobs

FRONTEND FILES:
✅ communication.html - Your main interface
✅ communication_script.js - JavaScript functions

TESTING:
✅ test_setup.php - Verify your setup!

DOCUMENTATION:
✅ QUICK_SETUP_GUIDE.txt - Quick start (5 minutes)
✅ COMMUNICATION_SYSTEM_GUIDE.md - Complete guide
✅ README_START_HERE.txt - This file

================================================================================
🚀 QUICK START (5 MINUTES)
================================================================================

STEP 1: Setup Database
-----------------------
1. Open phpMyAdmin (http://localhost/phpmyadmin)
2. Select your database: church_management_system
3. Click "Import" 
4. Upload: communication_system_setup.sql
5. Click "Go"

✅ You should see: "9 tables created successfully"


STEP 2: Configure Email
-----------------------
Run these SQL queries in phpMyAdmin:

UPDATE communication_settings 
SET setting_value = 'smtp.gmail.com' 
WHERE setting_key = 'smtp_host';

UPDATE communication_settings 
SET setting_value = '587' 
WHERE setting_key = 'smtp_port';

UPDATE communication_settings 
SET setting_value = 'your-email@gmail.com' 
WHERE setting_key = 'smtp_username';

UPDATE communication_settings 
SET setting_value = 'your-gmail-app-password' 
WHERE setting_key = 'smtp_password';

UPDATE communication_settings 
SET setting_value = 'Your Church Name' 
WHERE setting_key = 'email_from_name';

UPDATE communication_settings 
SET setting_value = 'your-email@gmail.com' 
WHERE setting_key = 'email_from_address';

📧 GET GMAIL APP PASSWORD:
1. Go to: https://myaccount.google.com/apppasswords
2. Enable 2-Factor Authentication first
3. Generate app password for "Mail"
4. Copy the 16-character password
5. Use it above


STEP 3: Test Your Setup
-----------------------
1. Open in browser:
   http://localhost/Church_Management_System/admin_dashboard/Communication/test_setup.php

2. Verify all tests pass
3. Send a test email to yourself
4. If successful, you're ready!


STEP 4: Send Your First Message
-------------------------------
1. Open: communication.html
2. Fill in:
   - Message Type: Announcement
   - Audience: All Members
   - Title: "Test Message"
   - Content: "Testing our new communication system!"
3. Select: Email
4. Click: "Send Now"

✅ Check results in phpMyAdmin:
   SELECT * FROM messages ORDER BY created_at DESC LIMIT 1;

================================================================================
📊 USING THE SYSTEM
================================================================================

SEND TO ALL MEMBERS:
-------------------
- Audience Type: all
- Sends to every active member

SEND TO A GROUP:
---------------
- Audience Type: group
- Audience Value: "Youth" (or any church_group value)

SEND TO MINISTRY:
----------------
- Audience Type: ministry  
- Audience Value: "Pastor" (or any leadership_role value)

SEND TO INDIVIDUALS:
-------------------
- Audience Type: individual
- Member IDs: [1, 2, 3, 4]

SEND TO CUSTOM GROUP:
--------------------
- Audience Type: custom_group
- Group ID: 1

================================================================================
🔧 OPTIONAL: SMS SETUP
================================================================================

TWILIO (Recommended - Works Globally):
--------------------------------------
1. Sign up: https://www.twilio.com
2. Get: Account SID, Auth Token, Phone Number
3. Update settings:

UPDATE communication_settings 
SET setting_value = 'twilio' 
WHERE setting_key = 'sms_provider';

UPDATE communication_settings 
SET setting_value = 'YOUR_ACCOUNT_SID' 
WHERE setting_key = 'sms_api_key';

UPDATE communication_settings 
SET setting_value = 'YOUR_AUTH_TOKEN' 
WHERE setting_key = 'sms_api_secret';

UPDATE communication_settings 
SET setting_value = '+1234567890' 
WHERE setting_key = 'sms_sender_id';


AFRICA'S TALKING (For Africa):
------------------------------
1. Sign up: https://africastalking.com
2. Get: Username, API Key
3. Update settings:

UPDATE communication_settings 
SET setting_value = 'africastalking' 
WHERE setting_key = 'sms_provider';

UPDATE communication_settings 
SET setting_value = 'YOUR_USERNAME' 
WHERE setting_key = 'sms_api_key';

UPDATE communication_settings 
SET setting_value = 'YOUR_API_KEY' 
WHERE setting_key = 'sms_api_secret';

================================================================================
💡 EXAMPLE API CALLS
================================================================================

JAVASCRIPT - Send to All Members:
---------------------------------
fetch('send_message.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
        message_type: 'announcement',
        title: 'Sunday Service',
        content: 'Join us this Sunday at 9 AM!',
        delivery_channels: ['email'],
        audience_type: 'all'
    })
})
.then(response => response.json())
.then(data => console.log(data));


JAVASCRIPT - Send to Youth Group:
---------------------------------
fetch('send_message.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
        message_type: 'event',
        title: 'Youth Night',
        content: 'Youth event this Friday at 7 PM!',
        delivery_channels: ['email', 'sms'],
        audience_type: 'group',
        audience_value: 'Youth'
    })
})
.then(response => response.json())
.then(data => console.log(data));

================================================================================
🐛 TROUBLESHOOTING
================================================================================

EMAILS NOT SENDING:
------------------
❌ Problem: Emails fail to send
✅ Solution:
   - Verify SMTP settings in communication_settings table
   - Make sure you used Gmail App Password (not regular password)
   - Check firewall isn't blocking port 587
   - Try port 465 instead
   - Check: SELECT * FROM email_logs WHERE status = 'failed';

NO RECIPIENTS FOUND:
-------------------
❌ Problem: "No recipients found" error
✅ Solution:
   - Verify members have status = 'active'
   - Check members have email/phone filled
   - Run: SELECT * FROM members WHERE status = 'active';

DATABASE ERROR:
--------------
❌ Problem: Database connection failed
✅ Solution:
   - Check XAMPP MySQL is running
   - Verify db_connect.php credentials
   - Ensure database name is correct

================================================================================
📈 MONITORING YOUR MESSAGES
================================================================================

View All Messages:
SELECT * FROM messages ORDER BY created_at DESC;

View Delivery Stats:
SELECT 
    m.title,
    m.total_recipients,
    m.total_sent,
    m.total_failed,
    m.status,
    m.sent_at
FROM messages m
ORDER BY m.created_at DESC;

View Failed Deliveries:
SELECT * FROM message_recipients WHERE delivery_status = 'failed';

View Email Logs:
SELECT * FROM email_logs ORDER BY created_at DESC LIMIT 10;

View SMS Logs:
SELECT * FROM sms_logs ORDER BY created_at DESC LIMIT 10;

================================================================================
📚 NEED MORE HELP?
================================================================================

1. Read QUICK_SETUP_GUIDE.txt for detailed instructions
2. Read COMMUNICATION_SYSTEM_GUIDE.md for complete documentation
3. Run test_setup.php to verify configuration
4. Check database logs for errors

================================================================================
🎉 YOU'RE ALL SET!
================================================================================

Your church communication system is ready to use!

Next Steps:
1. ✅ Run communication_system_setup.sql
2. ✅ Configure email settings
3. ✅ Run test_setup.php
4. ✅ Send your first message!
5. ✅ (Optional) Configure SMS

Happy messaging! 📧📱

Built with ❤️ for Church Management
================================================================================
