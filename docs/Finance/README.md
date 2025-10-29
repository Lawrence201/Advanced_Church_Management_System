# Finance Module - Database Integration

This Finance module now pulls data dynamically from the database.

## Files Created

1. **config.php** - Database configuration file
2. **get_finance_data.php** - PHP API to fetch financial data from database
3. **finance_data_loader.js** - JavaScript file that loads data and updates the page

## How It Works

### Data Flow
1. When the page loads, `finance_data_loader.js` makes AJAX requests to `get_finance_data.php`
2. The PHP file queries the database and returns JSON data
3. JavaScript updates the HTML elements with the real data

### API Endpoints

The `get_finance_data.php` accepts two parameters:
- `type`: The type of data to fetch
- `range`: The date range (today, week, month, quarter, year)

#### Available Types:
- `stats` - Financial statistics for dashboard cards
- `overview` - Overview data (totals)
- `offerings` - Offerings list and summary
- `project_offerings` - Project offerings list and summary
- `tithes` - Tithes/CPS list and summary
- `welfare` - Welfare contributions list and summary
- `expenses` - Expenses list and summary
- `recent_transactions` - Recent transactions from all tables

#### Example API Calls:
```
get_finance_data.php?type=stats
get_finance_data.php?type=offerings&range=month
get_finance_data.php?type=expenses&range=week
```

## Features

✅ **Dynamic Data Loading**
- All financial data is fetched from the database in real-time
- No more hardcoded values

✅ **Financial Statistics Cards**
- Shows total offerings, tithes, project offerings, welfare, and expenses
- Displays percentage change from previous month

✅ **Recent Transactions**
- Shows the 10 most recent transactions across all types
- Includes offerings, tithes, project offerings, welfare, and expenses

✅ **Offerings Management**
- Lists all offerings with details
- Filter by date range
- Search functionality

✅ **Project Offerings Management**
- Lists all project offerings
- Filter by date range
- Shows project names

✅ **Tithes (CPS) Management**
- Lists all tithes with member information
- Shows member names, emails, amounts
- Summary statistics

✅ **Welfare Tracking**
- Lists all welfare contributions
- Member information included
- Summary statistics

✅ **Expenses Management**
- Lists all expenses with full details
- Filter by category and status
- Approve/reject pending expenses
- Category breakdown

## Database Tables Used

- `offerings` - General church offerings
- `project_offerings` - Project-specific offerings
- `tithes` - Tithe payments (links to members)
- `welfare_contributions` - Welfare dues (links to members)
- `expenses` - Church expenses
- `members` - Member information (for tithes and welfare)

## Setup Instructions

1. **Database Setup**
   - Make sure all tables exist in your `church_management_system` database
   - Tables should match the structure used in the Record_Donations module

2. **File Placement**
   - All PHP and JS files are in: `admin_dashboard/Finance/`
   - Make sure they're in the same directory as `finance.html`

3. **Configuration**
   - Update `config.php` if your database credentials are different:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_USER', 'root');
     define('DB_PASS', '');
     define('DB_NAME', 'church_management_system');
     ```

4. **Testing**
   - Open `http://localhost/Church_Management_System/admin_dashboard/Finance/finance.html`
   - You should see data from your database
   - Check browser console (F12) for any errors

## Troubleshooting

### No data showing?
1. Check if database tables exist and have data
2. Open browser console (F12) and check for errors
3. Test API directly: `http://localhost/.../Finance/get_finance_data.php?type=stats`
4. Make sure XAMPP Apache and MySQL are running

### Wrong data appearing?
- Check date ranges - data is filtered by date
- Verify database table structures match expected format

### JavaScript errors?
- Make sure `finance_data_loader.js` is in the same folder as `finance.html`
- Check browser console for specific error messages

## Adding Test Data

To test with sample data, you can:
1. Use the Record Donations form to add data
2. Or manually insert test data via phpMyAdmin

Example SQL for quick test data:
```sql
-- Test offering
INSERT INTO offerings (transaction_id, date, service_type, service_time, amount_collected, collection_method, counted_by, status) 
VALUES ('OFF20251022001', CURDATE(), 'Sunday Worship', '09:00:00', 1500.00, 'Cash', 'Deacon John', 'Verified');

-- Test expense
INSERT INTO expenses (transaction_id, date, category, description, vendor_payee, amount, payment_method, status) 
VALUES ('EXP20251022001', CURDATE(), 'utilities', 'Electricity Bill', 'ECG', 850.00, 'Bank Transfer', 'approved');
```

## Next Steps

- Add date range pickers for custom date filtering
- Implement export functionality (CSV/Excel/PDF)
- Add charts with real database data
- Implement print functionality
- Add expense approval workflow via AJAX
