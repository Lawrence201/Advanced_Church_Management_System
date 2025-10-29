# Church Management System - Members Module

## Overview
This is the members management module for the Church Management System. It provides a complete backend API to manage church members with database integration.

## Files Structure

```
Members/
├── members.html          # Frontend interface
├── db_config.php        # Database configuration
├── get_members.php      # Fetch members API
├── save_member.php      # Add/Update members API
├── delete_member.php    # Delete members API
├── get_stats.php        # Member statistics API
└── README.md           # This file
```

## Database Setup

### Prerequisites
- XAMPP with MySQL/MariaDB
- PHP 7.4 or higher
- MySQL database named `church_management_system`

### Database Configuration
Update the database credentials in `db_config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'church_management_system');
```

### Running the SQL Script
1. Open phpMyAdmin (http://localhost/phpmyadmin)
2. Create a new database named `church_management_system`
3. Import the SQL schema file or run the SQL commands to create all tables

## API Endpoints

### 1. Get Members (`get_members.php`)
**Method:** GET

**Parameters:**
- `filter` (optional): 'all', 'active', 'inactive', 'new', 'visitor'
- `search` (optional): Search term for filtering members
- `limit` (optional): Number of records to return (default: 100)
- `offset` (optional): Pagination offset (default: 0)

**Example:**
```javascript
fetch('get_members.php?filter=active&search=John')
```

**Response:**
```json
{
    "success": true,
    "message": "Members fetched successfully",
    "data": {
        "members": [...],
        "total": 100,
        "showing": 10
    }
}
```

### 2. Save Member (`save_member.php`)
**Method:** POST

**Body (JSON):**
```json
{
    "member_id": 1,              // Optional, for updates
    "first_name": "John",
    "last_name": "Doe",
    "email": "john@example.com",
    "phone": "1234567890",
    "date_of_birth": "1990-01-01",
    "gender": "male",
    "marital_status": "married",
    "occupation": "Engineer",
    "address": "123 Main St",
    "city": "Accra",
    "region": "Greater Accra",
    "status": "active",
    "church_group": "judah",
    "leadership_role": "none",
    "baptism_status": "baptized",
    "spiritual_growth": "growing",
    "membership_type": "full",
    "notes": "Some notes",
    "emergency_name": "Jane Doe",
    "emergency_phone": "0987654321",
    "emergency_relation": "Spouse",
    "ministries": [1, 2],        // Array of ministry IDs
    "departments": [3]           // Array of department IDs
}
```

**Response:**
```json
{
    "success": true,
    "message": "Member added successfully",
    "data": {
        "member_id": 14
    }
}
```

### 3. Delete Member (`delete_member.php`)
**Method:** POST or DELETE

**Body (JSON):**
```json
{
    "member_id": 1
}
```

**Or Query Parameter:**
```
delete_member.php?member_id=1
```

**Response:**
```json
{
    "success": true,
    "message": "Member 'John Doe' deleted successfully",
    "data": {
        "member_id": 1
    }
}
```

### 4. Get Statistics (`get_stats.php`)
**Method:** GET

**Response:**
```json
{
    "success": true,
    "message": "Statistics fetched successfully",
    "data": {
        "total_members": 485,
        "active_members": 67,
        "birthday_this_month": 23,
        "males": 215,
        "females": 312,
        "children": 80,
        "new_members": 15,
        "inactive_members": 18,
        "visitors": 12,
        "groups": {
            "dunamis": 120,
            "judah": 150
        },
        "ministries": {
            "youth-ministry": 45,
            "children-ministry": 30
        }
    }
}
```

## Frontend Integration

### Loading Members
```javascript
async function loadMembers(filter = 'all', search = '') {
    const response = await fetch(`get_members.php?filter=${filter}&search=${encodeURIComponent(search)}`);
    const result = await response.json();

    if (result.success) {
        // Process members data
        members = result.data.members;
    }
}
```

### Adding/Updating Members
```javascript
async function saveMember() {
    const memberData = {
        first_name: "John",
        last_name: "Doe",
        email: "john@example.com",
        phone: "1234567890",
        // ... other fields
    };

    const response = await fetch('save_member.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(memberData)
    });

    const result = await response.json();
    if (result.success) {
        console.log(result.message);
    }
}
```

### Deleting Members
```javascript
async function deleteMember(memberId) {
    const response = await fetch('delete_member.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ member_id: memberId })
    });

    const result = await response.json();
    if (result.success) {
        console.log(result.message);
    }
}
```

## Features

### Current Features
- ✅ View all members with filtering and search
- ✅ Add new members
- ✅ Edit existing members
- ✅ Delete members
- ✅ Member statistics dashboard
- ✅ Filter by status (Active, Inactive, Visitors, New)
- ✅ Search functionality
- ✅ Emergency contact management
- ✅ Ministry and department associations
- ✅ Responsive design

### Database Relationships
- Members can have emergency contacts (one-to-many)
- Members can belong to multiple ministries (many-to-many)
- Members can belong to multiple departments (many-to-many)
- All relationships use CASCADE DELETE for data integrity

## Security Features

1. **SQL Injection Prevention:** Uses prepared statements
2. **Input Sanitization:** All inputs are sanitized before processing
3. **CORS Headers:** Configured for security
4. **Error Handling:** Comprehensive try-catch blocks
5. **Data Validation:** Required fields validation

## Testing

### Test the Connection
1. Start XAMPP (Apache and MySQL)
2. Navigate to: `http://localhost/Church_Management_System/admin_dashboard/Members/members.html`
3. Open browser console (F12) to check for errors
4. Try adding, editing, and deleting members

### Test API Endpoints
```bash
# Test Get Members
http://localhost/Church_Management_System/admin_dashboard/Members/get_members.php

# Test Stats
http://localhost/Church_Management_System/admin_dashboard/Members/get_stats.php
```

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Verify XAMPP MySQL is running
   - Check database credentials in `db_config.php`
   - Ensure database `church_management_system` exists

2. **CORS Errors**
   - Ensure all PHP files have CORS headers
   - Check browser console for specific errors

3. **Data Not Loading**
   - Check browser console for JavaScript errors
   - Verify API endpoints are accessible
   - Check database has data

4. **Insert/Update Fails**
   - Check required fields are provided
   - Verify email is unique
   - Check database constraints

## Future Enhancements

- [ ] Photo upload functionality
- [ ] Bulk import/export (CSV, Excel)
- [ ] Advanced analytics
- [ ] Email notifications
- [ ] SMS integration
- [ ] Attendance tracking integration
- [ ] Birthday reminders
- [ ] Member reports generation

## Support

For issues or questions:
1. Check the browser console for errors
2. Check PHP error logs in XAMPP
3. Verify database structure matches schema
4. Ensure all files are in correct locations

## License

This is part of the Church Management System project.
