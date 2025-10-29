/**
 * Advanced Executive Summary Dashboard
 * Real-time data visualization and analytics
 */

let executiveCharts = {
    growth: null,
    financial: null,
    attendance: null,
    ministry: null,
    engagement: null
};

// Fetch and display executive summary data
async function loadExecutiveSummary() {
    try {
        console.log('Fetching executive summary...');
        const response = await fetch('get_executive_summary.php');
        console.log('Response status:', response.status);
        
        const result = await response.json();
        console.log('API Result:', result);
        
        if (result.success) {
            updateExecutiveSummary(result.data);
            // Hide loading, show content
            document.getElementById('executive-loading').style.display = 'none';
            document.getElementById('executive-content').style.display = 'block';
        } else {
            console.error('API Error:', result.message);
            if (result.error_details) {
                console.error('Error Details:', result.error_details);
            }
            showError(result.message || 'Failed to load executive summary');
        }
    } catch (error) {
        console.error('Error loading executive summary:', error);
        showError('Error loading data: ' + error.message);
    }
}

// Update all executive summary elements
function updateExecutiveSummary(data) {
    // Update membership card
    document.getElementById('total-members').textContent = data.membership.total;
    const growthText = data.membership.growth_rate >= 0 ? `+${data.membership.growth_rate}%` : `${data.membership.growth_rate}%`;
    document.getElementById('member-growth').textContent = `${growthText} growth | ${data.membership.new_30d} new this month`;
    
    // Update engagement card
    document.getElementById('engagement-rate').textContent = `${data.engagement.engagement_rate}%`;
    document.getElementById('engagement-detail').textContent = `${data.engagement.engaged_members} active | ${data.engagement.at_risk_members} at risk`;
    
    // Update financial card
    const income = data.financial.total_income;
    const incomeFormatted = income >= 1000 ? `GH₵${(income / 1000).toFixed(1)}K` : `GH₵${income.toFixed(0)}`;
    document.getElementById('total-income').textContent = incomeFormatted;
    const incomeGrowthText = data.financial.income_growth >= 0 ? `+${data.financial.income_growth}%` : `${data.financial.income_growth}%`;
    document.getElementById('income-growth').textContent = `${incomeGrowthText} vs last month`;
    
    // Update attendance card
    document.getElementById('avg-attendance').textContent = data.attendance.avg_attendance;
    const attendanceGrowthText = data.attendance.growth_rate >= 0 ? `+${data.attendance.growth_rate}%` : `${data.attendance.growth_rate}%`;
    document.getElementById('attendance-growth').textContent = `${attendanceGrowthText} | ${data.attendance.attendance_rate}% attendance rate`;
    
    // Update events card
    document.getElementById('total-events').textContent = data.events.upcoming;
    document.getElementById('events-detail').textContent = `${data.events.unique_attendees} unique attendees | ${data.events.engagement_rate}% engagement`;
    
    // Update communication card
    document.getElementById('total-messages').textContent = data.communication.total_messages;
    document.getElementById('messages-detail').textContent = `${data.communication.total_sent} sent | ${data.communication.delivery_rate}% delivery rate`;
    
    // Update members at risk card
    document.getElementById('at-risk-count').textContent = data.engagement.at_risk_members;
    document.getElementById('at-risk-detail').textContent = `Need follow-up contact`;
    
    // Update retention card
    document.getElementById('retention-rate').textContent = `${data.membership.retention_rate}%`;
    document.getElementById('retention-detail').textContent = `${data.membership.active} active members`;
    
    // Update KPI table
    updateKPITable(data);
    
    // Render charts
    renderGrowthTrendChart(data.trends);
    renderFinancialChart(data.trends.financial);
    renderAttendanceChart(data.trends.attendance);
    renderMinistryChart(data.ministries);
    renderEngagementChart(data);
}

// Update KPI Table
function updateKPITable(data) {
    const tbody = document.getElementById('kpi-table-body');
    
    const kpis = [
        {
            metric: 'Average Attendance',
            current: data.attendance.avg_attendance,
            previous: data.attendance.last_month_avg,
            change: data.attendance.growth_rate,
            target: Math.round(data.membership.active * 0.75), // 75% target
            status: data.attendance.attendance_rate >= 75 ? 'Target Met' : 'Below Target'
        },
        {
            metric: 'Total Income',
            current: `GH₵${data.financial.total_income.toLocaleString()}`,
            previous: `GH₵${data.financial.last_month_income.toLocaleString()}`,
            change: data.financial.income_growth,
            target: `GH₵${(data.financial.last_month_income * 1.05).toLocaleString()}`, // 5% growth target
            status: data.financial.income_growth >= 5 ? 'Above Target' : data.financial.income_growth >= 0 ? 'On Track' : 'Below Target'
        },
        {
            metric: 'New Members',
            current: data.membership.new_30d,
            previous: Math.round(data.membership.new_90d / 3), // Avg per month
            change: data.membership.growth_rate,
            target: 5,
            status: data.membership.new_30d >= 5 ? 'Excellent' : data.membership.new_30d >= 3 ? 'Good' : 'Needs Improvement'
        },
        {
            metric: 'Engagement Rate',
            current: `${data.engagement.engagement_rate}%`,
            previous: '85%', // Could be calculated from historical data
            change: data.engagement.engagement_rate - 85,
            target: '85%',
            status: data.engagement.engagement_rate >= 85 ? 'Exceeded' : data.engagement.engagement_rate >= 75 ? 'Good' : 'Needs Attention'
        }
    ];
    
    tbody.innerHTML = kpis.map(kpi => {
        const changeClass = kpi.change >= 0 ? 'tre-up' : 'tre-down';
        const changeSymbol = kpi.change >= 0 ? '↑' : '↓';
        const badgeClass = kpi.status.includes('Target Met') || kpi.status.includes('Exceeded') || kpi.status.includes('Excellent') ? 'badg-succ' : 
                           kpi.status.includes('Good') || kpi.status.includes('On Track') ? 'badg-warn' : 'badg-dang';
        
        return `
            <tr>
                <td class="tab-nam">${kpi.metric}</td>
                <td><strong>${kpi.current}</strong></td>
                <td>${kpi.previous}</td>
                <td><span class="tre-ind ${changeClass}">${changeSymbol} ${Math.abs(kpi.change)}%</span></td>
                <td>${kpi.target}</td>
                <td><span class="badg ${badgeClass}">${kpi.status}</span></td>
            </tr>
        `;
    }).join('');
}

// Render Growth Trend Chart (Multi-line)
function renderGrowthTrendChart(trends) {
    const canvas = document.getElementById('executiveGrowthChart');
    if (!canvas) return;
    
    // Get chart instance from canvas
    const existingChart = Chart.getChart(canvas);
    if (existingChart) {
        existingChart.destroy();
    }
    
    // Destroy our stored reference too
    if (executiveCharts.growth) {
        executiveCharts.growth.destroy();
        executiveCharts.growth = null;
    }
    
    const ctx = canvas.getContext('2d');
    const labels = trends.membership.map(t => t.month);
    
    executiveCharts.growth = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'New Members',
                    data: trends.membership.map(t => t.count),
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'Avg Attendance',
                    data: trends.attendance.map(t => t.attendance),
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.4,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom'
                },
                tooltip: {
                    mode: 'index',
                    intersect: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value.toFixed(0);
                        }
                    }
                }
            }
        }
    });
}

// Render Financial Chart
function renderFinancialChart(financialTrend) {
    const canvas = document.getElementById('executiveFinancialChart');
    if (!canvas) return;
    
    // Get chart instance from canvas
    const existingChart = Chart.getChart(canvas);
    if (existingChart) {
        existingChart.destroy();
    }
    
    // Destroy our stored reference too
    if (executiveCharts.financial) {
        executiveCharts.financial.destroy();
        executiveCharts.financial = null;
    }
    
    const ctx = canvas.getContext('2d');
    
    executiveCharts.financial = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: financialTrend.map(t => t.month),
            datasets: [{
                label: 'Income',
                data: financialTrend.map(t => t.amount),
                backgroundColor: '#10b981',
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'GH₵' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
}

// Render Attendance Chart
function renderAttendanceChart(attendanceTrend) {
    const canvas = document.getElementById('executiveAttendanceChart');
    if (!canvas) return;
    
    // Get chart instance from canvas
    const existingChart = Chart.getChart(canvas);
    if (existingChart) {
        existingChart.destroy();
    }
    
    // Destroy our stored reference too
    if (executiveCharts.attendance) {
        executiveCharts.attendance.destroy();
        executiveCharts.attendance = null;
    }
    
    const ctx = canvas.getContext('2d');
    
    executiveCharts.attendance = new Chart(ctx, {
        type: 'line',
        data: {
            labels: attendanceTrend.map(t => t.month),
            datasets: [{
                label: 'Attendance',
                data: attendanceTrend.map(t => t.attendance),
                borderColor: '#6366f1',
                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                tension: 0.4,
                fill: true,
                pointRadius: 5,
                pointBackgroundColor: '#6366f1'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

// Render Ministry Distribution Chart
function renderMinistryChart(ministries) {
    const canvas = document.getElementById('executiveDeptChart');
    if (!canvas) return;
    
    // Get chart instance from canvas
    const existingChart = Chart.getChart(canvas);
    if (existingChart) {
        existingChart.destroy();
    }
    
    // Destroy our stored reference too
    if (executiveCharts.ministry) {
        executiveCharts.ministry.destroy();
        executiveCharts.ministry = null;
    }
    
    const ctx = canvas.getContext('2d');
    
    executiveCharts.ministry = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ministries.map(m => m.name),
            datasets: [{
                data: ministries.map(m => m.count),
                backgroundColor: [
                    '#3b82f6',
                    '#10b981',
                    '#f59e0b',
                    '#ef4444',
                    '#8b5cf6',
                    '#ec4899'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

// Render Engagement Chart
function renderEngagementChart(data) {
    const canvas = document.getElementById('executiveEngagementChart');
    if (!canvas) return;
    
    // Get chart instance from canvas
    const existingChart = Chart.getChart(canvas);
    if (existingChart) {
        existingChart.destroy();
    }
    
    // Destroy our stored reference too
    if (executiveCharts.engagement) {
        executiveCharts.engagement.destroy();
        executiveCharts.engagement = null;
    }
    
    const ctx = canvas.getContext('2d');
    
    executiveCharts.engagement = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Active', 'Inactive', 'Visitors', 'At Risk'],
            datasets: [{
                label: 'Count',
                data: [
                    data.membership.active,
                    data.membership.inactive,
                    data.membership.visitors,
                    data.engagement.at_risk_members
                ],
                backgroundColor: [
                    '#10b981',
                    '#94a3b8',
                    '#3b82f6',
                    '#ef4444'
                ],
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

// Show error message
function showError(message) {
    document.getElementById('executive-loading').innerHTML = `
        <p style="color: #ef4444; font-size: 16px;">${message}</p>
        <button onclick="loadExecutiveSummary()" style="margin-top: 10px; padding: 8px 16px; background: #3b82f6; color: white; border: none; border-radius: 6px; cursor: pointer;">Retry</button>
    `;
}

// Track if already loaded
let executiveSummaryLoaded = false;
let executiveRefreshInterval = null;

// Load on page load
document.addEventListener('DOMContentLoaded', function() {
    // Check if we're on the executive tab
    const executiveTab = document.getElementById('executive');
    if (executiveTab && executiveTab.classList.contains('acti')) {
        loadExecutiveSummary();
        executiveSummaryLoaded = true;
        // Start auto-refresh
        if (!executiveRefreshInterval) {
            executiveRefreshInterval = setInterval(loadExecutiveSummary, 30000);
        }
    }
});

// Load when tab is clicked
document.querySelectorAll('.tab-butt').forEach(button => {
    button.addEventListener('click', function() {
        if (this.dataset.tab === 'executive' && !executiveSummaryLoaded) {
            loadExecutiveSummary();
            executiveSummaryLoaded = true;
            // Start auto-refresh
            if (!executiveRefreshInterval) {
                executiveRefreshInterval = setInterval(loadExecutiveSummary, 30000);
            }
        }
    });
});
