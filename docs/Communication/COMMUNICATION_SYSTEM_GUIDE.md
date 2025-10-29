# 📧 Complete Communication System Guide

## 🎯 Overview
This comprehensive communication system allows you to send **Emails** and **SMS** to:
- ✅ All Members
- ✅ Specific Groups (Youth, Prayer Team, etc.)
- ✅ Departments/Ministries
- ✅ Custom Message Groups
- ✅ Individual Members

---

## 📋 Table of Contents
1. [Installation Steps](#installation-steps)
2. [Database Setup](#database-setup)
3. [Email Configuration](#email-configuration)
4. [SMS Configuration](#sms-configuration)
5. [How to Use](#how-to-use)
6. [API Reference](#api-reference)
7. [Troubleshooting](#troubleshooting)

---

## 🚀 Installation Steps

### Step 1: Run Database Setup
```sql
-- Open phpMyAdmin and run this file:
communication_system_setup.sql
```

This creates 9 tables:
- `messages` - Stores all messages
- `message_recipients` - Tracks delivery to each recipient
- `message_templates` - Reusable message templates
- `message_groups` - Custom recipient groups
- `message_group_members` - Group membership
- `scheduled_messages` - Scheduled message queue
- `sms_logs` - SMS delivery logs
- `email_logs` - Email delivery logs
- `communication_settings` - System configuration

### Step 2: Configure Email (Required for Email)

#### Option A: Using Gmail (Recommended for Testing)
1. Go to your Gmail account
2. Enable 2-Factor Authentication
3. Generate an App Password:
   - Go to: https://myaccount.google.com/apppasswords
   - Select "Mail" and "Other (custom name)"
   - Copy the 16-character password

4. Update database settings:
```sql
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
SET setting_value = 'your-app-password' 
WHERE setting_key = 'smtp_password';

UPDATE communication_settings 
SET setting_value = 'Church Name' 
WHERE setting_key = 'email_from_name';

UPDATE communication_settings 
SET setting_value = 'your-email@gmail.com' 
WHERE setting_key = 'email_from_address';
```

#### Option B: Other Email Providers
**Outlook/Office 365:**
- Host: smtp.office365.com
- Port: 587
- Encryption: TLS

**Yahoo:**
- Host: smtp.mail.yahoo.com
- Port: 587
- Encryption: TLS

### Step 3: Install PHPMailer (Optional but Recommended)

#### Method 1: Using Composer (Recommended)
```bash
cd C:\xampp\htdocs\Church_Management_System\admin_dashboard\Communication
composer require phpmailer/phpmailer
```

#### Method 2: Manual Installation
1. Download PHPMailer from: https://github.com/PHPMailer/PHPMailer
2. Extract to `Communication/PHPMailer/`
3. Update `email_handler.php` line 11-13 with correct paths

**Note:** If you don't install PHPMailer, the system will fallback to PHP's `mail()` function.

### Step 4: Configure SMS (Optional)

#### Option A: Twilio (Recommended - Works Globally)
1. Sign up at: https://www.twilio.com
2. Get your credentials:
   - Account SID
   - Auth Token
   - Phone Number

3. Update database:
```sql
UPDATE communication_settings SET setting_value = 'twilio' WHERE setting_key = 'sms_provider';
UPDATE communication_settings SET setting_value = 'YOUR_ACCOUNT_SID' WHERE setting_key = 'sms_api_key';
UPDATE communication_settings SET setting_value = 'YOUR_AUTH_TOKEN' WHERE setting_key = 'sms_api_secret';
UPDATE communication_settings SET setting_value = '+1234567890' WHERE setting_key = 'sms_sender_id';
```

#### Option B: Africa's Talking (For Africa)
1. Sign up at: https://africastalking.com
2. Get your credentials:
   - Username
   - API Key

3. Update database:
```sql
UPDATE communication_settings SET setting_value = 'africastalking' WHERE setting_key = 'sms_provider';
UPDATE communication_settings SET setting_value = 'YOUR_USERNAME' WHERE setting_key = 'sms_api_key';
UPDATE communication_settings SET setting_value = 'YOUR_API_KEY' WHERE setting_key = 'sms_api_secret';
UPDATE communication_settings SET setting_value = 'CHURCH' WHERE setting_key = 'sms_sender_id';
```

---

## 📊 Database Structure

### Members Table (Existing)
Your existing `members` table with fields:
- `member_id` - Unique identifier
- `first_name`, `last_name` - Member name
- `email` - Email address
- `phone` - Phone number
- `church_group` - Department/Group
- `leadership_role` - Ministry/Role
- `status` - Member status (active/inactive)

### Messages Table (New)
Stores all messages sent:
- `message_id` - Unique ID
- `message_type` - announcement, event, prayer_request, newsletter
- `title` - Message subject
- `content` - Message body
- `delivery_channels` - JSON array: ["email", "sms"]
- `status` - draft, scheduled, sending, sent, failed
- `total_recipients`, `total_sent`, `total_failed` - Statistics

---

## 💻 How to Use

### Method 1: Send to All Members
```javascript
fetch('send_message.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
        message_type: 'announcement',
        title: 'Sunday Service Reminder',
        content: 'Join us this Sunday at 9 AM!',
        delivery_channels: ['email', 'sms'],
        audience_type: 'all'
    })
})
.then(response => response.json())
.then(data => console.log(data));
```

### Method 2: Send to Specific Group
```javascript
fetch('send_message.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
        message_type: 'event',
        title: 'Youth Event This Friday',
        content: 'Join us for an exciting youth event!',
        delivery_channels: ['email'],
        audience_type: 'group',
        audience_value: 'Youth'  // church_group value
    })
})
.then(response => response.json())
.then(data => console.log(data));
```

### Method 3: Send to Department/Ministry
```javascript
fetch('send_message.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
        message_type: 'announcement',
        title: 'Leadership Meeting',
        content: 'Meeting tomorrow at 5 PM',
        delivery_channels: ['sms'],
        audience_type: 'ministry',
        audience_value: 'Pastor'  // leadership_role value
    })
})
.then(response => response.json())
.then(data => console.log(data));
```

### Method 4: Send to Individual Members
```javascript
fetch('send_message.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
        message_type: 'general',
        title: 'Personal Message',
        content: 'Hello! This is a personal message.',
        delivery_channels: ['email'],
        audience_type: 'individual',
        member_ids: [1, 5, 10]  // Array of member IDs
    })
})
.then(response => response.json())
.then(data => console.log(data));
```

### Method 5: Schedule Message for Later
```javascript
fetch('send_message.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
        message_type: 'announcement',
        title: 'Scheduled Message',
        content: 'This will be sent later',
        delivery_channels: ['email'],
        audience_type: 'all',
        action: 'schedule',
        scheduled_at: '2025-10-28 09:00:00'
    })
})
.then(response => response.json())
.then(data => console.log(data));
```

---

## 🔧 API Reference

### 1. Send Message
**Endpoint:** `send_message.php`  
**Method:** POST

**Request Body:**
```json
{
    "message_type": "announcement|event|prayer_request|newsletter|general",
    "title": "Message Title",
    "content": "Message content here",
    "delivery_channels": ["email", "sms"],
    "audience_type": "all|group|ministry|custom_group|individual",
    "audience_value": "Group name or ministry name (if applicable)",
    "member_ids": [1, 2, 3],  // For individual type
    "group_id": 1,  // For custom_group type
    "action": "send|schedule",  // Optional
    "scheduled_at": "2025-10-28 09:00:00"  // For scheduled messages
}
```

**Response:**
```json
{
    "success": true,
    "message_id": 123,
    "total_recipients": 50,
    "status": "sent",
    "delivery_stats": {
        "sent": 48,
        "failed": 2
    }
}
```

### 2. Get Recipients
**Endpoint:** `get_recipients.php`  
**Method:** GET

**Available Actions:**
- `?action=groups` - Get all message groups
- `?action=departments` - Get all church groups
- `?action=ministries` - Get all leadership roles
- `?action=members` - Get all members
- `?action=group_members&group_id=1` - Get members in a group
- `?action=search&q=john` - Search members

### 3. Manage Groups
**Endpoint:** `manage_groups.php`

**Create Group (POST):**
```json
{
    "group_name": "Prayer Warriors",
    "group_type": "static",
    "description": "Dedicated prayer team",
    "member_ids": [1, 2, 3, 4, 5]
}
```

**Update Group (PUT):**
```json
{
    "group_id": 1,
    "group_name": "Updated Name",
    "member_ids": [1, 2, 3]
}
```

**Delete Group (DELETE):**
```
?group_id=1
```

---

## 🎨 Integrating with Your HTML

Update your `communication.html` form submission:

```javascript
async function sendMessage() {
    const messageData = {
        message_type: document.getElementById('messageType').value,
        title: document.getElementById('messageTitle').value,
        content: document.getElementById('messageContent').value,
        delivery_channels: getSelectedChannels(),
        audience_type: getAudienceType(),
        audience_value: getAudienceValue()
    };
    
    try {
        const response = await fetch('send_message.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(messageData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert(`Message sent successfully to ${result.total_recipients} recipients!`);
            // Show success message
            document.getElementById('successMessage').style.display = 'block';
        } else {
            alert('Error: ' + result.error);
        }
    } catch (error) {
        alert('Failed to send message: ' + error.message);
    }
}

function getSelectedChannels() {
    const channels = [];
    if (document.getElementById('emailBtn').classList.contains('active')) {
        channels.push('email');
    }
    if (document.getElementById('smsBtn').classList.contains('active')) {
        channels.push('sms');
    }
    return channels;
}
```

---

## 🐛 Troubleshooting

### Emails Not Sending

**Problem:** Emails fail to send

**Solutions:**
1. Check SMTP credentials in `communication_settings` table
2. Verify your email provider allows SMTP
3. Check firewall/antivirus isn't blocking port 587
4. Try using port 465 with SSL instead of 587/TLS
5. Check PHP error logs: `C:\xampp\php\logs\php_error_log`

**Test Email Configuration:**
```php
<?php
require_once 'email_handler.php';
require_once 'db_connect.php';

$result = testEmailConfig('your-test-email@gmail.com');
echo $result ? 'Email sent!' : 'Email failed!';
?>
```

### SMS Not Sending

**Problem:** SMS messages fail

**Solutions:**
1. Verify SMS provider credentials
2. Check phone numbers are in international format (+1234567890)
3. Verify you have credits/balance with your SMS provider
4. Check provider API is accessible (firewall/network)
5. Review `sms_logs` table for error messages

**Test SMS Configuration:**
```php
<?php
require_once 'sms_handler.php';
require_once 'db_connect.php';

$result = testSMSConfig('+1234567890');
echo $result ? 'SMS sent!' : 'SMS failed!';
?>
```

### No Recipients Found

**Problem:** "No recipients found" error

**Solutions:**
1. Check members have `status = 'active'`
2. Verify members have email/phone populated
3. Check `audience_type` and `audience_value` match your data
4. Query members table directly to verify data

---

## 📈 Advanced Features

### 1. Create Custom Message Groups
```sql
-- Example: Create "New Members" group (joined in last 6 months)
INSERT INTO message_groups (group_name, group_type, description, filter_criteria)
VALUES ('New Members', 'dynamic', 'Members who joined recently', 
        '{"months": 6, "field": "created_at"}');

-- Add members to the group
INSERT INTO message_group_members (group_id, member_id)
SELECT 1, member_id 
FROM members 
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH);
```

### 2. Message Templates
```sql
-- Use existing templates
SELECT * FROM message_templates;

-- Create new template
INSERT INTO message_templates (template_name, template_type, subject, content)
VALUES ('Birthday Greeting', 'general', 'Happy Birthday {first_name}!',
        'Dear {first_name},\n\nHappy Birthday! May God bless you abundantly.\n\n{church_name}');
```

### 3. Scheduled Messages
Messages with `action: 'schedule'` are stored and need a cron job to send them.

**Create Cron Job (Linux):**
```bash
# Edit crontab
crontab -e

# Add this line (runs every 5 minutes)
*/5 * * * * php /path/to/process_scheduled_messages.php
```

**Windows Task Scheduler:**
1. Create `process_scheduled_messages.php`
2. Task Scheduler → Create Task
3. Trigger: Every 5 minutes
4. Action: Start Program → `C:\xampp\php\php.exe`
5. Arguments: `C:\xampp\htdocs\...\process_scheduled_messages.php`

---

## 📊 Monitoring & Reports

### Check Message Status
```sql
-- View all messages
SELECT * FROM messages ORDER BY created_at DESC LIMIT 10;

-- View delivery statistics
SELECT 
    m.message_id,
    m.title,
    m.total_recipients,
    m.total_sent,
    m.total_failed,
    m.status,
    m.sent_at
FROM messages m
ORDER BY m.created_at DESC;

-- View failed deliveries
SELECT * FROM message_recipients 
WHERE delivery_status = 'failed';

-- Email logs
SELECT * FROM email_logs 
WHERE status = 'failed' 
ORDER BY created_at DESC;

-- SMS logs
SELECT * FROM sms_logs 
WHERE status = 'failed' 
ORDER BY created_at DESC;
```

---

## 🔒 Security Notes

1. **Never commit credentials** to version control
2. **Encrypt sensitive data** in production
3. **Validate all inputs** before processing
4. **Use HTTPS** in production
5. **Rate limit** SMS/Email to prevent abuse
6. **Backup database** regularly

---

## 💡 Next Steps

1. ✅ Run `communication_system_setup.sql`
2. ✅ Configure email settings
3. ✅ (Optional) Configure SMS settings
4. ✅ Test with a small group first
5. ✅ Create message groups based on your church structure
6. ✅ Update your HTML form to use the new APIs
7. ✅ Monitor logs and adjust as needed

---

## 📞 Support

For issues or questions:
1. Check the Troubleshooting section
2. Review error logs
3. Verify database connectivity
4. Test with simple examples first

---

**Built with ❤️ for Church Management**
