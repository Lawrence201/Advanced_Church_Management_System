/**
 * Real-Time AI Insights JavaScript
 * Handles live data updates, chart rendering, and interactive features
 */

const AI_API_URL = 'get_ai_insights.php';
let refreshInterval = null;
let chartInstances = {};

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    initializeAIInsights();
    startAutoRefresh();
});

// ========================================
// INITIALIZATION
// ========================================

async function initializeAIInsights() {
    console.log('🤖 Initializing AI Insights...');
    
    try {
        await Promise.all([
            loadOverviewData(),
            loadPredictions(),
            loadMemberRisk(),
            loadKeyInsights(),
            loadTrends()
        ]);
        
        console.log('✅ AI Insights loaded successfully');
    } catch (error) {
        console.error('❌ Error initializing AI:', error);
        showErrorMessage('Failed to load AI insights. Please refresh the page.');
    }
}

// ========================================
// DATA LOADING FUNCTIONS
// ========================================

async function loadOverviewData() {
    try {
        const response = await fetch(`${AI_API_URL}?type=overview`);
        const result = await response.json();
        
        if (result.success) {
            updateOverviewUI(result.data);
        }
    } catch (error) {
        console.error('Error loading overview:', error);
    }
}

async function loadPredictions() {
    try {
        const response = await fetch(`${AI_API_URL}?type=predictions`);
        const result = await response.json();
        
        if (result.success) {
            updatePredictionsUI(result.data);
            renderPredictionChart(result.data);
        }
    } catch (error) {
        console.error('Error loading predictions:', error);
    }
}

async function loadMemberRisk() {
    try {
        const response = await fetch(`${AI_API_URL}?type=member_risk`);
        const result = await response.json();
        
        if (result.success) {
            updateMemberRiskUI(result.data);
        }
    } catch (error) {
        console.error('Error loading member risk:', error);
    }
}

async function loadKeyInsights() {
    try {
        const response = await fetch(`${AI_API_URL}?type=insights`);
        const result = await response.json();
        
        if (result.success) {
            updateInsightsUI(result.data);
        }
    } catch (error) {
        console.error('Error loading insights:', error);
    }
}

async function loadTrends() {
    try {
        const response = await fetch(`${AI_API_URL}?type=trends`);
        const result = await response.json();
        
        if (result.success) {
            renderTrendCharts(result.data);
        }
    } catch (error) {
        console.error('Error loading trends:', error);
    }
}

// ========================================
// UI UPDATE FUNCTIONS
// ========================================

function updateOverviewUI(data) {
    // Update main stats
    safeUpdateText('.zest-status-bar p', `Analyzing ${data.total_members || 0} members across ${data.data_points_analyzed || 12} data points in real-time • Auto-updates every 30s`);
    
    const statValues = document.querySelectorAll('.nexo-stat-value');
    if (statValues.length >= 3) {
        statValues[0].textContent = data.insights_generated || 0;
        statValues[1].textContent = (data.accuracy_rate || 0) + '%';
        statValues[2].textContent = data.monitoring_status || '24/7';
    }
    
    // Activity indicator
    const pulse = document.querySelector('.quix-pulse');
    if (pulse) {
        pulse.style.animation = 'pulse 2s ease-in-out infinite';
    }
}

function updatePredictionsUI(predictions) {
    const predCards = document.querySelectorAll('.jiva-prediction-card');
    
    // Attendance prediction
    if (predCards[0] && predictions.attendance) {
        safeUpdateText(predCards[0], '.jiva-prediction-value', predictions.attendance.predicted || 0);
        updateTrendIndicator(predCards[0], predictions.attendance.trend, 'vs current');
        safeUpdateText(predCards[0], '.xeno-confidence-label', `Confidence: ${predictions.attendance.confidence}%`);
        updateProgressBar(predCards[0], predictions.attendance.confidence);
    }
    
    // Donations prediction
    if (predCards[1] && predictions.donations) {
        const amount = (predictions.donations.predicted / 1000).toFixed(1);
        safeUpdateText(predCards[1], '.jiva-prediction-value', `$${amount}K`);
        updateTrendIndicator(predCards[1], predictions.donations.trend, 'projected');
        safeUpdateText(predCards[1], '.xeno-confidence-label', `Confidence: ${predictions.donations.confidence}%`);
        updateProgressBar(predCards[1], predictions.donations.confidence);
    }
    
    // Event participation
    if (predCards[2] && predictions.event_participation) {
        safeUpdateText(predCards[2], '.jiva-prediction-value', predictions.event_participation.predicted || 0);
        updateTrendIndicator(predCards[2], predictions.event_participation.trend, 'expected');
        safeUpdateText(predCards[2], '.xeno-confidence-label', `Confidence: ${predictions.event_participation.confidence}%`);
        updateProgressBar(predCards[2], predictions.event_participation.confidence);
    }
    
    // Message engagement
    if (predCards[3] && predictions.message_engagement) {
        safeUpdateText(predCards[3], '.jiva-prediction-value', `${predictions.message_engagement.predicted}%`);
        updateTrendIndicator(predCards[3], predictions.message_engagement.trend, 'improvement');
        safeUpdateText(predCards[3], '.xeno-confidence-label', `Confidence: ${predictions.message_engagement.confidence}%`);
        updateProgressBar(predCards[3], predictions.message_engagement.confidence);
    }
}

function updateMemberRiskUI(riskData) {
    const riskCards = document.querySelectorAll('.risko-card');
    
    if (riskCards.length >= 3 && riskData.summary) {
        safeUpdateText(riskCards[0], '.risko-count', riskData.summary.high_risk || 0);
        safeUpdateText(riskCards[1], '.risko-count', riskData.summary.medium_risk || 0);
        safeUpdateText(riskCards[2], '.risko-count', riskData.summary.low_risk || 0);
    }
    
    // Update member list
    const memberList = document.querySelector('.membrix-list');
    if (memberList && riskData.high_risk_members && riskData.high_risk_members.length > 0) {
        memberList.innerHTML = '';
        
        riskData.high_risk_members.slice(0, 5).forEach(member => {
            const memberHTML = `
                <div class="membrix-item" style="animation: fadeIn 0.5s ease-in">
                    <div class="membrix-info">
                        <div class="membrix-avatar" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">${member.initials}</div>
                        <div class="membrix-details">
                            <h4>${member.name}</h4>
                            <p>Last attended ${member.days_absent} days ago • ${member.risk_score >= 8 ? 'Critical' : 'High'} disengagement risk</p>
                        </div>
                    </div>
                    <div class="risko-score">
                        <div class="scorex-value" style="color: ${member.risk_score >= 8 ? '#dc2626' : '#f59e0b'};">${member.risk_score}</div>
                        <div class="scorex-label">RISK SCORE</div>
                    </div>
                </div>
            `;
            memberList.innerHTML += memberHTML;
        });
    }
}

function updateInsightsUI(insights) {
    const insightsGrid = document.querySelector('.kwest-insights-grid');
    
    if (insightsGrid && insights && insights.length > 0) {
        insightsGrid.innerHTML = '';
        
        insights.forEach((insight, index) => {
            const cardClass = insight.type === 'critical' ? 'zest-critical' : 
                            insight.type === 'success' ? 'zest-success' : 
                            insight.type === 'warning' ? 'zest-warning' : 'zest-info';
            
            const iconBg = insight.type === 'critical' ? '#fee2e2' : 
                         insight.type === 'success' ? '#d1fae5' : 
                         insight.type === 'warning' ? '#fef3c7' : '#dbeafe';
            
            const iconColor = insight.type === 'critical' ? '#dc2626' : 
                            insight.type === 'success' ? '#10b981' : 
                            insight.type === 'warning' ? '#d97706' : '#3b82f6';
            
            const priorityClass = insight.type === 'critical' ? 'xoro-high' : 
                                insight.type === 'success' ? 'xoro-low' : 'xoro-medium';
            
            const priorityText = insight.type === 'critical' ? 'High Priority' : 
                               insight.type === 'success' ? 'Opportunity' : 'Action Needed';
            
            const iconSVG = getInsightIcon(insight.type);
            
            const insightHTML = `
                <div class="kwest-insight-card ${cardClass}" style="animation: slideUp 0.5s ease-out ${index * 0.1}s both;">
                    <div class="yaro-insight-header">
                        <div class="yaro-insight-icon" style="background: ${iconBg}; color: ${iconColor};">
                            ${iconSVG}
                        </div>
                        <span class="yaro-insight-priority ${priorityClass}">${priorityText}</span>
                    </div>
                    <h3 class="yaro-insight-title">${insight.title}</h3>
                    <p class="yaro-insight-description">${insight.description}</p>
                    <div class="yaro-insight-metrics">
                        <div class="yaro-insight-metric">
                            <div class="zaro-metric-label">${insight.risk_score ? 'Risk Score' : insight.impact_score ? 'Impact Score' : 'Predicted Impact'}</div>
                            <div class="zaro-metric-value">${insight.risk_score ? insight.risk_score + '/10' : insight.impact_score ? insight.impact_score + '/10' : insight.predicted_impact || 'N/A'}</div>
                        </div>
                        <div class="yaro-insight-metric">
                            <div class="zaro-metric-label">${insight.confidence ? 'Confidence' : insight.roi_estimate ? 'ROI Estimate' : 'Time to Act'}</div>
                            <div class="zaro-metric-value">${insight.confidence ? insight.confidence + '%' : insight.roi_estimate || insight.time_to_act || 'N/A'}</div>
                        </div>
                    </div>
                    <div class="zaro-insight-actions">
                        <button class="zaro-insight-btn voxx-primary" onclick="handleInsightAction('${insight.action}')">${insight.action}</button>
                        <button class="zaro-insight-btn" onclick="viewInsightDetails(${index})">View Details</button>
                    </div>
                </div>
            `;
            
            insightsGrid.innerHTML += insightHTML;
        });
    }
}

// ========================================
// CHART RENDERING
// ========================================

function renderPredictionChart(predictions) {
    const canvas = document.getElementById('predictionChart');
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    
    // Destroy existing chart
    if (chartInstances.prediction) {
        chartInstances.prediction.destroy();
    }
    
    const weeklyData = predictions.attendance && predictions.attendance.weekly_data 
        ? predictions.attendance.weekly_data 
        : [320, 335, 342, 356];
    
    const predicted = predictions.attendance ? predictions.attendance.predicted : 378;
    
    chartInstances.prediction = new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4', 'Week 5 (Predicted)'],
            datasets: [
                {
                    label: 'Actual Attendance',
                    data: [...weeklyData, null],
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true,
                    borderWidth: 3,
                    pointRadius: 5,
                    pointHoverRadius: 7
                },
                {
                    label: 'AI Prediction',
                    data: [null, null, null, weeklyData[weeklyData.length - 1], predicted],
                    borderColor: '#8b5cf6',
                    backgroundColor: 'rgba(139, 92, 246, 0.1)',
                    tension: 0.4,
                    fill: true,
                    borderDash: [5, 5],
                    borderWidth: 3,
                    pointRadius: 5,
                    pointHoverRadius: 7
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        font: { size: 12 }
                    }
                },
                tooltip: {
                    mode: 'index',
                    intersect: false
                }
            },
            scales: {
                y: {
                    beginAtZero: false,
                    grid: { color: '#e5e7eb' }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });
}

function renderTrendCharts(trends) {
    // Render attendance trend if data available
    if (trends.weekly_attendance && trends.weekly_attendance.length > 0) {
        renderAttendanceTrendChart(trends.weekly_attendance);
    }
    
    // Render financial trend if data available
    if (trends.monthly_giving && trends.monthly_giving.length > 0) {
        renderFinancialTrendChart(trends.monthly_giving);
    }
}

function renderAttendanceTrendChart(data) {
    const canvas = document.getElementById('attendanceTrendChart');
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    
    if (chartInstances.attendanceTrend) {
        chartInstances.attendanceTrend.destroy();
    }
    
    const labels = data.map((_, i) => `Week ${i + 1}`);
    const members = data.map(d => d.members);
    const visitors = data.map(d => d.visitors);
    
    chartInstances.attendanceTrend = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Members',
                    data: members,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.4,
                    fill: true,
                    borderWidth: 2
                },
                {
                    label: 'Visitors',
                    data: visitors,
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(245, 158, 11, 0.1)',
                    tension: 0.4,
                    fill: true,
                    borderWidth: 2
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

function renderFinancialTrendChart(data) {
    const canvas = document.getElementById('financialTrendChart');
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    
    if (chartInstances.financialTrend) {
        chartInstances.financialTrend.destroy();
    }
    
    const labels = data.map(d => d.month);
    const amounts = data.map(d => d.total);
    
    chartInstances.financialTrend = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Monthly Giving ($)',
                data: amounts,
                backgroundColor: '#3b82f6',
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });
}

// ========================================
// HELPER FUNCTIONS
// ========================================

function safeUpdateText(parentOrSelector, childSelectorOrText, textValue) {
    let element;
    
    if (typeof parentOrSelector === 'string') {
        element = document.querySelector(parentOrSelector);
        if (element && typeof childSelectorOrText === 'string' && !textValue) {
            element.textContent = childSelectorOrText;
        }
    } else if (parentOrSelector && typeof childSelectorOrText === 'string') {
        element = parentOrSelector.querySelector(childSelectorOrText);
        if (element && textValue !== undefined) {
            element.textContent = textValue;
        }
    }
}

function updateProgressBar(parent, percentage) {
    const progressBar = parent.querySelector('.xeno-confidence-fill');
    if (progressBar) {
        progressBar.style.width = percentage + '%';
        progressBar.style.transition = 'width 1s ease-in-out';
    }
}

function updateTrendIndicator(parent, trendValue, suffix) {
    const trendElement = parent.querySelector('.jiva-prediction-trend');
    if (!trendElement) return;
    
    const trend = parseFloat(trendValue) || 0;
    const sign = trend > 0 ? '+' : '';
    const isPositive = trend >= 0;
    
    // Update arrow direction and color
    const arrowSVG = isPositive ? 
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15" /></svg>' :
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9" /></svg>';
    
    // Update text and styling
    trendElement.innerHTML = `${arrowSVG} ${sign}${trend}% ${suffix}`;
    trendElement.className = isPositive ? 'jiva-prediction-trend lift-up' : 'jiva-prediction-trend lift-down';
    trendElement.style.color = isPositive ? '#10b981' : '#ef4444';
}

function getInsightIcon(type) {
    const icons = {
        critical: '<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
        success: '<polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/>',
        warning: '<line x1="12" y1="1" x2="12" y2="13"/><path d="M12 1a5 5 0 0 1 5 5v7a5 5 0 0 1-10 0V6a5 5 0 0 1 5-5z"/><path d="M8.5 21h7"/><path d="M12 17v4"/>',
        info: '<circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>'
    };
    
    return `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${icons[type] || icons.info}</svg>`;
}

function showErrorMessage(message) {
    console.error(message);
    // Could add UI notification here
}

// ========================================
// AUTO-REFRESH
// ========================================

function startAutoRefresh() {
    // Refresh every 30 seconds
    refreshInterval = setInterval(() => {
        console.log('🔄 Auto-refreshing AI data...');
        initializeAIInsights();
    }, 30000);
}

function stopAutoRefresh() {
    if (refreshInterval) {
        clearInterval(refreshInterval);
        refreshInterval = null;
    }
}

// Stop refresh when page is hidden
document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        stopAutoRefresh();
    } else {
        startAutoRefresh();
    }
});

// ========================================
// ACTION HANDLERS
// ========================================

function handleInsightAction(action) {
    console.log('Action clicked:', action);
    alert(`AI Recommendation: ${action}\n\nThis would typically trigger:\n- Automated workflow\n- Staff notification\n- Task creation`);
}

function viewInsightDetails(index) {
    console.log('Viewing details for insight:', index);
    alert('Detailed insight analytics would be displayed in a modal or separate page.');
}

// Export functions for global use
window.initializeAIInsights = initializeAIInsights;
window.startAutoRefresh = startAutoRefresh;
window.stopAutoRefresh = stopAutoRefresh;
window.handleInsightAction = handleInsightAction;
window.viewInsightDetails = viewInsightDetails;
