// ==================== Visitor Analytics Auto-Refresh ====================
let visitorRefreshInterval = null;
let visitorFunnelChart = null;
let visitorSourcesChart = null;
let visitorTrendsChart = null;

async function loadVisitorAnalytics() {
    try {
        const response = await fetch('../Visitors/visitor_stats.php');
        const result = await response.json();

        if (result.success) {
            const data = result.data;

            // Update summary cards
            document.getElementById('visitor-total').textContent = data.total_visitors || 0;
            document.getElementById('visitor-pending').textContent = data.pending_followups || 0;
            document.getElementById('visitor-conversion').textContent = data.conversion_rate + '%';
            document.getElementById('visitor-converted').textContent = `${data.converted_members} new members`;
            document.getElementById('visitor-returning').textContent = data.returning_visitors || 0;

            // Additional stats
            document.getElementById('visitor-new-week').textContent = data.new_visitors || 0;
            document.getElementById('visitor-contacted').textContent = data.contacted || 0;
            document.getElementById('visitor-scheduled').textContent = data.scheduled || 0;
            document.getElementById('visitor-urgent').textContent = data.urgent_followups || 0;

            // Update total change
            const newVisitorsText = data.new_visitors === 1 ? 'new visitor' : 'new visitors';
            document.getElementById('visitor-total-change').textContent = `${data.new_visitors} ${newVisitorsText} this week`;

            // Render charts
            renderVisitorFunnelChart(data);
            renderVisitorSourcesChart(data.by_source);
            renderVisitorTrendsChart(data);

            // Render recent visitors table
            renderRecentVisitorsTable(data.recent_visitors);
        }
    } catch (error) {
        console.error('Error loading visitor analytics:', error);
    }
}

// Follow-Up Status Funnel Chart (HORIZONTAL BAR CHART - ORIGINAL DESIGN)
function renderVisitorFunnelChart(data) {
    const ctx = document.getElementById('visitorFunnelChart');
    if (!ctx) return;

    if (visitorFunnelChart) {
        visitorFunnelChart.destroy();
    }

    // Calculate funnel stages from database
    const totalVisitors = data.total_visitors || 0;
    const firstVisit = totalVisitors;
    const secondVisit = data.returning_visitors || 0;
    const regular = Math.floor(secondVisit * 0.65); // Estimate regulars
    const members = data.converted_members || 0;

    visitorFunnelChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['First Visit', 'Second Visit', 'Regular', 'Member'],
            datasets: [{
                data: [firstVisit, secondVisit, regular, members],
                backgroundColor: ['#ec4899', '#8b5cf6', '#3b82f6', '#10b981'],
                borderRadius: 8
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { x: { beginAtZero: true } }
        }
    });
}

// Visitor Sources Chart (DOUGHNUT CHART - ORIGINAL DESIGN)
function renderVisitorSourcesChart(sources) {
    const ctx = document.getElementById('visitorSourcesChart');
    if (!ctx) return;

    if (visitorSourcesChart) {
        visitorSourcesChart.destroy();
    }

    // Handle empty sources
    if (!sources || sources.length === 0) {
        visitorSourcesChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['No Data'],
                datasets: [{
                    data: [1],
                    backgroundColor: ['#e2e8f0']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'right' } }
            }
        });
        return;
    }

    // Get data from database and consolidate duplicate "other" entries
    const sourceMap = {};
    sources.forEach(s => {
        const sourceName = (s.source || 'other').toLowerCase().trim();
        // Consolidate all variations of "other" into one
        const normalizedSource = sourceName === 'other' || sourceName === '' ? 'other' : sourceName;

        if (sourceMap[normalizedSource]) {
            sourceMap[normalizedSource] += parseInt(s.count);
        } else {
            sourceMap[normalizedSource] = parseInt(s.count);
        }
    });

    const labels = Object.keys(sourceMap);
    const counts = Object.values(sourceMap);

    visitorSourcesChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: counts,
                backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ec4899', '#8b5cf6']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'right' } }
        }
    });
}

// Visitor Trends Chart (LINE CHART - ORIGINAL DESIGN)
function renderVisitorTrendsChart(data) {
    const ctx = document.getElementById('visitorTrendsChart');
    if (!ctx) return;

    if (visitorTrendsChart) {
        visitorTrendsChart.destroy();
    }

    // For now, we'll create a simple weekly trend
    // You can enhance this later with actual weekly data from database
    const newVisitors = data.new_visitors || 0;
    const returning = data.returning_visitors || 0;

    // Generate 8 weeks of trend data (simplified)
    const weeks = ['Week 1', 'Week 2', 'Week 3', 'Week 4', 'Week 5', 'Week 6', 'Week 7', 'Week 8'];
    const newData = Array(8).fill(0).map(() => Math.max(0, Math.floor(newVisitors / 8)));
    const returningData = Array(8).fill(0).map(() => Math.max(0, Math.floor(returning / 8)));

    visitorTrendsChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: weeks,
            datasets: [
                {
                    label: 'New Visitors',
                    data: newData,
                    borderColor: '#ec4899',
                    backgroundColor: 'rgba(236, 72, 153, 0.1)',
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'Returning',
                    data: returningData,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } },
            scales: { y: { beginAtZero: true } }
        }
    });
}

// Recent Visitors Table
function renderRecentVisitorsTable(visitors) {
    const tbody = document.getElementById('recent-visitors-table');

    if (!visitors || visitors.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 40px; color: #94a3b8;">No recent visitors found</td></tr>';
        return;
    }

    tbody.innerHTML = visitors.map(visitor => {
        const createdDate = visitor.created_at ? new Date(visitor.created_at).toLocaleDateString() : 'N/A';
        const lastVisit = visitor.last_visit ? new Date(visitor.last_visit).toLocaleDateString() : 'First visit';
        const sourceName = visitor.source ? visitor.source.charAt(0).toUpperCase() + visitor.source.slice(1) : 'N/A';

        // Status badge colors
        let statusClass = 'upcoming';
        let statusBg = '#dbeafe';
        let statusColor = '#1e40af';

        if (visitor.follow_up_status === 'contacted') {
            statusBg = '#ddd6fe';
            statusColor = '#6b21a8';
        } else if (visitor.follow_up_status === 'scheduled') {
            statusBg = '#d1fae5';
            statusColor = '#065f46';
        } else if (visitor.follow_up_status === 'completed') {
            statusBg = '#d1fae5';
            statusColor = '#047857';
        } else if (visitor.follow_up_status === 'pending') {
            statusBg = '#fef3c7';
            statusColor = '#92400e';
        }

        const statusText = visitor.follow_up_status ? visitor.follow_up_status.charAt(0).toUpperCase() + visitor.follow_up_status.slice(1) : 'Pending';

        return `
            <tr>
                <td style="font-weight: 500;">${visitor.name || 'N/A'}</td>
                <td>${visitor.phone || 'N/A'}</td>
                <td style="color: #64748b; font-size: 13px;">${visitor.email || 'N/A'}</td>
                <td>
                    <span style="display: inline-block; padding: 4px 10px; background: #f1f5f9; border-radius: 6px; font-size: 12px; font-weight: 500; text-transform: capitalize;">
                        ${sourceName}
                    </span>
                </td>
                <td style="font-size: 13px;">${createdDate}</td>
                <td style="font-size: 13px; color: #64748b;">${lastVisit}</td>
                <td>
                    <span style="display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; background: ${statusBg}; color: ${statusColor}; text-transform: capitalize;">
                        ${statusText}
                    </span>
                </td>
                <td style="font-size: 13px; color: #64748b;">${visitor.assigned_to || 'Unassigned'}</td>
            </tr>
        `;
    }).join('');
}

function startVisitorAutoRefresh() {
    // Initial load
    loadVisitorAnalytics();

    // Set up auto-refresh every 30 seconds
    visitorRefreshInterval = setInterval(() => {
        loadVisitorAnalytics();
    }, 30000); // 30 seconds
}

// Pause auto-refresh when tab is hidden
document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        if (visitorRefreshInterval) {
            clearInterval(visitorRefreshInterval);
            visitorRefreshInterval = null;
        }
    } else {
        // Resume when tab becomes visible
        if (!visitorRefreshInterval) {
            startVisitorAutoRefresh();
        }
    }
});

// Start visitor auto-refresh on page load
document.addEventListener('DOMContentLoaded', function() {
    startVisitorAutoRefresh();
});
