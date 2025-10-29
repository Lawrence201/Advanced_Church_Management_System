-- Budget Settings Table
-- This table stores monthly budget configurations for different categories

CREATE TABLE IF NOT EXISTS budget_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_name VARCHAR(100) NOT NULL UNIQUE,
    setting_value DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default monthly budget
INSERT INTO budget_settings (setting_name, setting_value, description) 
VALUES ('monthly_budget', 50000.00, 'Total monthly budget for church expenses')
ON DUPLICATE KEY UPDATE setting_value = setting_value;

-- Optional: Add category-specific budgets
INSERT INTO budget_settings (setting_name, setting_value, description) 
VALUES 
    ('budget_utilities', 10000.00, 'Monthly budget for utilities'),
    ('budget_maintenance', 8000.00, 'Monthly budget for maintenance'),
    ('budget_salaries', 20000.00, 'Monthly budget for salaries'),
    ('budget_supplies', 5000.00, 'Monthly budget for supplies'),
    ('budget_events', 5000.00, 'Monthly budget for events'),
    ('budget_transportation', 2000.00, 'Monthly budget for transportation')
ON DUPLICATE KEY UPDATE setting_value = setting_value;

-- View to easily check budget settings
CREATE OR REPLACE VIEW v_budget_summary AS
SELECT 
    setting_name,
    setting_value as budget_amount,
    description,
    updated_at as last_updated
FROM budget_settings
WHERE setting_name LIKE 'budget_%' OR setting_name = 'monthly_budget'
ORDER BY setting_name;
