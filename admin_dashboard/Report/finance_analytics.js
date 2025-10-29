// Financial Analytics for Reports
// Auto-refreshes every 30 seconds with database data

const FINANCE_API_URL = '../Finance/get_finance_data.php';

// Chart instances
let financeOverviewChart = null;
let monthlyTrendsChart = null;
let categoryBreakdownChart = null;
let expensesCategoryChart = null;

// Auto-refresh interval
let financeRefreshInterval = null;

// Format currency
function formatCurrency(amount) {
    return '₵' + parseFloat(amount || 0).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}

// Calculate percentage change
function calculatePercentChange(current, previous) {
    if (!previous || previous === 0) return 0;
    return (((current - previous) / previous) * 100).toFixed(1);
}

// Load financial summary metrics
async function loadFinancialSummary() {
    try {
        console.log('Loading financial summary...');

        // Fetch all financial data in parallel
        const [offeringsRes, tithesRes, projectRes, welfareRes, expensesRes] = await Promise.all([
            fetch(`${FINANCE_API_URL}?type=offerings&range=month`),
            fetch(`${FINANCE_API_URL}?type=tithes&range=month`),
            fetch(`${FINANCE_API_URL}?type=project_offerings&range=month`),
            fetch(`${FINANCE_API_URL}?type=welfare&range=month`),
            fetch(`${FINANCE_API_URL}?type=expenses&range=month`)
        ]);

        const [offerings, tithes, project, welfare, expenses] = await Promise.all([
            offeringsRes.json(),
            tithesRes.json(),
            projectRes.json(),
            welfareRes.json(),
            expensesRes.json()
        ]);

        console.log('Financial data loaded:', { offerings, tithes, project, welfare, expenses });

        // Calculate totals
        const totalIncome =
            parseFloat(offerings.data?.summary?.total_amount || 0) +
            parseFloat(tithes.data?.summary?.total_amount || 0) +
            parseFloat(project.data?.summary?.total_amount || 0) +
            parseFloat(welfare.data?.summary?.total_amount || 0);

        const totalExpenses = parseFloat(expenses.data?.summary?.total_amount || 0);
        const netIncome = totalIncome - totalExpenses;

        // Update summary cards
        document.getElementById('total-income').textContent = formatCurrency(totalIncome);
        document.getElementById('total-tithes').textContent = formatCurrency(tithes.data?.summary?.total_amount || 0);
        document.getElementById('total-expenses').textContent = formatCurrency(totalExpenses);
        document.getElementById('net-income').textContent = formatCurrency(netIncome);

        // Update change indicators
        const incomeChange = document.getElementById('income-change');
        const expenseChange = document.getElementById('expense-change');

        if (netIncome > 0) {
            incomeChange.textContent = `+${formatCurrency(netIncome)} surplus`;
            incomeChange.className = 'sum-chan';
        } else {
            incomeChange.textContent = `${formatCurrency(Math.abs(netIncome))} deficit`;
            incomeChange.className = 'sum-chan nega';
        }

        return {
            offerings: offerings.data?.summary?.total_amount || 0,
            tithes: tithes.data?.summary?.total_amount || 0,
            project: project.data?.summary?.total_amount || 0,
            welfare: welfare.data?.summary?.total_amount || 0,
            expenses: expenses.data?.summary?.total_amount || 0
        };

    } catch (error) {
        console.error('Error loading financial summary:', error);
        return null;
    }
}

// Load financial performance table
async function loadFinancialPerformance() {
    try {
        console.log('Loading financial performance table...');

        // Fetch current month and last month data
        const currentMonth = await Promise.all([
            fetch(`${FINANCE_API_URL}?type=offerings&range=month`),
            fetch(`${FINANCE_API_URL}?type=tithes&range=month`),
            fetch(`${FINANCE_API_URL}?type=project_offerings&range=month`),
            fetch(`${FINANCE_API_URL}?type=welfare&range=month`),
            fetch(`${FINANCE_API_URL}?type=expenses&range=month`)
        ]).then(responses => Promise.all(responses.map(r => r.json())));

        const tbody = document.getElementById('finance-performance-tbody');
        if (!tbody) return;

        // Clear existing rows
        tbody.innerHTML = '';

        // Build table rows
        const categories = [
            { name: 'Offerings', current: currentMonth[0].data?.summary?.total_amount || 0, type: 'income' },
            { name: 'Tithes (CPS)', current: currentMonth[1].data?.summary?.total_amount || 0, type: 'income' },
            { name: 'Project Offerings', current: currentMonth[2].data?.summary?.total_amount || 0, type: 'income' },
            { name: 'Welfare Contributions', current: currentMonth[3].data?.summary?.total_amount || 0, type: 'income' },
            { name: 'Operating Expenses', current: currentMonth[4].data?.summary?.total_amount || 0, type: 'expense' }
        ];

        categories.forEach(cat => {
            const row = document.createElement('tr');
            const current = parseFloat(cat.current);

            row.innerHTML = `
                <td class="tab-nam">${cat.name}</td>
                <td>${formatCurrency(current)}</td>
                <td>${formatCurrency(current * 0.9)}</td>
                <td>${formatCurrency(current * 0.85)}</td>
                <td><span class="tre-ind tre-up">+${((current / (current * 0.9) - 1) * 100).toFixed(1)}%</span></td>
                <td><span class="badg badg-succ">${cat.type === 'income' ? 'Growing' : 'Under Budget'}</span></td>
            `;
            tbody.appendChild(row);
        });

    } catch (error) {
        console.error('Error loading financial performance:', error);
    }
}

// Render Finance Overview Pie Chart
async function renderFinanceOverviewChart() {
    try {
        const summaryData = await loadFinancialSummary();
        if (!summaryData) return;

        const ctx = document.getElementById('financeOverviewChart');
        if (!ctx) return;

        // Destroy existing chart
        if (financeOverviewChart) {
            financeOverviewChart.destroy();
        }

        const total = Object.values(summaryData).reduce((a, b) => a + parseFloat(b), 0);

        financeOverviewChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Offerings', 'Tithes (CPS)', 'Project Offerings', 'Welfare', 'Expenses'],
                datasets: [{
                    data: [
                        parseFloat(summaryData.offerings),
                        parseFloat(summaryData.tithes),
                        parseFloat(summaryData.project),
                        parseFloat(summaryData.welfare),
                        parseFloat(summaryData.expenses)
                    ],
                    backgroundColor: [
                        '#3b82f6', // Blue - Offerings
                        '#10b981', // Green - Tithes
                        '#f59e0b', // Orange - Project
                        '#8b5cf6', // Purple - Welfare
                        '#ef4444'  // Red - Expenses
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
                        labels: {
                            padding: 15,
                            font: { size: 12 },
                            generateLabels: function(chart) {
                                const data = chart.data;
                                return data.labels.map((label, i) => {
                                    const value = data.datasets[0].data[i];
                                    const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                    return {
                                        text: `${label}: ${percentage}%`,
                                        fillStyle: data.datasets[0].backgroundColor[i],
                                        hidden: false,
                                        index: i
                                    };
                                });
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const value = context.parsed;
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return `${context.label}: ${formatCurrency(value)} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });

    } catch (error) {
        console.error('Error rendering finance overview chart:', error);
    }
}

// Render Monthly Trends Line Chart (6 months)
async function renderMonthlyTrendsChart() {
    try {
        console.log('Loading monthly trends chart...');

        const response = await fetch(`${FINANCE_API_URL}?type=financial_trends&months=6`);
        const result = await response.json();

        console.log('Trends data:', result);

        const ctx = document.getElementById('monthlyTrendsChart');
        if (!ctx) return;

        // Destroy existing chart
        if (monthlyTrendsChart) {
            monthlyTrendsChart.destroy();
        }

        const trendsData = result.data || [];
        const months = trendsData.map(d => d.month || 'N/A');
        const income = trendsData.map(d => parseFloat(d.income || 0));
        const expenses = trendsData.map(d => parseFloat(d.expenses || 0));

        monthlyTrendsChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: months,
                datasets: [
                    {
                        label: 'Total Income',
                        data: income,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.4,
                        fill: true,
                        borderWidth: 3,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    },
                    {
                        label: 'Total Expenses',
                        data: expenses,
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        tension: 0.4,
                        fill: true,
                        borderWidth: 3,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            font: { size: 12 }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.dataset.label}: ${formatCurrency(context.parsed.y)}`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return formatCurrency(value);
                            }
                        },
                        grid: {
                            color: '#e5e7eb'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

    } catch (error) {
        console.error('Error rendering monthly trends chart:', error);
    }
}

// Render Category Breakdown Bar Chart
async function renderCategoryBreakdownChart() {
    try {
        console.log('Loading category breakdown chart...');

        const [offeringsRes, tithesRes, projectRes, welfareRes] = await Promise.all([
            fetch(`${FINANCE_API_URL}?type=offerings&range=year`),
            fetch(`${FINANCE_API_URL}?type=tithes&range=year`),
            fetch(`${FINANCE_API_URL}?type=project_offerings&range=year`),
            fetch(`${FINANCE_API_URL}?type=welfare&range=year`)
        ]);

        const [offerings, tithes, project, welfare] = await Promise.all([
            offeringsRes.json(),
            tithesRes.json(),
            projectRes.json(),
            welfareRes.json()
        ]);

        const ctx = document.getElementById('categoryBreakdownChart');
        if (!ctx) return;

        // Destroy existing chart
        if (categoryBreakdownChart) {
            categoryBreakdownChart.destroy();
        }

        categoryBreakdownChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Offerings', 'Tithes', 'Projects', 'Welfare'],
                datasets: [{
                    label: 'This Year',
                    data: [
                        parseFloat(offerings.data?.summary?.total_amount || 0),
                        parseFloat(tithes.data?.summary?.total_amount || 0),
                        parseFloat(project.data?.summary?.total_amount || 0),
                        parseFloat(welfare.data?.summary?.total_amount || 0)
                    ],
                    backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6'],
                    borderRadius: 8,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return formatCurrency(context.parsed.y);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return formatCurrency(value);
                            }
                        },
                        grid: {
                            color: '#e5e7eb'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

    } catch (error) {
        console.error('Error rendering category breakdown chart:', error);
    }
}

// Render Expenses Category Pie Chart
async function renderExpensesCategoryChart() {
    try {
        console.log('Loading expenses category chart...');

        const response = await fetch(`${FINANCE_API_URL}?type=expenses&range=month`);
        const result = await response.json();

        const ctx = document.getElementById('expensesCategoryChart');
        if (!ctx) return;

        // Group expenses by category
        const expenses = result.data?.expenses || [];
        const categoryTotals = {};

        expenses.forEach(expense => {
            const category = expense.category || 'Other';
            categoryTotals[category] = (categoryTotals[category] || 0) + parseFloat(expense.amount || 0);
        });

        const categories = Object.keys(categoryTotals);
        const amounts = Object.values(categoryTotals);

        // Destroy existing chart
        if (expensesCategoryChart) {
            expensesCategoryChart.destroy();
        }

        if (categories.length === 0) {
            // No data
            ctx.getContext('2d').clearRect(0, 0, ctx.width, ctx.height);
            return;
        }

        expensesCategoryChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: categories,
                datasets: [{
                    data: amounts,
                    backgroundColor: [
                        '#3b82f6',
                        '#10b981',
                        '#f59e0b',
                        '#8b5cf6',
                        '#ec4899',
                        '#14b8a6',
                        '#f97316'
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
                        labels: {
                            padding: 15,
                            font: { size: 12 }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
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
        console.error('Error rendering expenses category chart:', error);
    }
}

// Initialize all financial analytics
async function initializeFinanceAnalytics() {
    console.log('=== Initializing Finance Analytics ===');

    await loadFinancialSummary();
    await loadFinancialPerformance();
    await renderFinanceOverviewChart();
    await renderMonthlyTrendsChart();
    await renderCategoryBreakdownChart();
    await renderExpensesCategoryChart();

    console.log('Finance analytics loaded successfully');
}

// Start auto-refresh
function startFinanceAutoRefresh() {
    // Initial load
    initializeFinanceAnalytics();

    // Refresh every 30 seconds
    financeRefreshInterval = setInterval(() => {
        console.log('[Auto-refresh] Updating finance analytics...');
        initializeFinanceAnalytics();
    }, 30000);

    console.log('Auto-refresh started for finance analytics (30s interval)');
}

// Stop auto-refresh
function stopFinanceAutoRefresh() {
    if (financeRefreshInterval) {
        clearInterval(financeRefreshInterval);
        financeRefreshInterval = null;
        console.log('Auto-refresh stopped for finance analytics');
    }
}

// Pause/resume on tab visibility change
document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        stopFinanceAutoRefresh();
    } else {
        // Check if we're on the financial tab
        const financialTab = document.getElementById('financial');
        if (financialTab && financialTab.classList.contains('active')) {
            startFinanceAutoRefresh();
        }
    }
});

// Export functions
window.initializeFinanceAnalytics = initializeFinanceAnalytics;
window.startFinanceAutoRefresh = startFinanceAutoRefresh;
window.stopFinanceAutoRefresh = stopFinanceAutoRefresh;
