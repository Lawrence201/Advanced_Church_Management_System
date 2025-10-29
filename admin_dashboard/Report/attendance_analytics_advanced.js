// Advanced Attendance Analytics for Reports
// Professional attendance intelligence system with real-time analytics

const ATTENDANCE_API_URL = '../Attendance/get_attendance_analytics.php';

// Chart instances
let weeklyTrendsChart = null;
let demographicsChart = null;
let peakTimesChart = null;
let retentionChart = null;
let growthChart = null;
let serviceBreakdownChart = null;

// Auto-refresh interval
let attendanceRefreshInterval = null;

// Format number with commas
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

// Calculate percentage change
function calculatePercentChange(current, previous) {
    if (!previous || previous === 0) return current > 0 ? '+100.0' : '0.0';
    const change = (((current - previous) / previous) * 100).toFixed(1);
    return change > 0 ? '+' + change : change;
}

// Render empty state chart
function renderEmptyAttendanceChart(ctx, chartInstance, message) {
    if (chartInstance) chartInstance.destroy();

    const canvas = ctx;
    const context = canvas.getContext('2d');

    context.clearRect(0, 0, canvas.width, canvas.height);
    context.font = '14px Arial';
    context.fillStyle = '#94a3b8';
    context.textAlign = 'center';
    context.textBaseline = 'middle';

    const lines = message.split('. ');
    const lineHeight = 24;
    const startY = canvas.height / 2 - (lines.length * lineHeight) / 2;

    lines.forEach((line, index) => {
        context.fillText(line, canvas.width / 2, startY + (index * lineHeight));
    });
}

// Load attendance overview summary
async function loadAttendanceOverview() {
    try {
        console.log('Loading attendance overview...');

        const response = await fetch(`${ATTENDANCE_API_URL}?type=overview&range=month`);
        const result = await response.json();

        if (!result.success) {
            console.error('Failed to load attendance overview');
            return;
        }

        const data = result.data;

        // Update main KPI cards
        document.getElementById('total-members-att').textContent = formatNumber(data.total_members || 0);
        document.getElementById('members-attended').textContent = formatNumber(data.members_attended || 0);
        document.getElementById('total-visitors-att').textContent = formatNumber(data.total_visitors || 0);
        document.getElementById('avg-attendance-att').textContent = formatNumber(data.avg_attendance || 0);

        // Update advanced metrics
        document.getElementById('attendance-rate').textContent = (data.attendance_rate || 0) + '%';
        document.getElementById('peak-attendance').textContent = formatNumber(data.peak_attendance || 0);
        document.getElementById('males-count-att').textContent = formatNumber(data.males_count || 0);
        document.getElementById('females-count-att').textContent = formatNumber(data.females_count || 0);
        document.getElementById('children-count-att').textContent = formatNumber(data.children_count || 0);
        
        // Calculate adults (total attended - children)
        const adultsCount = (data.members_attended || 0) - (data.children_count || 0);
        document.getElementById('adults-count-att').textContent = formatNumber(adultsCount > 0 ? adultsCount : 0);

        // Update growth metrics
        await loadGrowthMetrics();

    } catch (error) {
        console.error('Error loading attendance overview:', error);
    }
}

// Load growth metrics
async function loadGrowthMetrics() {
    try {
        const response = await fetch(`${ATTENDANCE_API_URL}?type=growth_metrics`);
        const result = await response.json();

        if (!result.success) return;

        const data = result.data;
        const growth = data.growth_percentage || 0;

        document.getElementById('att-growth-percentage').textContent =
            (growth > 0 ? '+' : '') + growth + '%';

        document.getElementById('new-attendees').textContent = formatNumber(data.new_attendees || 0);

        // Update growth indicator
        const growthEl = document.getElementById('att-growth-indicator');
        if (growth > 0) {
            growthEl.innerHTML = `↑ ${growth}% vs last month`;
            growthEl.className = 'tre-ind tre-up';
        } else if (growth < 0) {
            growthEl.innerHTML = `↓ ${Math.abs(growth)}% vs last month`;
            growthEl.className = 'tre-ind tre-down';
        } else {
            growthEl.innerHTML = '→ No change';
            growthEl.className = 'tre-ind';
        }

    } catch (error) {
        console.error('Error loading growth metrics:', error);
    }
}

// Render weekly attendance trends chart
async function renderWeeklyTrendsChart() {
    try {
        console.log('Loading weekly trends chart...');

        const response = await fetch(`${ATTENDANCE_API_URL}?type=trends&weeks=12`);
        const result = await response.json();

        const ctx = document.getElementById('weeklyTrendsChart');
        if (!ctx) return;

        if (weeklyTrendsChart) weeklyTrendsChart.destroy();

        const trendsData = result.data || [];

        if (trendsData.length === 0) {
            renderEmptyAttendanceChart(ctx, weeklyTrendsChart, 'No attendance data available. Start recording attendance to see trends.');
            return;
        }

        const weeks = trendsData.map(d => d.week);
        const totals = trendsData.map(d => d.total);
        const members = trendsData.map(d => d.members);
        const visitors = trendsData.map(d => d.visitors);

        weeklyTrendsChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: weeks,
                datasets: [
                    {
                        label: 'Total Attendance',
                        data: totals,
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    },
                    {
                        label: 'Members',
                        data: members,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true,
                        pointRadius: 3
                    },
                    {
                        label: 'Visitors',
                        data: visitors,
                        borderColor: '#f59e0b',
                        backgroundColor: 'rgba(245, 158, 11, 0.1)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true,
                        pointRadius: 3
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { padding: 15, font: { size: 12 } }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: '#e5e7eb' }
                    },
                    x: { grid: { display: false } }
                }
            }
        });

    } catch (error) {
        console.error('Error rendering weekly trends chart:', error);
    }
}

// Render demographics chart
async function renderDemographicsChart() {
    try {
        console.log('Loading demographics chart...');

        const response = await fetch(`${ATTENDANCE_API_URL}?type=demographics&range=month`);
        const result = await response.json();

        const ctx = document.getElementById('demographicsChart');
        if (!ctx) return;

        if (demographicsChart) demographicsChart.destroy();

        const demographics = result.data || {};
        const ageGroups = demographics.age_groups || [];

        if (ageGroups.length === 0) {
            renderEmptyAttendanceChart(ctx, demographicsChart, 'No demographic data available.');
            return;
        }

        const labels = ageGroups.map(d => d.age_group);
        const counts = ageGroups.map(d => parseInt(d.count));

        demographicsChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: counts,
                    backgroundColor: [
                        '#3b82f6',
                        '#10b981',
                        '#f59e0b',
                        '#8b5cf6',
                        '#ec4899'
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
                                return `${context.label}: ${context.parsed} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });

    } catch (error) {
        console.error('Error rendering demographics chart:', error);
    }
}

// Render peak times chart
async function renderPeakTimesChart() {
    try {
        console.log('Loading peak times chart...');

        const response = await fetch(`${ATTENDANCE_API_URL}?type=peak_times&range=month`);
        const result = await response.json();

        const ctx = document.getElementById('peakTimesChart');
        if (!ctx) return;

        if (peakTimesChart) peakTimesChart.destroy();

        const peakData = result.data || {};
        const daysDistribution = peakData.days_distribution || [];

        if (daysDistribution.length === 0) {
            renderEmptyAttendanceChart(ctx, peakTimesChart, 'No peak times data available.');
            return;
        }

        const days = daysDistribution.map(d => d.day_name);
        const counts = daysDistribution.map(d => parseInt(d.count));

        peakTimesChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: days,
                datasets: [{
                    label: 'Attendance by Day',
                    data: counts,
                    backgroundColor: '#3b82f6',
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (context) => `Attendance: ${context.parsed.y}`
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: '#e5e7eb' }
                    },
                    x: { grid: { display: false } }
                }
            }
        });

    } catch (error) {
        console.error('Error rendering peak times chart:', error);
    }
}

// Render retention metrics chart
async function renderRetentionChart() {
    try {
        console.log('Loading retention chart...');

        const response = await fetch(`${ATTENDANCE_API_URL}?type=retention&months=6`);
        const result = await response.json();

        const ctx = document.getElementById('retentionChart');
        if (!ctx) return;

        if (retentionChart) retentionChart.destroy();

        const retentionData = result.data || [];

        if (retentionData.length === 0) {
            renderEmptyAttendanceChart(ctx, retentionChart, 'No retention data available.');
            return;
        }

        const months = retentionData.map(d => d.month);
        const regular = retentionData.map(d => d.regular);
        const returning = retentionData.map(d => d.returning);
        const firstTimers = retentionData.map(d => d.first_timers);

        retentionChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: months,
                datasets: [
                    {
                        label: 'Regular',
                        data: regular,
                        backgroundColor: '#10b981',
                        borderRadius: 6
                    },
                    {
                        label: 'Returning',
                        data: returning,
                        backgroundColor: '#3b82f6',
                        borderRadius: 6
                    },
                    {
                        label: 'First Timers',
                        data: firstTimers,
                        backgroundColor: '#f59e0b',
                        borderRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { padding: 15, font: { size: 11 } }
                    }
                },
                scales: {
                    x: { stacked: true, grid: { display: false } },
                    y: { stacked: true, beginAtZero: true, grid: { color: '#e5e7eb' } }
                }
            }
        });

    } catch (error) {
        console.error('Error rendering retention chart:', error);
    }
}

// Render church groups distribution chart
async function renderChurchGroupsChart() {
    try {
        console.log('Loading church groups chart...');

        const response = await fetch(`${ATTENDANCE_API_URL}?type=demographics&range=month`);
        const result = await response.json();

        const ctx = document.getElementById('churchGroupsChart');
        if (!ctx) return;

        if (growthChart) growthChart.destroy();

        const demographics = result.data || {};
        const churchGroups = demographics.church_groups || [];

        if (churchGroups.length === 0) {
            renderEmptyAttendanceChart(ctx, growthChart, 'No church group data available.');
            return;
        }

        const labels = churchGroups.map(d => d.church_group);
        const counts = churchGroups.map(d => parseInt(d.count));

        growthChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Members Attended',
                    data: counts,
                    backgroundColor: '#8b5cf6',
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: { color: '#e5e7eb' }
                    },
                    y: { grid: { display: false } }
                }
            }
        });

    } catch (error) {
        console.error('Error rendering church groups chart:', error);
    }
}

// Render service breakdown chart
async function renderServiceBreakdownChart() {
    try {
        console.log('Loading service breakdown chart...');

        const response = await fetch(`${ATTENDANCE_API_URL}?type=service_breakdown&range=month`);
        const result = await response.json();

        const ctx = document.getElementById('serviceBreakdownChart');
        if (!ctx) return;

        if (serviceBreakdownChart) serviceBreakdownChart.destroy();

        const serviceData = result.data || {};
        const services = serviceData.services || [];

        if (services.length === 0) {
            renderEmptyAttendanceChart(ctx, serviceBreakdownChart, 'No service data available.');
            return;
        }

        const labels = services.map(d => d.service_id || 'Unknown');
        const totals = services.map(d => parseInt(d.total_attendance));

        serviceBreakdownChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: totals,
                    backgroundColor: [
                        '#3b82f6',
                        '#10b981',
                        '#f59e0b',
                        '#8b5cf6',
                        '#ec4899',
                        '#14b8a6'
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
                                return `${context.label}: ${context.parsed} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });

    } catch (error) {
        console.error('Error rendering service breakdown chart:', error);
    }
}

// Initialize all attendance analytics
async function initializeAttendanceAnalytics() {
    try {
        console.log('Initializing attendance analytics...');

        await Promise.all([
            loadAttendanceOverview(),
            renderWeeklyTrendsChart(),
            renderDemographicsChart(),
            renderPeakTimesChart(),
            renderRetentionChart(),
            renderChurchGroupsChart(),
            renderServiceBreakdownChart()
        ]);

        console.log('Attendance analytics initialized successfully');

    } catch (error) {
        console.error('Error initializing attendance analytics:', error);
    }
}

// Auto-refresh functionality
function startAttendanceAutoRefresh() {
    stopAttendanceAutoRefresh();

    attendanceRefreshInterval = setInterval(() => {
        console.log('Auto-refreshing attendance analytics...');
        initializeAttendanceAnalytics();
    }, 30000); // 30 seconds
}

function stopAttendanceAutoRefresh() {
    if (attendanceRefreshInterval) {
        clearInterval(attendanceRefreshInterval);
        attendanceRefreshInterval = null;
    }
}

// Tab visibility handling
document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        stopAttendanceAutoRefresh();
    } else {
        const attendanceTab = document.getElementById('attendance');
        if (attendanceTab && attendanceTab.classList.contains('acti')) {
            startAttendanceAutoRefresh();
        }
    }
});

// Export functions for global use
window.initializeAttendanceAnalytics = initializeAttendanceAnalytics;
window.startAttendanceAutoRefresh = startAttendanceAutoRefresh;
window.stopAttendanceAutoRefresh = stopAttendanceAutoRefresh;
