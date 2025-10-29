// Advanced Financial Analytics for Reports
// Ultra-professional financial intelligence system with predictive analytics

const FINANCE_API_URL = '../Finance/get_finance_data.php';

// Chart instances - expanded set
let financeOverviewChart = null;
let monthlyTrendsChart = null;
let categoryBreakdownChart = null;
let expensesCategoryChart = null;
let cashflowChart = null;
let forecastChart = null;
let budgetGaugeChart = null;
let donorRetentionChart = null;
let yoyComparisonChart = null;
let quarterlyHeatmapChart = null;
let expenseRatioChart = null;

// Auto-refresh interval
let financeRefreshInterval = null;

// Historical data cache for forecasting
let historicalData = {
    months: [],
    income: [],
    expenses: [],
    offerings: [],
    tithes: []
};

// Format currency
function formatCurrency(amount) {
    return '₵' + parseFloat(amount || 0).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}

// Format large numbers
function formatLargeNumber(num) {
    if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
    if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
    return num.toFixed(0);
}

// Calculate percentage change
function calculatePercentChange(current, previous) {
    if (!previous || previous === 0) return 0;
    return (((current - previous) / previous) * 100).toFixed(1);
}

// Calculate financial health score (0-100)
function calculateHealthScore(income, expenses, growth) {
    const profitMargin = income > 0 ? ((income - expenses) / income) * 100 : 0;
    const growthScore = Math.min(Math.max(growth, -20), 20) + 20; // -20 to +20 becomes 0 to 40
    const sustainabilityScore = profitMargin > 20 ? 40 : profitMargin > 10 ? 30 : profitMargin > 0 ? 20 : 10;

    return Math.min(Math.round(growthScore + sustainabilityScore), 100);
}

// Render empty state chart with message
function renderEmptyChart(ctx, chartInstance, message) {
    if (chartInstance) chartInstance.destroy();

    const canvas = ctx;
    const context = canvas.getContext('2d');

    // Clear canvas
    context.clearRect(0, 0, canvas.width, canvas.height);

    // Set text styles
    context.font = '14px Arial';
    context.fillStyle = '#94a3b8';
    context.textAlign = 'center';
    context.textBaseline = 'middle';

    // Draw message
    const lines = message.split('. ');
    const lineHeight = 24;
    const startY = canvas.height / 2 - (lines.length * lineHeight) / 2;

    lines.forEach((line, index) => {
        context.fillText(line, canvas.width / 2, startY + (index * lineHeight));
    });
}

// Simple moving average for forecasting
function calculateMovingAverage(data, periods = 3) {
    const result = [];
    for (let i = 0; i < data.length; i++) {
        if (i < periods - 1) {
            result.push(data[i]);
        } else {
            const sum = data.slice(i - periods + 1, i + 1).reduce((a, b) => a + b, 0);
            result.push(sum / periods);
        }
    }
    return result;
}

// Linear regression forecast
function forecastLinearRegression(data, periods = 3) {
    const n = data.length;
    let sumX = 0, sumY = 0, sumXY = 0, sumXX = 0;

    for (let i = 0; i < n; i++) {
        sumX += i;
        sumY += data[i];
        sumXY += i * data[i];
        sumXX += i * i;
    }

    const slope = (n * sumXY - sumX * sumY) / (n * sumXX - sumX * sumX);
    const intercept = (sumY - slope * sumX) / n;

    const forecast = [];
    for (let i = 0; i < periods; i++) {
        forecast.push(Math.max(0, slope * (n + i) + intercept));
    }

    return forecast;
}

// Load advanced financial summary with YoY comparison
async function loadAdvancedFinancialSummary() {
    try {
        console.log('Loading advanced financial summary...');

        // Fetch current year and last year data in parallel
        const [
            currentOfferings, currentTithes, currentProject, currentWelfare, currentExpenses,
            lastYearOfferings, lastYearTithes, lastYearProject, lastYearWelfare, lastYearExpenses
        ] = await Promise.all([
            // Current year
            fetch(`${FINANCE_API_URL}?type=offerings&range=year`).then(r => r.json()),
            fetch(`${FINANCE_API_URL}?type=tithes&range=year`).then(r => r.json()),
            fetch(`${FINANCE_API_URL}?type=project_offerings&range=year`).then(r => r.json()),
            fetch(`${FINANCE_API_URL}?type=welfare&range=year`).then(r => r.json()),
            fetch(`${FINANCE_API_URL}?type=expenses&range=year`).then(r => r.json()),
            // Last year - actual last year data
            fetch(`${FINANCE_API_URL}?type=offerings&range=last_year`).then(r => r.json()),
            fetch(`${FINANCE_API_URL}?type=tithes&range=last_year`).then(r => r.json()),
            fetch(`${FINANCE_API_URL}?type=project_offerings&range=last_year`).then(r => r.json()),
            fetch(`${FINANCE_API_URL}?type=welfare&range=last_year`).then(r => r.json()),
            fetch(`${FINANCE_API_URL}?type=expenses&range=last_year`).then(r => r.json())
        ]);

        // Calculate totals
        const currentIncome =
            parseFloat(currentOfferings.data?.summary?.total_amount || 0) +
            parseFloat(currentTithes.data?.summary?.total_amount || 0) +
            parseFloat(currentProject.data?.summary?.total_amount || 0) +
            parseFloat(currentWelfare.data?.summary?.total_amount || 0);

        const lastYearIncome =
            parseFloat(lastYearOfferings.data?.summary?.total_amount || 0) +
            parseFloat(lastYearTithes.data?.summary?.total_amount || 0) +
            parseFloat(lastYearProject.data?.summary?.total_amount || 0) +
            parseFloat(lastYearWelfare.data?.summary?.total_amount || 0);

        const currentExpensesTotal = parseFloat(currentExpenses.data?.summary?.total_amount || 0);
        const lastYearExpensesTotal = parseFloat(lastYearExpenses.data?.summary?.total_amount || 0);

        const netIncome = currentIncome - currentExpensesTotal;
        const yoyIncomeGrowth = calculatePercentChange(currentIncome, lastYearIncome);
        const yoyExpenseGrowth = calculatePercentChange(currentExpensesTotal, lastYearExpensesTotal);

        // Calculate health score
        const healthScore = calculateHealthScore(currentIncome, currentExpensesTotal, parseFloat(yoyIncomeGrowth));

        // Update main summary cards
        document.getElementById('total-income').textContent = formatCurrency(currentIncome);
        document.getElementById('total-tithes').textContent = formatCurrency(currentTithes.data?.summary?.total_amount || 0);
        document.getElementById('total-expenses').textContent = formatCurrency(currentExpensesTotal);
        document.getElementById('net-income').textContent = formatCurrency(netIncome);

        // Update YoY cards
        document.getElementById('yoy-income-growth').textContent = yoyIncomeGrowth + '%';
        document.getElementById('yoy-expense-growth').textContent = yoyExpenseGrowth + '%';
        document.getElementById('financial-health-score').textContent = healthScore;
        document.getElementById('expense-ratio').textContent = currentIncome > 0 ? ((currentExpensesTotal / currentIncome) * 100).toFixed(1) + '%' : '0%';

        // Update health score color
        const healthScoreEl = document.getElementById('financial-health-score');
        if (healthScore >= 80) {
            healthScoreEl.style.color = '#10b981';
        } else if (healthScore >= 60) {
            healthScoreEl.style.color = '#f59e0b';
        } else {
            healthScoreEl.style.color = '#ef4444';
        }

        // Update change indicators
        const incomeChange = document.getElementById('income-change');
        if (netIncome > 0) {
            incomeChange.textContent = `+${formatCurrency(netIncome)} surplus`;
            incomeChange.className = 'sum-chan';
        } else {
            incomeChange.textContent = `${formatCurrency(Math.abs(netIncome))} deficit`;
            incomeChange.className = 'sum-chan nega';
        }

        // Update YoY indicators
        const yoyIncomeEl = document.getElementById('yoy-income-indicator');
        const yoyExpenseEl = document.getElementById('yoy-expense-indicator');

        if (parseFloat(yoyIncomeGrowth) > 0) {
            yoyIncomeEl.className = 'tre-ind tre-up';
            yoyIncomeEl.textContent = `↑ ${yoyIncomeGrowth}%`;
        } else {
            yoyIncomeEl.className = 'tre-ind tre-down';
            yoyIncomeEl.textContent = `↓ ${Math.abs(yoyIncomeGrowth)}%`;
        }

        if (parseFloat(yoyExpenseGrowth) > 0) {
            yoyExpenseEl.className = 'tre-ind tre-down';
            yoyExpenseEl.textContent = `↑ ${yoyExpenseGrowth}%`;
        } else {
            yoyExpenseEl.className = 'tre-ind tre-up';
            yoyExpenseEl.textContent = `↓ ${Math.abs(yoyExpenseGrowth)}%`;
        }

        return {
            currentIncome,
            currentExpensesTotal,
            offerings: currentOfferings.data?.summary?.total_amount || 0,
            tithes: currentTithes.data?.summary?.total_amount || 0,
            project: currentProject.data?.summary?.total_amount || 0,
            welfare: currentWelfare.data?.summary?.total_amount || 0,
            expenses: currentExpensesTotal,
            healthScore,
            yoyIncomeGrowth,
            yoyExpenseGrowth
        };

    } catch (error) {
        console.error('Error loading advanced financial summary:', error);
        return null;
    }
}

// Load top contributors/donors
async function loadTopContributors() {
    try {
        console.log('Loading top contributors...');

        const response = await fetch(`${FINANCE_API_URL}?type=tithes&range=year`);
        const result = await response.json();

        const tbody = document.getElementById('top-contributors-tbody');
        if (!tbody) return;

        // Group by member and sum amounts
        const memberTotals = {};
        const tithes = result.data?.tithes || [];

        tithes.forEach(tithe => {
            const memberName = tithe.member_name || 'Anonymous';
            const amount = parseFloat(tithe.amount || 0);

            if (memberTotals[memberName]) {
                memberTotals[memberName] += amount;
            } else {
                memberTotals[memberName] = amount;
            }
        });

        // Convert to array and sort
        const topContributors = Object.entries(memberTotals)
            .map(([name, amount]) => ({ name, amount }))
            .sort((a, b) => b.amount - a.amount)
            .slice(0, 10);

        // Clear table
        tbody.innerHTML = '';

        // Populate table
        topContributors.forEach((contributor, index) => {
            const row = document.createElement('tr');
            const rank = index + 1;
            let badge = '';

            if (rank === 1) badge = '<span class="rank-badge gold">1st</span>';
            else if (rank === 2) badge = '<span class="rank-badge silver">2nd</span>';
            else if (rank === 3) badge = '<span class="rank-badge bronze">3rd</span>';
            else badge = `<span class="rank-badge">${rank}th</span>`;

            row.innerHTML = `
                <td>${badge}</td>
                <td class="tab-nam">${contributor.name}</td>
                <td><strong>${formatCurrency(contributor.amount)}</strong></td>
                <td><span class="badg badg-succ">Active</span></td>
            `;
            tbody.appendChild(row);
        });

        if (topContributors.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" style="text-align: center; padding: 20px; color: #64748b;">No contributor data available. Start recording tithes to see top contributors.</td></tr>';
        }

    } catch (error) {
        console.error('Error loading top contributors:', error);
    }
}

// Render cashflow analysis chart
async function renderCashflowChart() {
    try {
        console.log('Loading cashflow chart...');

        const response = await fetch(`${FINANCE_API_URL}?type=financial_trends&months=12`);
        const result = await response.json();

        const ctx = document.getElementById('cashflowChart');
        if (!ctx) return;

        if (cashflowChart) cashflowChart.destroy();

        const trendsData = result.data || [];

        // Check if we have data
        if (trendsData.length === 0) {
            renderEmptyChart(ctx, cashflowChart, 'No cashflow data available. Start adding financial records to see analysis.');
            return;
        }

        const months = trendsData.map(d => d.month || 'N/A');
        const operating = trendsData.map(d => parseFloat(d.income || 0) - parseFloat(d.expenses || 0));

        cashflowChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: months,
                datasets: [
                    {
                        label: 'Operating Activities (Income - Expenses)',
                        data: operating,
                        backgroundColor: '#10b981',
                        borderRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { padding: 15, font: { size: 11 } } },
                    tooltip: {
                        callbacks: {
                            label: (context) => `${context.dataset.label}: ${formatCurrency(context.parsed.y)}`
                        }
                    }
                },
                scales: {
                    x: { stacked: false, grid: { display: false } },
                    y: {
                        stacked: false,
                        ticks: { callback: (value) => formatCurrency(value) },
                        grid: { color: '#e5e7eb' }
                    }
                }
            }
        });

    } catch (error) {
        console.error('Error rendering cashflow chart:', error);
    }
}

// Render predictive forecast chart with ML-inspired forecasting
async function renderForecastChart() {
    try {
        console.log('Loading forecast chart...');

        const response = await fetch(`${FINANCE_API_URL}?type=financial_trends&months=12`);
        const result = await response.json();

        const ctx = document.getElementById('forecastChart');
        if (!ctx) return;

        if (forecastChart) forecastChart.destroy();

        const trendsData = result.data || [];

        // Check if we have sufficient data for forecasting (need at least 3 months)
        if (trendsData.length < 3) {
            renderEmptyChart(ctx, forecastChart, 'Insufficient data for forecasting. Need at least 3 months of financial records.');
            return;
        }

        const months = trendsData.map(d => d.month || 'N/A');
        const actualIncome = trendsData.map(d => parseFloat(d.income || 0));
        const actualExpenses = trendsData.map(d => parseFloat(d.expenses || 0));

        // Generate forecast for next 3 months
        const incomeForecast = forecastLinearRegression(actualIncome, 3);
        const expensesForecast = forecastLinearRegression(actualExpenses, 3);

        const futureMonths = ['Jan+1', 'Jan+2', 'Jan+3']; // Placeholder labels

        forecastChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [...months, ...futureMonths],
                datasets: [
                    {
                        label: 'Actual Income',
                        data: [...actualIncome, ...Array(3).fill(null)],
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true,
                        pointRadius: 4
                    },
                    {
                        label: 'Forecasted Income',
                        data: [...Array(months.length).fill(null), ...incomeForecast],
                        borderColor: '#10b981',
                        borderDash: [5, 5],
                        backgroundColor: 'rgba(16, 185, 129, 0.05)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true,
                        pointRadius: 3,
                        pointStyle: 'rect'
                    },
                    {
                        label: 'Actual Expenses',
                        data: [...actualExpenses, ...Array(3).fill(null)],
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true,
                        pointRadius: 4
                    },
                    {
                        label: 'Forecasted Expenses',
                        data: [...Array(months.length).fill(null), ...expensesForecast],
                        borderColor: '#ef4444',
                        borderDash: [5, 5],
                        backgroundColor: 'rgba(239, 68, 68, 0.05)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true,
                        pointRadius: 3,
                        pointStyle: 'rect'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { padding: 15, font: { size: 11 } } },
                    tooltip: {
                        callbacks: {
                            label: (context) => {
                                if (context.parsed.y === null) return '';
                                return `${context.dataset.label}: ${formatCurrency(context.parsed.y)}`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { callback: (value) => formatCurrency(value) },
                        grid: { color: '#e5e7eb' }
                    },
                    x: { grid: { display: false } }
                }
            }
        });

    } catch (error) {
        console.error('Error rendering forecast chart:', error);
    }
}

// Render budget utilization gauge chart
async function renderBudgetGaugeChart() {
    try {
        console.log('Loading budget gauge chart...');

        const ctx = document.getElementById('budgetGaugeChart');
        if (!ctx) return;

        if (budgetGaugeChart) budgetGaugeChart.destroy();

        // Fetch actual budget and expenses data
        const [budgetResponse, expensesResponse] = await Promise.all([
            fetch(`${FINANCE_API_URL}?type=budget&range=year`),
            fetch(`${FINANCE_API_URL}?type=expenses&range=year`)
        ]);

        const budgetResult = await budgetResponse.json();
        const expensesResult = await expensesResponse.json();

        const budgetTotal = parseFloat(budgetResult.data?.summary?.total_budget || 0);
        const actualExpenses = parseFloat(expensesResult.data?.summary?.total_amount || 0);

        // Check if we have budget data
        if (budgetTotal === 0) {
            renderEmptyChart(ctx, budgetGaugeChart, 'No budget set. Configure your budget in Finance settings.');
            return;
        }

        const utilization = (actualExpenses / budgetTotal) * 100;

        budgetGaugeChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Used', 'Remaining'],
                datasets: [{
                    data: [actualExpenses, budgetTotal - actualExpenses],
                    backgroundColor: [
                        utilization > 90 ? '#ef4444' : utilization > 75 ? '#f59e0b' : '#10b981',
                        '#e5e7eb'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                circumference: 180,
                rotation: 270,
                cutout: '75%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (context) => `${context.label}: ${formatCurrency(context.parsed)} (${((context.parsed / budgetTotal) * 100).toFixed(1)}%)`
                        }
                    }
                }
            },
            plugins: [{
                id: 'centerText',
                beforeDraw: (chart) => {
                    const { ctx, chartArea: { width, height } } = chart;
                    ctx.save();
                    ctx.font = 'bold 24px Arial';
                    ctx.fillStyle = utilization > 90 ? '#ef4444' : utilization > 75 ? '#f59e0b' : '#10b981';
                    ctx.textAlign = 'center';
                    ctx.fillText(`${utilization.toFixed(1)}%`, width / 2, height / 2 + 40);
                    ctx.font = '12px Arial';
                    ctx.fillStyle = '#64748b';
                    ctx.fillText('Budget Used', width / 2, height / 2 + 60);
                    ctx.restore();
                }
            }]
        });

    } catch (error) {
        console.error('Error rendering budget gauge chart:', error);
    }
}

// Render donor retention chart
async function renderDonorRetentionChart() {
    try {
        console.log('Loading donor retention chart...');

        const ctx = document.getElementById('donorRetentionChart');
        if (!ctx) return;

        if (donorRetentionChart) donorRetentionChart.destroy();

        // Fetch contributor retention data from tithes
        const response = await fetch(`${FINANCE_API_URL}?type=donor_retention&months=6`);
        const result = await response.json();

        const retentionData = result.data || [];

        // Check if we have data
        if (retentionData.length === 0) {
            renderEmptyChart(ctx, donorRetentionChart, 'No contributor data available. Start tracking member contributions.');
            return;
        }

        const months = retentionData.map(d => d.month || 'N/A');
        const newDonors = retentionData.map(d => parseInt(d.new_donors || 0));
        const returningDonors = retentionData.map(d => parseInt(d.returning_donors || 0));
        const lapsedDonors = retentionData.map(d => parseInt(d.lapsed_donors || 0));

        donorRetentionChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: months,
                datasets: [
                    {
                        label: 'New Donors',
                        data: newDonors,
                        backgroundColor: '#10b981',
                        borderRadius: 6
                    },
                    {
                        label: 'Returning Donors',
                        data: returningDonors,
                        backgroundColor: '#3b82f6',
                        borderRadius: 6
                    },
                    {
                        label: 'Lapsed Donors',
                        data: lapsedDonors,
                        backgroundColor: '#ef4444',
                        borderRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { padding: 15, font: { size: 11 } } }
                },
                scales: {
                    x: { stacked: true, grid: { display: false } },
                    y: { stacked: true, beginAtZero: true, grid: { color: '#e5e7eb' } }
                }
            }
        });

    } catch (error) {
        console.error('Error rendering donor retention chart:', error);
    }
}

// Render YoY comparison chart
async function renderYoYComparisonChart() {
    try {
        console.log('Loading YoY comparison chart...');

        const ctx = document.getElementById('yoyComparisonChart');
        if (!ctx) return;

        if (yoyComparisonChart) yoyComparisonChart.destroy();

        // Fetch this year and last year data
        const [offerings, tithes, projects, welfare, expenses, offeringsLY, tithesLY, projectsLY, welfareLY, expensesLY] = await Promise.all([
            // This year
            fetch(`${FINANCE_API_URL}?type=offerings&range=year`).then(r => r.json()),
            fetch(`${FINANCE_API_URL}?type=tithes&range=year`).then(r => r.json()),
            fetch(`${FINANCE_API_URL}?type=project_offerings&range=year`).then(r => r.json()),
            fetch(`${FINANCE_API_URL}?type=welfare&range=year`).then(r => r.json()),
            fetch(`${FINANCE_API_URL}?type=expenses&range=year`).then(r => r.json()),
            // Last year
            fetch(`${FINANCE_API_URL}?type=offerings&range=last_year`).then(r => r.json()),
            fetch(`${FINANCE_API_URL}?type=tithes&range=last_year`).then(r => r.json()),
            fetch(`${FINANCE_API_URL}?type=project_offerings&range=last_year`).then(r => r.json()),
            fetch(`${FINANCE_API_URL}?type=welfare&range=last_year`).then(r => r.json()),
            fetch(`${FINANCE_API_URL}?type=expenses&range=last_year`).then(r => r.json())
        ]);

        const categories = ['Offerings', 'Tithes', 'Projects', 'Welfare', 'Expenses'];
        const thisYear = [
            parseFloat(offerings.data?.summary?.total_amount || 0),
            parseFloat(tithes.data?.summary?.total_amount || 0),
            parseFloat(projects.data?.summary?.total_amount || 0),
            parseFloat(welfare.data?.summary?.total_amount || 0),
            parseFloat(expenses.data?.summary?.total_amount || 0)
        ];
        const lastYear = [
            parseFloat(offeringsLY.data?.summary?.total_amount || 0),
            parseFloat(tithesLY.data?.summary?.total_amount || 0),
            parseFloat(projectsLY.data?.summary?.total_amount || 0),
            parseFloat(welfareLY.data?.summary?.total_amount || 0),
            parseFloat(expensesLY.data?.summary?.total_amount || 0)
        ];

        // Check if we have any data
        const hasData = thisYear.some(val => val > 0) || lastYear.some(val => val > 0);
        if (!hasData) {
            renderEmptyChart(ctx, yoyComparisonChart, 'No year-over-year data available. Start adding financial records.');
            return;
        }

        yoyComparisonChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: categories,
                datasets: [
                    {
                        label: 'This Year',
                        data: thisYear,
                        backgroundColor: '#3b82f6',
                        borderRadius: 6
                    },
                    {
                        label: 'Last Year',
                        data: lastYear,
                        backgroundColor: '#94a3b8',
                        borderRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { padding: 15, font: { size: 11 } } },
                    tooltip: {
                        callbacks: {
                            label: (context) => `${context.dataset.label}: ${formatCurrency(context.parsed.y)}`
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { callback: (value) => formatCurrency(value) },
                        grid: { color: '#e5e7eb' }
                    },
                    x: { grid: { display: false } }
                }
            }
        });

    } catch (error) {
        console.error('Error rendering YoY comparison chart:', error);
    }
}

// Render expense ratio doughnut chart
async function renderExpenseRatioChart() {
    try {
        console.log('Loading expense ratio chart...');

        const response = await fetch(`${FINANCE_API_URL}?type=expenses&range=month`);
        const result = await response.json();

        const ctx = document.getElementById('expenseRatioChart');
        if (!ctx) return;

        if (expenseRatioChart) expenseRatioChart.destroy();

        // Group expenses by category
        const expenses = result.data?.expenses || [];
        const categoryTotals = {};

        expenses.forEach(expense => {
            const category = expense.category || 'Other';
            categoryTotals[category] = (categoryTotals[category] || 0) + parseFloat(expense.amount || 0);
        });

        const categories = Object.keys(categoryTotals);
        const amounts = Object.values(categoryTotals);

        if (categories.length === 0) {
            renderEmptyChart(ctx, expenseRatioChart, 'No expense data available. Start recording expenses to see distribution.');
            return;
        }

        expenseRatioChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: categories,
                datasets: [{
                    data: amounts,
                    backgroundColor: [
                        '#3b82f6', '#10b981', '#f59e0b', '#8b5cf6',
                        '#ec4899', '#14b8a6', '#f97316', '#06b6d4'
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: { padding: 12, font: { size: 11 } }
                    },
                    tooltip: {
                        callbacks: {
                            label: (context) => {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? ((context.parsed / total) * 100).toFixed(1) : 0;
                                return `${context.label}: ${formatCurrency(context.parsed)} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });

    } catch (error) {
        console.error('Error rendering expense ratio chart:', error);
    }
}

// Initialize all advanced financial analytics
async function initializeAdvancedFinanceAnalytics() {
    console.log('=== Initializing Advanced Finance Analytics ===');

    try {
        await loadAdvancedFinancialSummary();
        await loadTopContributors();

        // Render all charts
        await Promise.all([
            renderCashflowChart(),
            renderForecastChart(),
            renderBudgetGaugeChart(),
            renderDonorRetentionChart(),
            renderYoYComparisonChart(),
            renderExpenseRatioChart()
        ]);

        console.log('Advanced finance analytics loaded successfully');
    } catch (error) {
        console.error('Error initializing advanced analytics:', error);
    }
}

// Start auto-refresh
function startAdvancedFinanceAutoRefresh() {
    stopAdvancedFinanceAutoRefresh();

    initializeAdvancedFinanceAnalytics();

    financeRefreshInterval = setInterval(() => {
        console.log('[Auto-refresh] Updating advanced finance analytics...');
        initializeAdvancedFinanceAnalytics();
    }, 30000);

    console.log('Auto-refresh started for advanced finance analytics (30s interval)');
}

// Stop auto-refresh
function stopAdvancedFinanceAutoRefresh() {
    if (financeRefreshInterval) {
        clearInterval(financeRefreshInterval);
        financeRefreshInterval = null;
        console.log('Auto-refresh stopped for advanced finance analytics');
    }
}

// Pause/resume on tab visibility change
document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        stopAdvancedFinanceAutoRefresh();
    } else {
        const financialTab = document.getElementById('financial');
        if (financialTab && financialTab.classList.contains('acti')) {
            startAdvancedFinanceAutoRefresh();
        }
    }
});

// Export PDF report with actual implementation
async function exportFinancePDF() {
    try {
        // Get jsPDF from window
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();

        // Add header
        doc.setFontSize(20);
        doc.setTextColor(66, 126, 234);
        doc.text('Financial Analytics Report', 20, 20);

        doc.setFontSize(10);
        doc.setTextColor(100, 116, 139);
        doc.text(`Generated on: ${new Date().toLocaleDateString()}`, 20, 28);

        // Add summary section
        doc.setFontSize(14);
        doc.setTextColor(0, 0, 0);
        doc.text('Executive Summary', 20, 40);

        doc.setFontSize(10);
        const summaryData = [
            `Total Income: ${document.getElementById('total-income')?.textContent || 'N/A'}`,
            `Total Tithes: ${document.getElementById('total-tithes')?.textContent || 'N/A'}`,
            `Total Expenses: ${document.getElementById('total-expenses')?.textContent || 'N/A'}`,
            `Net Income: ${document.getElementById('net-income')?.textContent || 'N/A'}`,
            '',
            `YoY Income Growth: ${document.getElementById('yoy-income-growth')?.textContent || 'N/A'}`,
            `Financial Health Score: ${document.getElementById('financial-health-score')?.textContent || 'N/A'}/100`,
            `Expense Ratio: ${document.getElementById('expense-ratio')?.textContent || 'N/A'}`
        ];

        let yPos = 48;
        summaryData.forEach(line => {
            doc.text(line, 20, yPos);
            yPos += 6;
        });

        // Add top contributors section
        doc.setFontSize(14);
        doc.setTextColor(0, 0, 0);
        doc.text('Top 5 Contributors', 20, yPos + 10);

        yPos += 18;
        doc.setFontSize(10);
        const contributorsTable = document.getElementById('top-contributors-tbody');
        if (contributorsTable) {
            const rows = contributorsTable.querySelectorAll('tr');
            let count = 0;
            rows.forEach(row => {
                if (count < 5) {
                    const cells = row.querySelectorAll('td');
                    if (cells.length >= 3) {
                        const rank = cells[0].textContent.trim();
                        const name = cells[1].textContent.trim();
                        const amount = cells[2].textContent.trim();
                        doc.text(`${rank}: ${name} - ${amount}`, 20, yPos);
                        yPos += 6;
                        count++;
                    }
                }
            });
        }

        // Add footer
        doc.setFontSize(8);
        doc.setTextColor(100, 116, 139);
        doc.text('Church Management System - Confidential Financial Report', 20, 280);

        // Save PDF
        doc.save(`Financial_Report_${new Date().toISOString().split('T')[0]}.pdf`);

        // Show success message
        alert('✅ PDF Report Generated Successfully!\n\nThe report has been downloaded to your device.');

    } catch (error) {
        console.error('Error generating PDF:', error);
        alert('❌ Error generating PDF report. Please try again.');
    }
}

// Export Excel report with actual implementation
async function exportFinanceExcel() {
    try {
        // Prepare data for Excel
        const workbook = XLSX.utils.book_new();

        // Sheet 1: Summary
        const summaryData = [
            ['Financial Summary Report'],
            ['Generated on:', new Date().toLocaleDateString()],
            [],
            ['Metric', 'Value'],
            ['Total Income', document.getElementById('total-income')?.textContent || 'N/A'],
            ['Total Tithes', document.getElementById('total-tithes')?.textContent || 'N/A'],
            ['Total Expenses', document.getElementById('total-expenses')?.textContent || 'N/A'],
            ['Net Income', document.getElementById('net-income')?.textContent || 'N/A'],
            [],
            ['YoY Income Growth', document.getElementById('yoy-income-growth')?.textContent || 'N/A'],
            ['YoY Expense Growth', document.getElementById('yoy-expense-growth')?.textContent || 'N/A'],
            ['Financial Health Score', (document.getElementById('financial-health-score')?.textContent || 'N/A') + '/100'],
            ['Expense Ratio', document.getElementById('expense-ratio')?.textContent || 'N/A']
        ];

        const summarySheet = XLSX.utils.aoa_to_sheet(summaryData);
        XLSX.utils.book_append_sheet(workbook, summarySheet, 'Summary');

        // Sheet 2: Top Contributors
        const contributorsData = [['Rank', 'Member Name', 'Total Contribution', 'Status']];
        const contributorsTable = document.getElementById('top-contributors-tbody');
        if (contributorsTable) {
            const rows = contributorsTable.querySelectorAll('tr');
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                if (cells.length >= 4) {
                    contributorsData.push([
                        cells[0].textContent.trim(),
                        cells[1].textContent.trim(),
                        cells[2].textContent.trim(),
                        cells[3].textContent.trim()
                    ]);
                }
            });
        }

        const contributorsSheet = XLSX.utils.aoa_to_sheet(contributorsData);
        XLSX.utils.book_append_sheet(workbook, contributorsSheet, 'Top Contributors');

        // Sheet 3: KPIs
        const kpiData = [
            ['Key Performance Indicators'],
            [],
            ['KPI', 'Value', 'Status'],
            ['Income Growth (YoY)', document.getElementById('yoy-income-growth')?.textContent || 'N/A', 'Target: >10%'],
            ['Expense Ratio', document.getElementById('expense-ratio')?.textContent || 'N/A', 'Target: <80%'],
            ['Health Score', document.getElementById('financial-health-score')?.textContent || 'N/A', 'Target: >80'],
            ['Net Income', document.getElementById('net-income')?.textContent || 'N/A', 'Positive']
        ];

        const kpiSheet = XLSX.utils.aoa_to_sheet(kpiData);
        XLSX.utils.book_append_sheet(workbook, kpiSheet, 'KPIs');

        // Generate and download Excel file
        XLSX.writeFile(workbook, `Financial_Report_${new Date().toISOString().split('T')[0]}.xlsx`);

        // Show success message
        alert('✅ Excel Report Generated Successfully!\n\nThe detailed financial report has been downloaded.\n\nIncludes:\n- Summary sheet\n- Top contributors\n- Key Performance Indicators');

    } catch (error) {
        console.error('Error generating Excel:', error);
        alert('❌ Error generating Excel report. Please try again.');
    }
}

// Export functions
window.initializeAdvancedFinanceAnalytics = initializeAdvancedFinanceAnalytics;
window.startAdvancedFinanceAutoRefresh = startAdvancedFinanceAutoRefresh;
window.stopAdvancedFinanceAutoRefresh = stopAdvancedFinanceAutoRefresh;
window.exportFinancePDF = exportFinancePDF;
window.exportFinanceExcel = exportFinanceExcel;
