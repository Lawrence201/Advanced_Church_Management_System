# 🔧 Executive Summary Troubleshooting Guide

## Quick Fix Steps

### Step 1: Test the API Directly
Open this URL in your browser:
```
http://localhost/Church_Management_System/admin_dashboard/Report/get_executive_summary.php
```

**Expected Result**: You should see JSON data like:
```json
{
  "success": true,
  "data": { ... },
  "generated_at": "2025-10-27 16:00:00"
}
```

**If you see an error**: Note the error message and proceed to Step 2.

---

### Step 2: Run the Test Script
Open this URL:
```
http://localhost/Church_Management_System/admin_dashboard/Report/test_executive_api.php
```

This will show you:
- ✅ or ❌ Database connection status
- ✅ or ❌ Query test results
- Directory structure information

---

### Step 3: Check Browser Console
1. Open the Report page
2. Click "Executive Summary" tab
3. Press F12 to open Developer Tools
4. Click "Console" tab
5. Look for error messages

**Common errors and solutions:**

#### Error: "Failed to fetch"
**Solution**: 
- Check if Apache is running
- Verify the file path is correct
- Check file permissions

#### Error: "Unexpected token"
**Solution**:
- API is returning HTML instead of JSON
- Check for PHP errors in the API file
- Look at the Network tab to see the actual response

#### Error: "Cannot read property 'total' of undefined"
**Solution**:
- API is not returning expected data structure
- Check API response in Network tab
- Verify database has data

---

### Step 4: Check Error Log
Look for this file:
```
C:\xampp\htdocs\Church_Management_System\admin_dashboard\Report\executive_summary_errors.log
```

This file will contain detailed error messages from the PHP API.

---

## Common Issues & Solutions

### Issue 1: "Loading executive summary..." Never Disappears

**Possible Causes**:
1. JavaScript file not loaded
2. API not responding
3. JavaScript error

**Solutions**:
1. Check browser console for errors
2. Verify `executive_summary.js` is loaded (Network tab)
3. Test API directly (Step 1 above)
4. Check if Chart.js is loaded

---

### Issue 2: API Returns Error

**Check these**:
1. Database connection settings in `get_executive_summary.php`:
   ```php
   $host = 'localhost';
   $dbname = 'church_management_system';
   $username = 'root';
   $password = '';
   ```

2. Database exists and has tables:
   - members
   - attendance
   - events
   - messages
   - offerings, tithes, project_offerings, welfare_contributions

3. MySQL service is running

---

### Issue 3: Charts Not Showing

**Solutions**:
1. Check if Chart.js is loaded:
   - Open browser console
   - Type: `typeof Chart`
   - Should return "function"

2. Check canvas elements exist:
   - Open Elements tab in DevTools
   - Search for: `executiveGrowthChart`
   - Should find canvas elements

3. Check for JavaScript errors in console

---

### Issue 4: Data Shows as 0 or Empty

**This is normal if**:
- You have no data in the database yet
- Tables are empty
- No records match the query criteria

**To verify**:
1. Check your database in phpMyAdmin
2. Look at the members table - should have records
3. Check attendance table - should have records
4. Check financial tables - should have records

---

## Manual Testing Steps

### Test 1: Database Connection
```php
<?php
$conn = new mysqli('localhost', 'root', '', 'church_management_system');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "Connected successfully";
$conn->close();
?>
```

### Test 2: Simple Query
```php
<?php
$conn = new mysqli('localhost', 'root', '', 'church_management_system');
$result = $conn->query("SELECT COUNT(*) as count FROM members");
$row = $result->fetch_assoc();
echo "Total members: " . $row['count'];
$conn->close();
?>
```

### Test 3: API Response
```javascript
fetch('get_executive_summary.php')
  .then(response => response.json())
  .then(data => console.log(data))
  .catch(error => console.error('Error:', error));
```

---

## Quick Fixes

### Fix 1: Clear Browser Cache
1. Press Ctrl + Shift + Delete
2. Clear cached images and files
3. Reload page

### Fix 2: Check File Permissions
Make sure these files are readable:
- `get_executive_summary.php`
- `executive_summary.js`
- `report.html`

### Fix 3: Restart Apache
1. Open XAMPP Control Panel
2. Stop Apache
3. Start Apache
4. Try again

### Fix 4: Check PHP Error Log
Location: `C:\xampp\php\logs\php_error_log`

Look for recent errors related to the executive summary.

---

## Still Not Working?

### Debug Mode
Add this to the top of `get_executive_summary.php`:
```php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

Then access the API directly and you'll see any PHP errors.

### Check Network Tab
1. Open DevTools (F12)
2. Go to Network tab
3. Reload page
4. Click on `get_executive_summary.php` request
5. Check:
   - Status code (should be 200)
   - Response (should be JSON)
   - Headers (Content-Type should be application/json)

---

## Contact Information

If you've tried all these steps and it's still not working:

1. Check the error log file
2. Note the exact error message
3. Check browser console errors
4. Check Network tab response

The error messages will tell you exactly what's wrong!
