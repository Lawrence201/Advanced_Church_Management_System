// Finance Data Loader - Fetches data from database and populates the page
const API_URL = 'get_finance_data.php';

// Format currency
function formatCurrency(amount) {
    // Ensure amount is a number, default to 0 if invalid
    const numAmount = parseFloat(amount) || 0;
    return '₵' + numAmount.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

// Format date
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { month: '2-digit', day: '2-digit', year: 'numeric' });
}

// Load financial stats for cards
async function loadFinancialStats() {
    try {
        const response = await fetch(`${API_URL}?type=stats`);
        const result = await response.json();
        
        if (result.success) {
            const data = result.data;
            
            // Update offerings card with safe defaults
            const offeringsCard = document.querySelector('.zzamp_statcard.zzamp_cardblue');
            if (offeringsCard) {
                const offeringsTotal = parseFloat(data.offerings?.total) || 0;
                const offeringsChange = parseFloat(data.offerings?.change) || 0;
                offeringsCard.querySelector('.zzamp_statval').textContent = formatCurrency(offeringsTotal);
                const changeElem = offeringsCard.querySelector('.zzamp_statchg');
                changeElem.textContent = `${offeringsChange >= 0 ? '+' : ''}${offeringsChange}% from last month`;
                changeElem.className = offeringsChange >= 0 ? 'zzamp_statchg positive' : 'zzamp_statchg negative';
            }
            
            // Update tithes card with safe defaults
            const tithesCard = document.querySelector('.zzamp_statcard.zzamp_cardgreen');
            if (tithesCard) {
                const tithesTotal = parseFloat(data.tithes?.total) || 0;
                const tithesChange = parseFloat(data.tithes?.change) || 0;
                tithesCard.querySelector('.zzamp_statval').textContent = formatCurrency(tithesTotal);
                const changeElem = tithesCard.querySelector('.zzamp_statchg');
                changeElem.textContent = `${tithesChange >= 0 ? '+' : ''}${tithesChange}% from last month`;
                changeElem.className = tithesChange >= 0 ? 'zzamp_statchg positive' : 'zzamp_statchg negative';
            }
            
            // Update project offerings card with safe defaults
            const projectCard = document.querySelector('.zzamp_statcard.zzamp_cardyellow');
            if (projectCard) {
                const projectTotal = parseFloat(data.project_offerings?.total) || 0;
                const projectChange = parseFloat(data.project_offerings?.change) || 0;
                projectCard.querySelector('.zzamp_statval').textContent = formatCurrency(projectTotal);
                const changeElem = projectCard.querySelector('.zzamp_statchg');
                changeElem.textContent = `${projectChange >= 0 ? '+' : ''}${projectChange}% from last month`;
                changeElem.className = projectChange >= 0 ? 'zzamp_statchg positive' : 'zzamp_statchg negative';
            }
            
            // Update welfare card with safe defaults
            const welfareCard = document.querySelector('.zzamp_statcard.zzamp_cardpink');
            if (welfareCard) {
                const welfareTotal = parseFloat(data.welfare?.total) || 0;
                const welfareChange = parseFloat(data.welfare?.change) || 0;
                welfareCard.querySelector('.zzamp_statval').textContent = formatCurrency(welfareTotal);
                const changeElem = welfareCard.querySelector('.zzamp_statchg');
                changeElem.textContent = `${welfareChange >= 0 ? '+' : ''}${welfareChange}% from last month`;
                changeElem.className = welfareChange >= 0 ? 'zzamp_statchg positive' : 'zzamp_statchg negative';
            }
            
            // Update expenses card with safe defaults
            const expensesCard = document.querySelector('.zzamp_statcard.zzamp_cardgray');
            if (expensesCard) {
                const expensesTotal = parseFloat(data.expenses?.total) || 0;
                const expensesChange = parseFloat(data.expenses?.change) || 0;
                expensesCard.querySelector('.zzamp_statval').textContent = formatCurrency(expensesTotal);
                const changeElem = expensesCard.querySelector('.zzamp_statchg');
                changeElem.textContent = `${expensesChange >= 0 ? '+' : ''}${expensesChange}% from last month`;
                // Note: for expenses, positive change is bad
                changeElem.className = expensesChange < 0 ? 'zzamp_statchg positive' : 'zzamp_statchg negative';
            }
        }
    } catch (error) {
        console.error('Error loading financial stats:', error);
    }
}

// Load recent transactions
async function loadRecentTransactions() {
    try {
        const response = await fetch(`${API_URL}?type=recent_transactions`);
        const result = await response.json();
        
        if (result.success) {
            const tbody = document.querySelector('#overviewContent .wump_txntable tbody');
            if (!tbody) return;
            
            tbody.innerHTML = '';
            
            result.data.forEach(transaction => {
                const isIncome = transaction.category === 'income';
                const amountClass = isIncome ? 'wump_amtpos' : 'wump_amtneg';
                const amountPrefix = isIncome ? '+' : '-';
                
                const row = document.createElement('tr');
                row.className = 'wump_txnrow';
                row.onclick = () => openTransactionModal(
                    transaction.transaction_id,
                    formatDate(transaction.date),
                    transaction.type,
                    transaction.member,
                    transaction.category,
                    amountPrefix + formatCurrency(transaction.amount)
                );
                
                row.innerHTML = `
                    <td class="wump_txnid">${transaction.transaction_id}</td>
                    <td>${formatDate(transaction.date)}</td>
                    <td>${transaction.type}</td>
                    <td>${transaction.member}</td>
                    <td><span class="wump_catbadge ${transaction.category}">${transaction.category}</span></td>
                    <td class="${amountClass}">${amountPrefix}${formatCurrency(transaction.amount)}</td>
                `;
                
                tbody.appendChild(row);
            });
        }
    } catch (error) {
        console.error('Error loading recent transactions:', error);
    }
}

// Load offerings data with custom date range
async function loadOfferingsDataCustom(startDate, endDate) {
    try {
        const response = await fetch(`${API_URL}?type=offerings&range=custom&start_date=${startDate}&end_date=${endDate}`);
        const result = await response.json();
        
        if (result.success) {
            const tbody = document.getElementById('offeringsList');
            if (!tbody) return;
            
            tbody.innerHTML = '';
            
            if (result.data.offerings.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 20px;">No offerings recorded for this date range</td></tr>';
                return;
            }
            
            result.data.offerings.forEach(offering => {
                const row = document.createElement('tr');
                row.className = 'eorp_exprow';
                
                row.innerHTML = `
                    <td>${formatDate(offering.date)}</td>
                    <td>
                        <div class="eorp_expdesc">
                            <div class="eorp_exptit">${offering.service_type}</div>
                            <div class="eorp_expnote">${offering.notes || 'No notes'}</div>
                        </div>
                    </td>
                    <td>${offering.service_time || 'N/A'}</td>
                    <td class="wump_amtpos">${formatCurrency(offering.amount_collected)}</td>
                    <td><span class="borp_paymeth">${offering.collection_method}</span></td>
                    <td>${offering.counted_by || 'N/A'}</td>
                    <td><span class="borp_statbadge borp_statpaid">${offering.status}</span></td>
                    <td>
                        <div class="gorp_actbtns">
                            <button class="borp_acticon" onclick="viewOfferingDetails('${offering.transaction_id}')" title="View Details">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </button>
                            <button class="borp_acticon" onclick="editOffering('${offering.transaction_id}')" title="Edit">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                            </button>
                            <button class="borp_acticon gorp_delbtn" onclick="deleteOffering('${offering.transaction_id}')" title="Delete">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="3 6 5 6 21 6"></polyline>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                </svg>
                            </button>
                        </div>
                    </td>
                `;
                
                tbody.appendChild(row);
            });
            
            // Update summary cards
            const summary = result.data.summary;
            updateOfferingsSummary(summary, 'custom');
        }
    } catch (error) {
        console.error('Error loading custom offerings:', error);
    }
}

// Load offerings data
async function loadOfferingsData(dateRange = 'month') {
    try {
        console.log('=== Loading Offerings Data ===');
        console.log('Date Range:', dateRange);
        console.log('API URL:', `${API_URL}?type=offerings&range=${dateRange}`);

        const response = await fetch(`${API_URL}?type=offerings&range=${dateRange}`);
        const result = await response.json();

        console.log('API Response:', result);

        if (result.success) {
            const tbody = document.getElementById('offeringsList');
            if (!tbody) {
                console.error('offeringsList tbody not found!');
                return;
            }

            tbody.innerHTML = '';

            console.log('Offerings count:', result.data.offerings.length);
            console.log('Summary:', result.data.summary);

            if (result.data.offerings.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 20px;">No offerings recorded</td></tr>';
                console.warn('No offerings found for date range:', dateRange);
                // Still update summary even with no data
                updateOfferingsSummary(result.data.summary, dateRange);
                return;
            }

            console.log('Loading offerings:', result.data.offerings.length, 'records');
            result.data.offerings.forEach(offering => {
                console.log('Creating row for offering:', offering.transaction_id);
                const row = document.createElement('tr');
                row.className = 'eorp_exprow';
                
                row.innerHTML = `
                    <td>${formatDate(offering.date)}</td>
                    <td>
                        <div class="eorp_expdesc">
                            <div class="eorp_exptit">${offering.service_type}</div>
                            <div class="eorp_expnote">${offering.notes || 'No notes'}</div>
                        </div>
                    </td>
                    <td>${offering.service_time || 'N/A'}</td>
                    <td class="wump_amtpos">${formatCurrency(offering.amount_collected)}</td>
                    <td><span class="borp_paymeth">${offering.collection_method}</span></td>
                    <td>${offering.counted_by || 'N/A'}</td>
                    <td><span class="borp_statbadge borp_statpaid">${offering.status}</span></td>
                    <td>
                        <div class="gorp_actbtns">
                            <button class="borp_acticon" onclick="viewOfferingDetails('${offering.transaction_id}')" title="View Details">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </button>
                            <button class="borp_acticon" onclick="editOffering('${offering.transaction_id}', '${offering.date}', '${offering.service_type}', '${offering.service_time}', '${offering.amount_collected}', '${offering.collection_method}', '${offering.counted_by}', '${offering.notes}')" title="Edit">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                            </button>
                            <button class="borp_acticon gorp_delbtn" onclick="deleteOffering('${offering.transaction_id}')" title="Delete">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="3 6 5 6 21 6"></polyline>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                </svg>
                            </button>
                        </div>
                    </td>
                `;
                
                tbody.appendChild(row);
            });
            
            // Update summary cards
            const summary = result.data.summary;
            updateOfferingsSummary(summary, dateRange);
        }
    } catch (error) {
        console.error('Error loading offerings:', error);
    }
}

// Load project offerings data with custom date range
async function loadProjectOfferingsDataCustom(startDate, endDate) {
    try {
        const response = await fetch(`${API_URL}?type=project_offerings&range=custom&start_date=${startDate}&end_date=${endDate}`);
        const result = await response.json();
        
        if (result.success) {
            const tbody = document.getElementById('projectOfferingsList');
            if (!tbody) return;
            tbody.innerHTML = '';
            
            if (result.data.project_offerings.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 20px;">No project offerings recorded for this date range</td></tr>';
            } else {
                result.data.project_offerings.forEach(offering => {
                    const row = document.createElement('tr');
                    row.className = 'eorp_exprow';
                    row.innerHTML = `
                        <td>${formatDate(offering.date)}</td>
                        <td>
                            <div class="eorp_expdesc">
                                <div class="eorp_exptit">${offering.service_type}</div>
                                <div class="eorp_expnote">${offering.project_name}</div>
                            </div>
                        </td>
                        <td>${offering.service_time || 'N/A'}</td>
                        <td class="wump_amtpos">${formatCurrency(offering.amount_collected)}</td>
                        <td><span class="borp_paymeth">${offering.collection_method}</span></td>
                        <td>${offering.counted_by || 'N/A'}</td>
                        <td><span class="borp_statbadge borp_statpaid">${offering.status}</span></td>
                        <td>
                            <div class="gorp_actbtns">
                                <button class="borp_acticon" onclick="viewProjectOfferingDetails('${offering.transaction_id}')" title="View Details">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                </button>
                                <button class="borp_acticon" onclick="editProjectOffering('${offering.transaction_id}')" title="Edit">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                </button>
                                <button class="borp_acticon gorp_delbtn" onclick="deleteProjectOffering('${offering.transaction_id}')" title="Delete">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="3 6 5 6 21 6"></polyline>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    `;
                    tbody.appendChild(row);
                });
            }
            updateProjectOfferingsSummary(result.data.summary, 'custom');
        }
    } catch (error) {
        console.error('Error loading custom project offerings:', error);
    }
}

// Load project offerings data
async function loadProjectOfferingsData(dateRange = 'month') {
    try {
        const response = await fetch(`${API_URL}?type=project_offerings&range=${dateRange}`);
        const result = await response.json();
        
        if (result.success) {
            const tbody = document.getElementById('projectOfferingsList');
            if (!tbody) return;
            
            tbody.innerHTML = '';
            
            if (result.data.project_offerings.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 20px;">No project offerings recorded</td></tr>';
                return;
            }
            
            result.data.project_offerings.forEach(offering => {
                const row = document.createElement('tr');
                row.className = 'eorp_exprow';
                
                row.innerHTML = `
                    <td>${formatDate(offering.date)}</td>
                    <td>
                        <div class="eorp_expdesc">
                            <div class="eorp_exptit">${offering.service_type}</div>
                            <div class="eorp_expnote">${offering.project_name}</div>
                        </div>
                    </td>
                    <td>${offering.service_time || 'N/A'}</td>
                    <td class="wump_amtpos">${formatCurrency(offering.amount_collected)}</td>
                    <td><span class="borp_paymeth">${offering.collection_method}</span></td>
                    <td>${offering.counted_by || 'N/A'}</td>
                    <td><span class="borp_statbadge borp_statpaid">${offering.status}</span></td>
                    <td>
                        <div class="gorp_actbtns">
                            <button class="borp_acticon" onclick="viewProjectOfferingDetails('${offering.transaction_id}')" title="View Details">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </button>
                            <button class="borp_acticon" onclick="editProjectOffering('${offering.transaction_id}')" title="Edit">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                            </button>
                            <button class="borp_acticon gorp_delbtn" onclick="deleteProjectOffering('${offering.transaction_id}')" title="Delete">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="3 6 5 6 21 6"></polyline>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                </svg>
                            </button>
                        </div>
                    </td>
                `;
                
                tbody.appendChild(row);
            });
            
            // Update summary cards
            updateProjectOfferingsSummary(result.data.summary, dateRange);
        }
    } catch (error) {
        console.error('Error loading project offerings:', error);
    }
}

// Update project offerings summary cards
function updateProjectOfferingsSummary(summary, dateRange) {
    const projectContent = document.getElementById('projectOfferingContent');
    if (!projectContent) return;

    const totalAmount = parseFloat(summary?.total_amount) || 0;
    const totalCount = parseInt(summary?.total_count) || 0;
    const avgAmount = parseFloat(summary?.avg_amount) || 0;
    const activeProjects = parseInt(summary?.active_projects) || 0;

    // Update all cards with the summary data from the selected date range
    // Card 0: Total Amount
    const todayAmountElem = document.getElementById('projectOfferingsTodayAmount');
    const todayMetaElem = document.getElementById('projectOfferingsTodayMeta');
    if (todayAmountElem) {
        todayAmountElem.textContent = formatCurrency(totalAmount);
    }
    if (todayMetaElem) {
        todayMetaElem.textContent = `${totalCount} projects recorded`;
    }

    // Card 1: Total Amount (same data)
    const weekAmountElem = document.getElementById('projectOfferingsWeekAmount');
    const weekMetaElem = document.getElementById('projectOfferingsWeekMeta');
    if (weekAmountElem) {
        weekAmountElem.textContent = formatCurrency(totalAmount);
    }
    if (weekMetaElem) {
        weekMetaElem.textContent = `${totalCount} total projects`;
    }

    // Card 2: Total Amount with Average
    const monthAmountElem = document.getElementById('projectOfferingsMonthAmount');
    const monthMetaElem = document.getElementById('projectOfferingsMonthMeta');
    if (monthAmountElem) {
        monthAmountElem.textContent = formatCurrency(totalAmount);
    }
    if (monthMetaElem) {
        if (totalCount > 0) {
            monthMetaElem.textContent = `Average: ${formatCurrency(avgAmount)}/project`;
        } else {
            monthMetaElem.textContent = 'No projects recorded';
        }
    }

    // Card 3: Active Projects count
    const activeCountElem = document.getElementById('projectOfferingsActiveCount');
    const activeMetaElem = document.getElementById('projectOfferingsActiveMeta');
    if (activeCountElem) {
        activeCountElem.textContent = activeProjects;
    }
}

// Fetch today's project offerings
async function fetchTodayProjectOfferings() {
    try {
        const response = await fetch(`${API_URL}?type=project_offerings&range=today`);
        const result = await response.json();
        
        if (result.success) {
            const projectContent = document.getElementById('projectOfferingContent');
            if (!projectContent) return;
            
            const todayCard = projectContent.querySelector('.yump_offcard.cardbluelight');
            if (todayCard) {
                const amountElem = todayCard.querySelector('.yump_offamt');
                const metaElem = todayCard.querySelector('.yump_offmeta');
                
                if (amountElem) {
                    const totalAmount = parseFloat(result.data.summary?.total_amount) || 0;
                    amountElem.textContent = formatCurrency(totalAmount);
                }
                if (metaElem) {
                    const totalCount = parseInt(result.data.summary?.total_count) || 0;
                    metaElem.textContent = `${totalCount} projects recorded`;
                }
            }
        }
    } catch (error) {
        console.error('Error loading today project offerings:', error);
        // Set default zero values on error
        const projectContent = document.getElementById('projectOfferingContent');
        if (projectContent) {
            const todayCard = projectContent.querySelector('.yump_offcard.cardbluelight');
            if (todayCard) {
                const amountElem = todayCard.querySelector('.yump_offamt');
                if (amountElem) amountElem.textContent = formatCurrency(0);
            }
        }
    }
}

// Load tithes data with custom date range
async function loadTithesDataCustom(startDate, endDate) {
    try {
        const response = await fetch(`${API_URL}?type=tithes&range=custom&start_date=${startDate}&end_date=${endDate}`);
        const result = await response.json();
        if (result.success) {
            const tbody = document.getElementById('cpsContributorsList');
            if (tbody) {
                tbody.innerHTML = result.data.tithes.length === 0 ? 
                    '<tr><td colspan="7" style="text-align: center; padding: 20px;">No tithes recorded for this date range</td></tr>' : '';
                result.data.tithes.forEach(tithe => {
                    // Same rendering logic as loadTithesData
                });
                updateTithesSummary(result.data.summary);
            }
        }
    } catch (error) {
        console.error('Error loading custom tithes:', error);
    }
}

// Load welfare data with custom date range
async function loadWelfareDataCustom(startDate, endDate) {
    try {
        const response = await fetch(`${API_URL}?type=welfare&range=custom&start_date=${startDate}&end_date=${endDate}`);
        const result = await response.json();
        if (result.success) {
            const tbody = document.getElementById('welfareContributorsList');
            if (tbody) {
                tbody.innerHTML = result.data.welfare.length === 0 ? 
                    '<tr><td colspan="7" style="text-align: center; padding: 20px;">No welfare contributions recorded for this date range</td></tr>' : '';
                result.data.welfare.forEach(welfare => {
                    // Same rendering logic as loadWelfareData
                });
                updateWelfareSummary(result.data.summary);
            }
        }
    } catch (error) {
        console.error('Error loading custom welfare:', error);
    }
}

// Load expenses data with custom date range
async function loadExpensesDataCustom(startDate, endDate) {
    try {
        const response = await fetch(`${API_URL}?type=expenses&range=custom&start_date=${startDate}&end_date=${endDate}`);
        const result = await response.json();
        if (result.success) {
            const tbody = document.getElementById('expensesList');
            if (tbody) {
                tbody.innerHTML = result.data.expenses.length === 0 ? 
                    '<tr><td colspan="9" style="text-align: center; padding: 20px;">No expenses recorded for this date range</td></tr>' : '';
                // Render expenses using same logic as loadExpensesData
            }
        }
    } catch (error) {
        console.error('Error loading custom expenses:', error);
    }
}

// Load tithes data
async function loadTithesData(dateRange = 'month') {
    try {
        const response = await fetch(`${API_URL}?type=tithes&range=${dateRange}`);
        const result = await response.json();
        
        if (result.success) {
            const tbody = document.getElementById('cpsContributorsList');
            if (!tbody) return;
            
            tbody.innerHTML = '';
            
            if (result.data.tithes.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 20px;">No tithes recorded</td></tr>';
                return;
            }
            
            result.data.tithes.forEach(tithe => {
                const memberName = tithe.member_name || 'Unknown';
                const memberEmail = tithe.member_email || 'N/A';
                const initials = memberName.split(' ').map(n => n[0]).join('').toUpperCase();
                const photoPath = tithe.member_photo;
                
                const row = document.createElement('tr');
                row.className = 'borp_cpsrow';
                
                // Create avatar HTML - use photo if available, otherwise use initials
                let avatarHTML = '';
                if (photoPath && photoPath !== 'null' && photoPath !== '') {
                    // Use img tag like Members page does
                    avatarHTML = `<div class="borp_memavatar" style="padding: 0; overflow: hidden;">
                        <img src="../Add_Members/${photoPath}" alt="${memberName}" 
                             style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;" 
                             onerror="this.style.display='none'; this.parentElement.innerHTML='${initials}'; this.parentElement.style.padding='';">
                    </div>`;
                } else {
                    avatarHTML = `<div class="borp_memavatar">${initials}</div>`;
                }
                
                row.innerHTML = `
                    <td><input type="checkbox" class="member-checkbox"></td>
                    <td>
                        <div class="borp_meminfo">
                            ${avatarHTML}
                            <div>
                                <div class="borp_memname">${memberName}</div>
                                <div class="borp_mememail">${memberEmail}</div>
                            </div>
                        </div>
                    </td>
                    <td class="borp_amtcell">${formatCurrency(tithe.amount)}</td>
                    <td>${formatDate(tithe.date)}</td>
                    <td><span class="borp_paymeth">${tithe.payment_method}</span></td>
                    <td><span class="borp_statbadge borp_statpaid">${tithe.status}</span></td>
                    <td>
                        <button class="borp_acticon" onclick="viewCPSReceipt('${memberName}')" title="View Receipt">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </td>
                `;
                
                tbody.appendChild(row);
            });
            
            // Update summary
            const summary = result.data.summary;
            updateTithesSummary(summary);
        }
    } catch (error) {
        console.error('Error loading tithes:', error);
    }
}

// Load welfare data
async function loadWelfareData(dateRange = 'month') {
    try {
        const response = await fetch(`${API_URL}?type=welfare&range=${dateRange}`);
        const result = await response.json();
        
        if (result.success) {
            const tbody = document.getElementById('welfareContributorsList');
            if (!tbody) return;
            
            tbody.innerHTML = '';
            
            if (result.data.welfare.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 20px;">No welfare contributions recorded</td></tr>';
                return;
            }
            
            result.data.welfare.forEach(welfare => {
                const memberName = welfare.member_name || 'Unknown';
                const memberEmail = welfare.member_email || 'N/A';
                const initials = memberName.split(' ').map(n => n[0]).join('').toUpperCase();
                const photoPath = welfare.member_photo;
                
                const row = document.createElement('tr');
                row.className = 'borp_cpsrow';
                
                // Create avatar HTML - use photo if available, otherwise use initials
                let avatarHTML = '';
                if (photoPath && photoPath !== 'null' && photoPath !== '') {
                    // Use img tag like Members page does
                    avatarHTML = `<div class="borp_memavatar" style="padding: 0; overflow: hidden;">
                        <img src="../Add_Members/${photoPath}" alt="${memberName}" 
                             style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;" 
                             onerror="this.style.display='none'; this.parentElement.innerHTML='${initials}'; this.parentElement.style.padding='';">
                    </div>`;
                } else {
                    avatarHTML = `<div class="borp_memavatar">${initials}</div>`;
                }
                
                row.innerHTML = `
                    <td><input type="checkbox" class="member-checkbox"></td>
                    <td>
                        <div class="borp_meminfo">
                            ${avatarHTML}
                            <div>
                                <div class="borp_memname">${memberName}</div>
                                <div class="borp_mememail">${memberEmail}</div>
                            </div>
                        </div>
                    </td>
                    <td class="borp_amtcell">${formatCurrency(welfare.amount)}</td>
                    <td>${formatDate(welfare.date)}</td>
                    <td><span class="borp_paymeth">${welfare.payment_method}</span></td>
                    <td><span class="borp_statbadge borp_statpaid">${welfare.status}</span></td>
                    <td>
                        <button class="borp_acticon" onclick="viewWelfareReceipt('${memberName}')" title="View Receipt">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </td>
                `;
                
                tbody.appendChild(row);
            });
            
            // Update summary
            const summary = result.data.summary;
            updateWelfareSummary(summary);
        }
    } catch (error) {
        console.error('Error loading welfare:', error);
    }
}

// Update expenses summary cards
function updateExpensesSummary(data) {
    // Total Expenses
    const totalExpensesElem = document.getElementById('totalExpensesAmount');
    if (totalExpensesElem) {
        const totalAmount = parseFloat(data.summary?.total_amount) || 0;
        totalExpensesElem.textContent = formatCurrency(totalAmount);
    }
    
    // Total Expenses Change (would need previous month data from backend)
    const totalExpensesChange = document.getElementById('totalExpensesChange');
    if (totalExpensesChange) {
        // This would come from backend comparison
        totalExpensesChange.textContent = '0% from last month';
        totalExpensesChange.className = 'dorp_expchg neutral';
    }
    
    // Pending Approval
    const pendingCountElem = document.getElementById('pendingCount');
    const pendingValueElem = document.getElementById('pendingValue');
    if (pendingCountElem) {
        const pendingCount = parseInt(data.summary?.pending_count) || 0;
        pendingCountElem.textContent = pendingCount;
    }
    if (pendingValueElem) {
        // Calculate pending total from expenses list
        let pendingTotal = 0;
        if (data.expenses && Array.isArray(data.expenses)) {
            pendingTotal = data.expenses
                .filter(exp => exp.status === 'pending')
                .reduce((sum, exp) => sum + (parseFloat(exp.amount) || 0), 0);
        }
        pendingValueElem.textContent = `${formatCurrency(pendingTotal)} total value`;
    }
    
    // Budget Remaining (uses budget from database or default)
    const budgetRemainingElem = document.getElementById('budgetRemaining');
    const budgetTotalElem = document.getElementById('budgetTotal');
    const budgetProgressElem = document.getElementById('budgetProgress');
    
    const monthlyBudget = window.currentBudget || 50000; // From budget settings or default
    const totalExpenses = parseFloat(data.summary?.total_amount) || 0;
    const remaining = monthlyBudget - totalExpenses;
    const percentUsed = monthlyBudget > 0 ? ((totalExpenses / monthlyBudget) * 100) : 0;
    
    if (budgetRemainingElem) {
        budgetRemainingElem.textContent = formatCurrency(Math.max(0, remaining));
    }
    if (budgetTotalElem) {
        budgetTotalElem.textContent = `Out of ${formatCurrency(monthlyBudget)}`;
    }
    if (budgetProgressElem) {
        budgetProgressElem.style.width = `${Math.min(100, percentUsed)}%`;
    }
    
    // Largest Category
    const largestCategoryElem = document.getElementById('largestCategory');
    const largestCategoryValueElem = document.getElementById('largestCategoryValue');
    
    if (data.categories && data.categories.length > 0) {
        const largest = data.categories[0]; // Already sorted by total DESC in PHP
        const largestAmount = parseFloat(largest.total) || 0;
        const totalAmount = parseFloat(data.summary?.total_amount) || 0;
        const percentage = totalAmount > 0 ? ((largestAmount / totalAmount) * 100).toFixed(1) : 0;
        
        if (largestCategoryElem) {
            largestCategoryElem.textContent = largest.category || 'None';
        }
        if (largestCategoryValueElem) {
            largestCategoryValueElem.textContent = `${formatCurrency(largestAmount)} (${percentage}%)`;
        }
    } else {
        if (largestCategoryElem) largestCategoryElem.textContent = 'None';
        if (largestCategoryValueElem) largestCategoryValueElem.textContent = '₵0.00 (0%)';
    }
}

// Load expenses data
async function loadExpensesData(dateRange = 'month') {
    try {
        const response = await fetch(`${API_URL}?type=expenses&range=${dateRange}`);
        const result = await response.json();
        
        if (result.success) {
            const tbody = document.getElementById('expensesList');
            if (!tbody) return;
            
            tbody.innerHTML = '';
            
            if (result.data.expenses.length === 0) {
                tbody.innerHTML = '<tr><td colspan="9" style="text-align: center; padding: 20px;">No expenses recorded</td></tr>';
                // Still update summary even with no expenses
                updateExpensesSummary(result.data);
                return;
            }
            
            result.data.expenses.forEach(expense => {
                const row = document.createElement('tr');
                row.className = 'eorp_exprow';
                row.setAttribute('data-category', expense.category);
                row.setAttribute('data-status', expense.status);
                
                const statusClass = expense.status === 'approved' ? 'forp_statapp' : 
                                   expense.status === 'pending' ? 'forp_statpend' : 'forp_statsrej';
                
                row.innerHTML = `
                    <td>${formatDate(expense.date)}</td>
                    <td>
                        <div class="eorp_expdesc">
                            <div class="eorp_exptit">${expense.description}</div>
                            <div class="eorp_expnote">${expense.notes || 'No notes'}</div>
                        </div>
                    </td>
                    <td><span class="forp_cattag forp_catutil">${expense.category}</span></td>
                    <td>${expense.vendor_payee || 'N/A'}</td>
                    <td class="wump_amtneg">${formatCurrency(expense.amount)}</td>
                    <td><span class="borp_paymeth">${expense.payment_method}</span></td>
                    <td><span class="borp_statbadge ${statusClass}">${expense.status}</span></td>
                    <td>
                        <button class="gorp_receiptbtn" onclick="viewReceipt('${expense.transaction_id}')" title="View Receipt">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                            </svg>
                        </button>
                    </td>
                    <td>
                        <div class="gorp_actbtns">
                            ${expense.status === 'pending' ? `
                                <button class="borp_acticon gorp_appbtn" onclick="approveExpense('${expense.transaction_id}')" title="Approve">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                </button>
                                <button class="borp_acticon gorp_rejbtn" onclick="rejectExpense('${expense.transaction_id}')" title="Reject">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="18" y1="6" x2="6" y2="18"></line>
                                        <line x1="6" y1="6" x2="18" y2="18"></line>
                                    </svg>
                                </button>
                            ` : `
                                <button class="borp_acticon" onclick="editExpense('${expense.transaction_id}')" title="Edit">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                </button>
                                <button class="borp_acticon gorp_delbtn" onclick="deleteExpense('${expense.transaction_id}')" title="Delete">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="3 6 5 6 21 6"></polyline>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                    </svg>
                                </button>
                            `}
                        </div>
                    </td>
                `;
                
                tbody.appendChild(row);
            });
            
            // Update expenses summary cards
            updateExpensesSummary(result.data);
        }
    } catch (error) {
        console.error('Error loading expenses:', error);
    }
}

function updateOfferingsSummary(summary, dateRange) {
    // Update cards in offerings section
    const offeringsContent = document.getElementById('offeringsContent');
    if (!offeringsContent) return;

    const totalAmount = parseFloat(summary?.total_amount) || 0;
    const totalCount = parseInt(summary?.total_count) || 0;
    const avgAmount = parseFloat(summary?.avg_amount) || 0;

    // Update all cards with the summary data from the selected date range
    // Card 0: Total Amount
    const todayAmountElem = document.getElementById('offeringsTodayAmount');
    const todayMetaElem = document.getElementById('offeringsTodayMeta');
    if (todayAmountElem) {
        todayAmountElem.textContent = formatCurrency(totalAmount);
    }
    if (todayMetaElem) {
        todayMetaElem.textContent = `${totalCount} services recorded`;
    }

    // Card 1: Total Amount (same data, different card)
    const weekAmountElem = document.getElementById('offeringsWeekAmount');
    const weekMetaElem = document.getElementById('offeringsWeekMeta');
    if (weekAmountElem) {
        weekAmountElem.textContent = formatCurrency(totalAmount);
    }
    if (weekMetaElem) {
        weekMetaElem.textContent = `${totalCount} total services`;
    }

    // Card 2: Total Amount with Average
    const monthAmountElem = document.getElementById('offeringsMonthAmount');
    const monthMetaElem = document.getElementById('offeringsMonthMeta');
    if (monthAmountElem) {
        monthAmountElem.textContent = formatCurrency(totalAmount);
    }
    if (monthMetaElem) {
        if (totalCount > 0) {
            monthMetaElem.textContent = `Average: ${formatCurrency(avgAmount)}/service`;
        } else {
            monthMetaElem.textContent = 'No offerings recorded';
        }
    }

    // Card 3: Special offerings
    fetchSpecialOfferingsForRange(dateRange);
}

// Fetch today's offerings separately
async function fetchTodayOfferings() {
    try {
        const response = await fetch(`${API_URL}?type=offerings&range=today`);
        const result = await response.json();
        
        if (result.success) {
            const offeringsContent = document.getElementById('offeringsContent');
            if (!offeringsContent) return;
            
            const todayCard = offeringsContent.querySelector('.yump_offcard.cardbluelight');
            if (todayCard) {
                const amountElem = todayCard.querySelector('.yump_offamt');
                const metaElem = todayCard.querySelector('.yump_offmeta');
                
                if (amountElem) {
                    const totalAmount = parseFloat(result.data.summary?.total_amount) || 0;
                    amountElem.textContent = formatCurrency(totalAmount);
                }
                if (metaElem) {
                    const totalCount = parseInt(result.data.summary?.total_count) || 0;
                    metaElem.textContent = `${totalCount} services recorded`;
                }
            }
        }
    } catch (error) {
        console.error('Error loading today offerings:', error);
        // Set default zero values on error
        const offeringsContent = document.getElementById('offeringsContent');
        if (offeringsContent) {
            const todayCard = offeringsContent.querySelector('.yump_offcard.cardbluelight');
            if (todayCard) {
                const amountElem = todayCard.querySelector('.yump_offamt');
                if (amountElem) amountElem.textContent = formatCurrency(0);
            }
        }
    }
}

// Fetch special offerings (from project offerings table or specific service type)
async function fetchSpecialOfferings() {
    // Default to 'all' range for backward compatibility
    await fetchSpecialOfferingsForRange('all');
}

// Fetch special offerings for a specific date range
async function fetchSpecialOfferingsForRange(dateRange = 'month') {
    try {
        // Get special offerings from the specified range
        const response = await fetch(`${API_URL}?type=offerings&range=${dateRange}`);
        const result = await response.json();

        if (result.success) {
            const offeringsContent = document.getElementById('offeringsContent');
            if (!offeringsContent) return;

            // Calculate special offerings (those marked as "Special Offering" service type)
            let specialTotal = 0;
            let specialName = 'Various projects';

            result.data.offerings.forEach(offering => {
                if (offering.service_type && offering.service_type.toLowerCase().includes('special')) {
                    specialTotal += parseFloat(offering.amount_collected);
                }
            });

            // If no special offerings from regular offerings, check project offerings
            if (specialTotal === 0) {
                const projectResponse = await fetch(`${API_URL}?type=project_offerings&range=${dateRange}`);
                const projectResult = await projectResponse.json();
                
                if (projectResult.success && projectResult.data.project_offerings.length > 0) {
                    // Get the most recent or largest project
                    const sortedProjects = projectResult.data.project_offerings.sort((a, b) => 
                        parseFloat(b.amount_collected || 0) - parseFloat(a.amount_collected || 0)
                    );
                    if (sortedProjects[0]) {
                        specialTotal = parseFloat(sortedProjects[0].amount_collected) || 0;
                        specialName = sortedProjects[0].project_name || 'Building project';
                    }
                }
            }
            
            // Update special offerings card using IDs
            const specialAmountElem = document.getElementById('offeringsSpecialAmount');
            const specialMetaElem = document.getElementById('offeringsSpecialMeta');

            if (specialAmountElem) {
                specialAmountElem.textContent = formatCurrency(specialTotal);
            }
            if (specialMetaElem) {
                specialMetaElem.textContent = specialName;
            }
        }
    } catch (error) {
        console.error('Error loading special offerings:', error);
    }
}

function updateTithesSummary(summary) {
    // Update tithe summary cards with safe defaults
    const memberCountCard = document.querySelector('#cpsContent .yump_offcard.cardbluelight .yump_offamt');
    if (memberCountCard) {
        const uniqueMembers = parseInt(summary?.unique_members) || 0;
        memberCountCard.textContent = uniqueMembers;
    }
    
    const totalAmountCard = document.querySelector('#cpsContent .yump_offcard.cardgreenlight .yump_offamt.cps-amount');
    if (totalAmountCard) {
        const totalAmount = parseFloat(summary?.total_amount) || 0;
        totalAmountCard.textContent = formatCurrency(totalAmount);
    }
    
    const avgCard = document.querySelector('#cpsContent .yump_offcard.cardyellowlight .yump_offamt.cps-amount');
    if (avgCard) {
        const uniqueMembers = parseInt(summary?.unique_members) || 0;
        const avgAmount = parseFloat(summary?.avg_amount) || 0;
        if (uniqueMembers > 0) {
            avgCard.textContent = formatCurrency(avgAmount);
        } else {
            avgCard.textContent = formatCurrency(0);
        }
    }
}

function updateWelfareSummary(summary) {
    // Update welfare summary cards with safe defaults
    const memberCountCard = document.querySelector('#welfareContent .yump_offcard.cardbluelight .yump_offamt.cps-amount');
    if (memberCountCard) {
        const uniqueMembers = parseInt(summary?.unique_members) || 0;
        memberCountCard.textContent = uniqueMembers;
    }
    
    const totalAmountCard = document.querySelector('#welfareContent .yump_offcard.cardgreenlight .yump_offamt.cps-amount');
    if (totalAmountCard) {
        const totalAmount = parseFloat(summary?.total_amount) || 0;
        totalAmountCard.textContent = formatCurrency(totalAmount);
    }
    
    const avgCard = document.querySelector('#welfareContent .yump_offcard.cardyellowlight .yump_offamt.cps-amount');
    if (avgCard) {
        const uniqueMembers = parseInt(summary?.unique_members) || 0;
        const avgAmount = parseFloat(summary?.avg_amount) || 0;
        if (uniqueMembers > 0) {
            avgCard.textContent = formatCurrency(avgAmount);
        } else {
            avgCard.textContent = formatCurrency(0);
        }
    }
}

// Load chart data for pie chart
async function loadFinanceCategoriesChart() {
    try {
        // Check if chart exists, if not wait and try again
        if (!window.categoriesChart) {
            console.log('Chart not ready yet, waiting...');
            setTimeout(loadFinanceCategoriesChart, 500);
            return;
        }
        
        const response = await fetch(`${API_URL}?type=stats`);
        const result = await response.json();
        
        if (result.success) {
            const data = result.data;
            
            // Calculate totals
            const titheTotal = parseFloat(data.tithes.total) || 0;
            const welfareTotal = parseFloat(data.welfare.total) || 0;
            const projectTotal = parseFloat(data.project_offerings.total) || 0;
            const offeringsTotal = parseFloat(data.offerings.total) || 0;
            const total = titheTotal + welfareTotal + projectTotal + offeringsTotal;
            
            if (total > 0) {
                // Calculate percentages
                const tithePercent = ((titheTotal / total) * 100).toFixed(1);
                const welfarePercent = ((welfareTotal / total) * 100).toFixed(1);
                const projectPercent = ((projectTotal / total) * 100).toFixed(1);
                const offeringsPercent = ((offeringsTotal / total) * 100).toFixed(1);
                
                // Update the pie chart
                window.categoriesChart.data.datasets[0].data = [
                    parseFloat(tithePercent),
                    parseFloat(welfarePercent),
                    parseFloat(projectPercent),
                    parseFloat(offeringsPercent)
                ];
                window.categoriesChart.update();
                console.log('Chart updated successfully');
            }
        }
    } catch (error) {
        console.error('Error loading chart data:', error);
    }
}

// Load line chart data (6 months trend)
async function loadFinancialTrendsChart() {
    try {
        // Get data for the last 6 months
        const months = [];
        const incomeData = [];
        const expenseData = [];
        
        for (let i = 5; i >= 0; i--) {
            const date = new Date();
            date.setMonth(date.getMonth() - i);
            const monthName = date.toLocaleString('default', { month: 'short' });
            months.push(monthName);
            
            // Fetch data for this month
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const startDate = `${year}-${month}-01`;
            const lastDay = new Date(year, date.getMonth() + 1, 0).getDate();
            const endDate = `${year}-${month}-${lastDay}`;
            
            // This would need a new endpoint or we calculate from overview
            // For now, let's use a simplified approach
            const response = await fetch(`${API_URL}?type=overview&range=month`);
            const result = await response.json();
            
            if (result.success) {
                incomeData.push(result.data.total_income);
                expenseData.push(result.data.total_expenses);
            } else {
                incomeData.push(0);
                expenseData.push(0);
            }
        }
        
        // Note: The line chart is SVG-based, not Chart.js, so we'd need to update the SVG points
        // For now, let's just update the tooltip data
        if (window.chartData) {
            months.forEach((month, index) => {
                window.chartData[month] = {
                    income: formatCurrency(incomeData[index]),
                    expenses: formatCurrency(expenseData[index])
                };
            });
        }
    } catch (error) {
        console.error('Error loading trends chart:', error);
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    console.log('Finance data loader initialized');

    // Load initial data - changed to 'all' to show all data by default
    loadFinancialStats();
    loadRecentTransactions();
    loadOfferingsData('all'); // Changed from 'month' to 'all'
    fetchSpecialOfferings(); // Load special offerings
    loadProjectOfferingsData('all'); // Changed from 'month' to 'all'
    loadTithesData('all'); // Changed from 'month' to 'all'
    loadWelfareData('all'); // Changed from 'month' to 'all'
    loadExpensesData('all'); // Changed from 'month' to 'all'

    // Load charts after a longer delay to ensure Chart.js and HTML scripts are loaded
    setTimeout(() => {
        console.log('Attempting to load charts...');
        loadFinanceCategoriesChart();
        loadFinancialTrendsChart();
    }, 2000);
});
