# Budget Management System

## Overview
The Finance module now includes a comprehensive budget management system that allows you to set and track monthly budgets for church expenses.

## Setup Instructions

### 1. Create the Database Table
Run the SQL script to create the budget settings table:

```bash
mysql -u root -p church_management_system < budget_settings_schema.sql
```

Or manually execute the SQL in phpMyAdmin:
- Navigate to: `http://localhost/phpmyadmin`
- Select your database: `church_management_system`
- Go to the SQL tab
- Copy and paste the contents of `budget_settings_schema.sql`
- Click "Go" to execute

### 2. Verify Installation
The script will create:
- `budget_settings` table - Stores budget configurations
- Default monthly budget of ₵50,000.00
- Optional category-specific budgets

## How to Use

### Setting the Monthly Budget

1. **Navigate to Finance Page**
   - Go to Finances → Expenses tab

2. **Open Budget Settings**
   - Look for the "Budget Remaining" card
   - Click the "Set Budget" button in the top-right corner

3. **Enter New Budget**
   - Enter your desired monthly budget amount
   - Click "Save Budget"
   - The system will update immediately

### Budget Display Features

The Budget Remaining card shows:
- **Total Budget**: Your configured monthly budget
- **Remaining Budget**: Budget minus current month's expenses
- **Progress Bar**: Visual indicator of budget usage
- **Percentage**: How much of the budget has been used

### Budget Tracking

The system automatically:
- ✅ Calculates total expenses for the current month
- ✅ Subtracts expenses from your budget
- ✅ Updates the progress bar in real-time
- ✅ Shows remaining budget amount
- ✅ Works with date range filters

## API Endpoints

### Get Monthly Budget
```javascript
GET budget_settings_api.php?action=get_monthly

Response:
{
  "success": true,
  "data": {
    "monthly_budget": 50000.00
  }
}
```

### Update Monthly Budget
```javascript
POST budget_settings_api.php?action=update_monthly
Content-Type: application/json

Body:
{
  "amount": 75000.00
}

Response:
{
  "success": true,
  "message": "Monthly budget updated successfully",
  "data": {
    "monthly_budget": 75000.00
  }
}
```

### Get All Budget Settings
```javascript
GET budget_settings_api.php?action=get

Response:
{
  "success": true,
  "data": {
    "monthly_budget": {
      "value": 50000.00,
      "description": "Total monthly budget for church expenses",
      "updated_at": "2025-01-23 12:00:00"
    },
    "budget_utilities": {
      "value": 10000.00,
      "description": "Monthly budget for utilities",
      "updated_at": "2025-01-23 12:00:00"
    }
    // ... other category budgets
  }
}
```

## Database Schema

```sql
CREATE TABLE budget_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_name VARCHAR(100) NOT NULL UNIQUE,
    setting_value DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

## Default Budget Categories

The system includes default budgets for:
- **Monthly Budget**: ₵50,000.00 (Total)
- **Utilities**: ₵10,000.00
- **Maintenance**: ₵8,000.00
- **Salaries**: ₵20,000.00
- **Supplies**: ₵5,000.00
- **Events**: ₵5,000.00
- **Transportation**: ₵2,000.00

## Customization

### Change Default Budget
Edit line 15 in `budget_settings_schema.sql`:
```sql
VALUES ('monthly_budget', 75000.00, 'Total monthly budget for church expenses')
```

### Add Custom Categories
Add new budget categories to the INSERT statement:
```sql
INSERT INTO budget_settings (setting_name, setting_value, description) 
VALUES ('budget_security', 3000.00, 'Monthly budget for security services');
```

## Troubleshooting

### Budget not updating?
1. Check browser console for errors
2. Verify `budget_settings_api.php` has correct database credentials
3. Ensure the `budget_settings` table exists
4. Check that `config.php` is properly configured

### Budget shows ₵0.00?
1. Run the SQL schema to create default budget
2. Manually set budget using the "Set Budget" button
3. Check browser console for API errors

### Progress bar not showing?
1. Ensure expenses are loaded successfully
2. Check that budget value is greater than 0
3. Verify JavaScript is not throwing errors

## Future Enhancements

Potential features to add:
- [ ] Category-specific budget tracking
- [ ] Budget vs. Actual reports
- [ ] Budget alerts when approaching limit
- [ ] Year-over-year budget comparison
- [ ] Automated budget rollover
- [ ] Budget approval workflow

## Support

For issues or questions:
1. Check the browser console for errors
2. Verify database connection
3. Ensure all files are in the correct directory
4. Check that SQL schema was executed successfully
