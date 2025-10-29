/**
 * Communication System JavaScript
 * Handles message sending, recipient selection, and UI interactions
 */

// Store selected channels and audience
let selectedChannels = ['email'];
let selectedAudience = {
    type: 'all',
    value: null,
    memberIds: []
};

// Store all members for autocomplete
let allMembersForMessage = [];
let selectedMembersArray = [];

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    loadDepartmentsForMessage();
    loadChurchGroupsForMessage();
    loadMinistriesForMessage();
    loadMembersForAutocomplete();
    setupEventListeners();
    updatePreview();
});

/**
 * Setup event listeners
 */
function setupEventListeners() {
    // Message preview updates
    const titleInput = document.getElementById('messageTitle');
    const contentInput = document.getElementById('messageContent');
    const audienceSelect = document.getElementById('audience');
    
    if (titleInput) titleInput.addEventListener('input', updatePreview);
    if (contentInput) contentInput.addEventListener('input', updatePreview);
    if (audienceSelect) audienceSelect.addEventListener('change', updatePreview);
}

/**
 * Toggle delivery channel
 */
function toggleChannel(channel) {
    const btn = document.getElementById(channel + 'Btn');
    if (!btn) return;
    
    btn.classList.toggle('active');
    
    // Update selected channels array
    if (btn.classList.contains('active')) {
        if (!selectedChannels.includes(channel)) {
            selectedChannels.push(channel);
        }
    } else {
        selectedChannels = selectedChannels.filter(c => c !== channel);
    }
    
    // Ensure at least one channel is selected
    if (selectedChannels.length === 0) {
        btn.classList.add('active');
        selectedChannels.push(channel);
        alert('At least one delivery channel must be selected');
    }
}

/**
 * Update message preview
 */
function updatePreview() {
    const title = document.getElementById('messageTitle')?.value || 'Message Title';
    const content = document.getElementById('messageContent')?.value || 'Your message content will appear here...';
    
    document.getElementById('previewTitle').textContent = title;
    document.getElementById('previewBody').textContent = content;
    updatePreviewDisplay();
}

/**
 * Send message now
 */
async function sendMessage() {
    // Validate inputs
    const title = document.getElementById('messageTitle').value.trim();
    const content = document.getElementById('messageContent').value.trim();
    
    if (!title) {
        alert('Please enter a message title');
        return;
    }
    
    if (!content) {
        alert('Please enter message content');
        return;
    }
    
    if (selectedChannels.length === 0) {
        alert('Please select at least one delivery channel');
        return;
    }
    
    // Get current audience type
    const audienceType = document.getElementById('audience').value;
    let audienceValue = null;
    let memberIds = [];
    
    // Determine audience value based on type
    switch(audienceType) {
        case 'department':
            audienceValue = document.getElementById('departmentSelect').value;
            if (!audienceValue) {
                alert('Please select a department');
                return;
            }
            break;
        case 'church_group':
            audienceValue = document.getElementById('churchGroupSelect').value;
            if (!audienceValue) {
                alert('Please select a church group');
                return;
            }
            break;
        case 'ministry':
            audienceValue = document.getElementById('ministrySelect').value;
            if (!audienceValue) {
                alert('Please select a ministry');
                return;
            }
            break;
        case 'others':
            if (selectedMembersArray.length === 0) {
                alert('Please select at least one member');
                return;
            }
            memberIds = selectedMembersArray.map(m => m.member_id);
            break;
    }
    
    // Prepare message data
    const messageData = {
        message_type: document.getElementById('messageType').value.toLowerCase().replace(' ', '_'),
        title: title,
        content: content,
        delivery_channels: selectedChannels,
        audience_type: audienceType === 'department' || audienceType === 'church_group' ? 'group' : audienceType,
        audience_value: audienceValue,
        member_ids: memberIds,
        action: 'send'
    };
    
    // Show loading state
    const sendBtn = event.target;
    const originalText = sendBtn.textContent;
    sendBtn.textContent = 'Sending...';
    sendBtn.disabled = true;
    
    try {
        const response = await fetch('send_message.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(messageData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Show success message
            showSuccessMessage(result);
            
            // Clear form
            clearMessageForm();
            
            // Show success notification
            const successDiv = document.getElementById('successMessage');
            if (successDiv) {
                successDiv.style.display = 'block';
                setTimeout(() => {
                    successDiv.style.display = 'none';
                }, 5000);
            }
        } else {
            alert('Error sending message: ' + result.error);
        }
    } catch (error) {
        alert('Failed to send message: ' + error.message);
        console.error('Send error:', error);
    } finally {
        sendBtn.textContent = originalText;
        sendBtn.disabled = false;
    }
}

/**
 * Schedule message for later
 */
async function scheduleMessage() {
    const title = document.getElementById('messageTitle').value.trim();
    const content = document.getElementById('messageContent').value.trim();
    
    if (!title || !content) {
        alert('Please fill in all required fields');
        return;
    }
    
    // Prompt for schedule time
    const scheduleTime = prompt('Enter schedule date and time (YYYY-MM-DD HH:MM:SS):', 
                                new Date(Date.now() + 86400000).toISOString().slice(0, 19).replace('T', ' '));
    
    if (!scheduleTime) return;
    
    const messageData = {
        message_type: document.getElementById('messageType').value,
        title: title,
        content: content,
        delivery_channels: selectedChannels,
        audience_type: selectedAudience.type,
        audience_value: selectedAudience.value,
        member_ids: selectedAudience.memberIds,
        action: 'schedule',
        scheduled_at: scheduleTime
    };
    
    try {
        const response = await fetch('send_message.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(messageData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert(`Message scheduled successfully for ${scheduleTime}`);
            clearMessageForm();
        } else {
            alert('Error: ' + result.error);
        }
    } catch (error) {
        alert('Failed to schedule message: ' + error.message);
    }
}

/**
 * Save draft
 */
async function saveDraft() {
    const title = document.getElementById('messageTitle').value.trim();
    const content = document.getElementById('messageContent').value.trim();
    
    if (!title && !content) {
        alert('Nothing to save');
        return;
    }
    
    const messageData = {
        message_type: document.getElementById('messageType').value,
        title: title || 'Untitled Draft',
        content: content || '',
        delivery_channels: selectedChannels,
        audience_type: selectedAudience.type,
        audience_value: selectedAudience.value,
        action: 'draft'
    };
    
    try {
        const response = await fetch('send_message.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(messageData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('Draft saved successfully');
        } else {
            alert('Error: ' + result.error);
        }
    } catch (error) {
        alert('Failed to save draft: ' + error.message);
    }
}

/**
 * Show success message with details
 */
function showSuccessMessage(result) {
    const message = `
        ✅ Message sent successfully!
        
        Recipients: ${result.total_recipients}
        Sent: ${result.delivery_stats?.sent || 0}
        Failed: ${result.delivery_stats?.failed || 0}
    `;
    
    alert(message);
}

/**
 * Clear message form
 */
function clearMessageForm() {
    document.getElementById('messageTitle').value = '';
    document.getElementById('messageContent').value = '';
    updatePreview();
}

/**
 * Handle audience selection change
 */
function handleAudienceChange() {
    const audienceSelect = document.getElementById('audience');
    const audienceType = audienceSelect.value;
    
    // Hide all selection sections
    document.getElementById('departmentSelection').style.display = 'none';
    document.getElementById('churchGroupSelection').style.display = 'none';
    document.getElementById('ministrySelection').style.display = 'none';
    document.getElementById('memberSearchSection').style.display = 'none';
    
    // Show appropriate section based on selection
    switch(audienceType) {
        case 'department':
            document.getElementById('departmentSelection').style.display = 'block';
            selectedAudience = { type: 'group', value: null };
            break;
        case 'church_group':
            document.getElementById('churchGroupSelection').style.display = 'block';
            selectedAudience = { type: 'group', value: null };
            break;
        case 'ministry':
            document.getElementById('ministrySelection').style.display = 'block';
            selectedAudience = { type: 'ministry', value: null };
            break;
        case 'others':
            document.getElementById('memberSearchSection').style.display = 'block';
            selectedAudience = { type: 'individual', memberIds: [] };
            selectedMembersArray = [];
            break;
        case 'all':
        default:
            selectedAudience = { type: 'all', value: null };
            break;
    }
    
    updatePreview();
}

/**
 * Load departments from database
 */
async function loadDepartmentsForMessage() {
    try {
        const response = await fetch('get_recipients.php?action=departments');
        const result = await response.json();
        
        if (result.success && result.departments) {
            const select = document.getElementById('departmentSelect');
            select.innerHTML = '<option value="">-- Select Department --</option>';
            result.departments.forEach(dept => {
                const option = document.createElement('option');
                option.value = dept.name;
                option.textContent = `${dept.name} (${dept.member_count} members)`;
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Failed to load departments:', error);
    }
}

/**
 * Load church groups from database
 */
async function loadChurchGroupsForMessage() {
    try {
        const response = await fetch('get_recipients.php?action=departments');
        const result = await response.json();
        
        if (result.success && result.departments) {
            const select = document.getElementById('churchGroupSelect');
            select.innerHTML = '<option value="">-- Select Church Group --</option>';
            result.departments.forEach(group => {
                const option = document.createElement('option');
                option.value = group.name;
                option.textContent = `${group.name} (${group.member_count} members)`;
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Failed to load church groups:', error);
    }
}

/**
 * Load ministries from database
 */
async function loadMinistriesForMessage() {
    try {
        const response = await fetch('get_recipients.php?action=ministries');
        const result = await response.json();
        
        if (result.success && result.ministries) {
            const select = document.getElementById('ministrySelect');
            select.innerHTML = '<option value="">-- Select Ministry --</option>';
            result.ministries.forEach(ministry => {
                const option = document.createElement('option');
                option.value = ministry.name;
                option.textContent = `${ministry.name} (${ministry.member_count} members)`;
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Failed to load ministries:', error);
    }
}

/**
 * Load all members for autocomplete
 */
async function loadMembersForAutocomplete() {
    try {
        const response = await fetch('get_recipients.php?action=members');
        const result = await response.json();
        
        if (result.success && result.members) {
            allMembersForMessage = result.members;
            console.log(`Loaded ${allMembersForMessage.length} members for search`);
        }
    } catch (error) {
        console.error('Failed to load members:', error);
    }
}

/**
 * Search members by name (autocomplete)
 */
function searchMembersForMessage(query) {
    const suggestionsDiv = document.getElementById('memberSuggestionsForMessage');
    
    if (!query || query.trim().length < 1) {
        suggestionsDiv.style.display = 'none';
        return;
    }
    
    const searchTerm = query.toLowerCase();
    const filtered = allMembersForMessage.filter(member => 
        member.name.toLowerCase().includes(searchTerm) ||
        (member.email && member.email.toLowerCase().includes(searchTerm)) ||
        (member.phone && member.phone.includes(searchTerm))
    );
    
    if (filtered.length === 0) {
        suggestionsDiv.innerHTML = '<div style="padding: 12px; text-align: center; color: #9ca3af; font-size: 14px;">No members found</div>';
        suggestionsDiv.style.display = 'block';
        return;
    }
    
    // Show top 10 results
    const resultsToShow = filtered.slice(0, 10);
    suggestionsDiv.innerHTML = resultsToShow.map(member => {
        const initials = member.name.split(' ').map(n => n[0]).join('').toUpperCase();
        
        // Check if already selected
        const isSelected = selectedMembersArray.some(m => m.member_id === member.member_id);
        const opacity = isSelected ? 'opacity: 0.5;' : '';
        const cursor = isSelected ? 'cursor: not-allowed;' : 'cursor: pointer;';
        
        return `
            <div 
                onclick="${isSelected ? '' : `selectMemberForMessage(${member.member_id}, '${member.name.replace(/'/g, "\\'")}', '${member.email || ''}', '${member.phone || ''}')`}" 
                style="padding: 12px; ${cursor} border-bottom: 1px solid #f3f4f6; display: flex; align-items: center; gap: 12px; transition: background 0.2s; ${opacity}"
                ${!isSelected ? `onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='white'"` : ''}>
                <div style="width: 36px; height: 36px; background: #3b82f6; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 13px;">${initials}</div>
                <div style="flex: 1;">
                    <div style="font-weight: 600; color: #111827; font-size: 14px;">${member.name}${isSelected ? ' ✓' : ''}</div>
                    <div style="font-size: 12px; color: #6b7280;">${member.email || member.phone || 'No contact info'}</div>
                </div>
            </div>
        `;
    }).join('');
    
    suggestionsDiv.style.display = 'block';
}

/**
 * Show member suggestions when input is focused
 */
function showMemberSuggestionsForMessage() {
    const input = document.getElementById('memberSearchInput');
    if (input.value.trim().length === 0 && allMembersForMessage.length > 0) {
        const suggestionsDiv = document.getElementById('memberSuggestionsForMessage');
        suggestionsDiv.innerHTML = '<div style="padding: 12px; text-align: center; color: #6b7280; font-size: 13px;">Start typing to search members...</div>';
        suggestionsDiv.style.display = 'block';
    }
}

/**
 * Select a member from suggestions
 */
function selectMemberForMessage(id, name, email, phone) {
    // Check if already selected
    if (selectedMembersArray.some(m => m.member_id === id)) {
        return;
    }
    
    // Add to selected members
    selectedMembersArray.push({ member_id: id, name: name, email: email, phone: phone });
    
    // Update hidden input with IDs
    document.getElementById('selectedMemberIds').value = selectedMembersArray.map(m => m.member_id).join(',');
    
    // Clear search input
    document.getElementById('memberSearchInput').value = '';
    document.getElementById('memberSuggestionsForMessage').style.display = 'none';
    
    // Display selected members
    displaySelectedMembers();
    updatePreview();
    
    console.log(`Selected member: ${name} (ID: ${id})`);
}

/**
 * Display selected members as badges
 */
function displaySelectedMembers() {
    const displayDiv = document.getElementById('selectedMembersDisplay');
    const listDiv = document.getElementById('selectedMembersList');
    
    if (selectedMembersArray.length === 0) {
        displayDiv.style.display = 'none';
        return;
    }
    
    displayDiv.style.display = 'block';
    listDiv.innerHTML = selectedMembersArray.map(member => {
        return `
            <div style="background: #3b82f6; color: white; padding: 6px 12px; border-radius: 6px; display: flex; align-items: center; gap: 8px; font-size: 13px;">
                <span>${member.name}</span>
                <button onclick="removeMemberFromSelection(${member.member_id})" style="background: transparent; border: none; color: white; cursor: pointer; font-size: 16px; line-height: 1; padding: 0;">×</button>
            </div>
        `;
    }).join('');
}

/**
 * Remove member from selection
 */
function removeMemberFromSelection(memberId) {
    selectedMembersArray = selectedMembersArray.filter(m => m.member_id !== memberId);
    document.getElementById('selectedMemberIds').value = selectedMembersArray.map(m => m.member_id).join(',');
    displaySelectedMembers();
    updatePreview();
}

/**
 * Update preview display with audience information
 */
function updatePreviewDisplay() {
    const audienceType = document.getElementById('audience').value;
    let audienceText = 'All Members';
    
    switch(audienceType) {
        case 'department':
            const dept = document.getElementById('departmentSelect').value;
            audienceText = dept ? `Department: ${dept}` : 'Select department';
            break;
        case 'church_group':
            const group = document.getElementById('churchGroupSelect').value;
            audienceText = group ? `Church Group: ${group}` : 'Select church group';
            break;
        case 'ministry':
            const ministry = document.getElementById('ministrySelect').value;
            audienceText = ministry ? `Ministry: ${ministry}` : 'Select ministry';
            break;
        case 'others':
            audienceText = selectedMembersArray.length > 0 
                ? `${selectedMembersArray.length} Selected Member${selectedMembersArray.length > 1 ? 's' : ''}` 
                : 'No members selected';
            break;
    }
    
    document.getElementById('previewMeta').textContent = `To: ${audienceText}`;
}

/**
 * Update selected audience based on dropdown
 */
function updateAudience() {
    const audienceSelect = document.getElementById('audience');
    if (!audienceSelect) return;
    
    const value = audienceSelect.value;
    
    // Map dropdown values to audience types
    switch (value) {
        case 'All Members':
            selectedAudience = { type: 'all', value: null };
            break;
        case 'Youth Group':
            selectedAudience = { type: 'group', value: 'Youth' };
            break;
        case 'Prayer Team':
            selectedAudience = { type: 'group', value: 'Prayer Team' };
            break;
        case 'Volunteers':
            selectedAudience = { type: 'ministry', value: 'Volunteer' };
            break;
        default:
            selectedAudience = { type: 'all', value: null };
    }
    
    updatePreview();
}

/**
 * Open schedule modal
 */
function openScheduleModal() {
    scheduleMessage();
}

/**
 * AI content generation (placeholder)
 */
function generateContent(type) {
    const contentField = document.getElementById('messageContent');
    
    const templates = {
        sunday: "Dear Church Family,\n\nYou are warmly invited to join us this Sunday for our worship service.\n\nTime: 9:00 AM\nLocation: Main Sanctuary\n\nWe look forward to worshiping with you!\n\nGod bless,\nPastor",
        event: "Join us for an exciting upcoming event!\n\nDate: [Event Date]\nTime: [Event Time]\nLocation: [Event Location]\n\nRSVP: [Contact Info]\n\nSee you there!",
        prayer: "Dear Prayer Warriors,\n\nWe request your prayers for:\n\n[Prayer Request Details]\n\nLet us stand in agreement and lift this up to our Heavenly Father.\n\nIn Christ,\nPrayer Team",
        followup: "Thank you for attending [Event/Service]!\n\nWe hope you were blessed. We'd love to hear your feedback.\n\nStay connected with us and join us again soon!\n\nBlessings,"
    };
    
    if (templates[type]) {
        contentField.value = templates[type];
        updatePreview();
    }
}

// Close suggestions when clicking outside
document.addEventListener('click', function(e) {
    const searchInput = document.getElementById('memberSearchInput');
    const suggestionsDiv = document.getElementById('memberSuggestionsForMessage');
    
    if (searchInput && suggestionsDiv && 
        !searchInput.contains(e.target) && 
        !suggestionsDiv.contains(e.target)) {
        suggestionsDiv.style.display = 'none';
    }
});

// Make functions globally accessible
window.toggleChannel = toggleChannel;
window.sendMessage = sendMessage;
window.scheduleMessage = scheduleMessage;
window.saveDraft = saveDraft;
window.openScheduleModal = openScheduleModal;
window.generateContent = generateContent;
window.handleAudienceChange = handleAudienceChange;
window.searchMembersForMessage = searchMembersForMessage;
window.showMemberSuggestionsForMessage = showMemberSuggestionsForMessage;
window.selectMemberForMessage = selectMemberForMessage;
window.removeMemberFromSelection = removeMemberFromSelection;
