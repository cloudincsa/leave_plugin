<?php
/**
 * Professional Frontend Calendar Page - AJAX-Based Implementation
 *
 * @package Leave_Manager
 * @version 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Check custom authentication
$custom_auth = new Leave_Manager_Custom_Auth();
if ( ! $custom_auth->is_logged_in() ) {
    wp_redirect( home_url( '/leave-management/login/' ) );
    exit;
}

$current_user = $custom_auth->get_current_user();
$branding = new Leave_Manager_Branding();
$primary_color = $branding->get_setting( 'primary_color' );
$secondary_color = $branding->get_setting( 'primary_dark_color' );

// Generate nonce for AJAX
$calendar_nonce = wp_create_nonce( 'leave_manager_calendar_nonce' );
?>

<style>
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    background: #f5f5f5;
    color: #333;
}

.leave-manager-container {
    max-width: 1000px;
    margin: 0 auto;
    padding: 20px;
}

.professional-header {
    background: linear-gradient(135deg, <?php echo esc_attr( $primary_color ); ?> 0%, <?php echo esc_attr( $secondary_color ); ?> 100%);
    color: white;
    padding: 40px;
    border-radius: 12px;
    margin-bottom: 30px;
}

.professional-header h1 {
    margin: 0 0 10px 0;
    font-size: 32px;
    font-weight: 700;
}

.professional-header p {
    margin: 0;
    font-size: 16px;
    opacity: 0.95;
}

.calendar-card {
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    margin-bottom: 30px;
}

.calendar-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #f0f0f0;
}

.calendar-header h2 {
    margin: 0;
    font-size: 20px;
    font-weight: 700;
    color: #333;
}

.calendar-nav {
    display: flex;
    gap: 10px;
}

.btn-nav {
    padding: 8px 16px;
    background: <?php echo esc_attr( $primary_color ); ?>;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s ease;
    font-size: 13px;
}

.btn-nav:hover {
    background: <?php echo esc_attr( $secondary_color ); ?>;
}

.btn-nav.today {
    background: #f0f0f0;
    color: #333;
}

.btn-nav.today:hover {
    background: #e0e0e0;
}

.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 8px;
    margin-bottom: 30px;
}

.calendar-day-header {
    text-align: center;
    font-weight: 700;
    font-size: 12px;
    color: #666;
    padding: 10px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.calendar-day {
    aspect-ratio: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: flex-start;
    padding: 8px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    background: #fafafa;
    position: relative;
}

.calendar-day:hover {
    background: #f0f0f0;
}

.calendar-day.today {
    background: <?php echo esc_attr( $primary_color ); ?>;
    color: white;
}

.calendar-day.weekend {
    background: #fff5f5;
    color: #999;
}

.calendar-day.leave {
    background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
    color: #2e7d32;
    font-weight: 600;
}

.calendar-day.pending-leave {
    background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%);
    color: #e65100;
    font-weight: 600;
}

.calendar-day.holiday {
    background: linear-gradient(135deg, #fce4ec 0%, #f8bbd9 100%);
    color: #c2185b;
    font-weight: 600;
}

.calendar-day.other-month {
    background: transparent;
    color: #ccc;
}

.calendar-day .day-number {
    font-size: 14px;
    font-weight: 600;
}

.calendar-day .day-indicator {
    font-size: 8px;
    margin-top: 2px;
    text-align: center;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.legend {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    padding: 20px;
    background: #f9f9f9;
    border-radius: 8px;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 13px;
    color: #666;
}

.legend-color {
    width: 20px;
    height: 20px;
    border-radius: 4px;
}

.legend-color.today {
    background: <?php echo esc_attr( $primary_color ); ?>;
}

.legend-color.leave {
    background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
}

.legend-color.pending-leave {
    background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%);
}

.legend-color.holiday {
    background: linear-gradient(135deg, #fce4ec 0%, #f8bbd9 100%);
}

.legend-color.weekend {
    background: #fff5f5;
}

.team-calendar h3 {
    margin: 0 0 20px 0;
    font-size: 18px;
    font-weight: 700;
    color: #333;
}

.team-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.team-member {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    background: #f9f9f9;
    border-radius: 8px;
    transition: all 0.2s ease;
}

.team-member:hover {
    background: #f0f0f0;
}

.team-member-info h4 {
    margin: 0 0 5px 0;
    font-size: 14px;
    font-weight: 600;
    color: #333;
}

.team-member-info p {
    margin: 0;
    font-size: 12px;
    color: #999;
}

.team-member-status {
    padding: 6px 12px;
    background: #d4edda;
    color: #155724;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.team-member-status.on-leave {
    background: #f0f4ff;
    color: <?php echo esc_attr( $primary_color ); ?>;
}

.team-member-status.pending {
    background: #fff3cd;
    color: #856404;
}

.filters {
    display: flex;
    gap: 15px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.filter-group {
    display: flex;
    gap: 10px;
    align-items: center;
}

.filter-group label {
    font-size: 13px;
    font-weight: 600;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.filter-group select {
    padding: 8px 12px;
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    font-size: 13px;
    font-family: inherit;
}

.filter-group select:focus {
    outline: none;
    border-color: <?php echo esc_attr( $primary_color ); ?>;
    box-shadow: 0 0 0 3px rgba(74, 95, 255, 0.1);
}

.loading-spinner {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 40px;
    color: #999;
}

.loading-spinner::after {
    content: '';
    width: 30px;
    height: 30px;
    border: 3px solid #f0f0f0;
    border-top-color: <?php echo esc_attr( $primary_color ); ?>;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.no-data {
    text-align: center;
    padding: 40px;
    color: #999;
    font-size: 14px;
}

@media (max-width: 768px) {
    .calendar-grid {
        gap: 5px;
    }
    
    .calendar-day {
        font-size: 12px;
        padding: 5px;
    }
    
    .calendar-header {
        flex-direction: column;
        gap: 15px;
    }
    
    .legend {
        grid-template-columns: 1fr;
    }
    
    .team-member {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
}
</style>

<div class="leave-manager-container">
    <div class="professional-header">
        <h1>Leave Calendar</h1>
        <p>Welcome, <?php echo esc_html( $current_user->first_name . ' ' . $current_user->last_name ); ?>! View leave schedules and manage your time off.</p>
    </div>

    <div class="calendar-card">
        <div class="calendar-header">
            <h2 id="calendar-month">Loading...</h2>
            <div class="calendar-nav">
                <button class="btn-nav" onclick="previousMonth()">← Previous</button>
                <button class="btn-nav today" onclick="goToToday()">Today</button>
                <button class="btn-nav" onclick="nextMonth()">Next →</button>
            </div>
        </div>

        <div class="calendar-grid" id="calendar-grid">
            <div class="loading-spinner"></div>
        </div>

        <div class="legend">
            <div class="legend-item">
                <div class="legend-color today"></div>
                <span>Today</span>
            </div>
            <div class="legend-item">
                <div class="legend-color leave"></div>
                <span>Approved Leave</span>
            </div>
            <div class="legend-item">
                <div class="legend-color pending-leave"></div>
                <span>Pending Leave</span>
            </div>
            <div class="legend-item">
                <div class="legend-color holiday"></div>
                <span>Public Holiday</span>
            </div>
            <div class="legend-item">
                <div class="legend-color weekend"></div>
                <span>Weekend</span>
            </div>
        </div>
    </div>

    <div class="calendar-card">
        <div class="filters">
            <div class="filter-group">
                <label>Show:</label>
                <select id="filter-type" onchange="updateTeamCalendar()">
                    <option value="all">All Team Members</option>
                    <option value="department">My Department</option>
                    <option value="on-leave">Currently On Leave</option>
                </select>
            </div>
        </div>

        <div class="team-calendar">
            <h3>Team Leave Schedule</h3>
            <div class="team-list" id="team-list">
                <div class="loading-spinner"></div>
            </div>
        </div>
    </div>
</div>

<script>
// Calendar configuration
const calendarConfig = {
    ajaxUrl: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
    nonce: '<?php echo esc_js( $calendar_nonce ); ?>',
    userId: <?php echo intval( $current_user->id ); ?>,
    userDepartment: '<?php echo esc_js( $current_user->department ?? '' ); ?>'
};

// Calendar state
let currentDate = new Date();
let leaveEvents = [];
let publicHolidays = [];
let teamLeaveData = [];

// Month names
const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'];

// Initialize calendar
document.addEventListener('DOMContentLoaded', function() {
    loadCalendarData();
    loadTeamData();
});

// Load calendar data via AJAX
async function loadCalendarData() {
    const year = currentDate.getFullYear();
    const month = currentDate.getMonth();
    
    // Calculate date range for the month
    const startDate = new Date(year, month, 1).toISOString().split('T')[0];
    const endDate = new Date(year, month + 1, 0).toISOString().split('T')[0];
    
    try {
        // Fetch user's leave events
        const leaveResponse = await fetch(calendarConfig.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'leave_manager_get_staff_leave_events',
                nonce: calendarConfig.nonce,
                start: startDate,
                end: endDate,
                user_id: calendarConfig.userId
            })
        });
        
        const leaveData = await leaveResponse.json();
        if (leaveData.success) {
            leaveEvents = leaveData.data || [];
        } else {
            console.warn('Failed to load leave events:', leaveData.data);
            leaveEvents = [];
        }
        
        // Fetch public holidays
        const holidaysResponse = await fetch(calendarConfig.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'leave_manager_get_public_holidays_events',
                nonce: calendarConfig.nonce,
                start: startDate,
                end: endDate
            })
        });
        
        const holidaysData = await holidaysResponse.json();
        if (holidaysData.success) {
            publicHolidays = holidaysData.data || [];
        } else {
            console.warn('Failed to load public holidays:', holidaysData.data);
            publicHolidays = [];
        }
        
    } catch (error) {
        console.error('Error loading calendar data:', error);
        leaveEvents = [];
        publicHolidays = [];
    }
    
    generateCalendar();
}

// Load team data via AJAX
async function loadTeamData() {
    const filterType = document.getElementById('filter-type').value;
    const teamList = document.getElementById('team-list');
    
    teamList.innerHTML = '<div class="loading-spinner"></div>';
    
    try {
        const response = await fetch(calendarConfig.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'leave_manager_get_team_leave_events',
                nonce: calendarConfig.nonce,
                filter: filterType,
                department: calendarConfig.userDepartment
            })
        });
        
        const data = await response.json();
        if (data.success) {
            teamLeaveData = data.data || [];
            renderTeamList();
        } else {
            console.warn('Failed to load team data:', data.data);
            teamList.innerHTML = '<div class="no-data">Unable to load team data</div>';
        }
        
    } catch (error) {
        console.error('Error loading team data:', error);
        teamList.innerHTML = '<div class="no-data">Error loading team data</div>';
    }
}

// Generate calendar grid
function generateCalendar() {
    const year = currentDate.getFullYear();
    const month = currentDate.getMonth();
    
    // Update header
    document.getElementById('calendar-month').textContent = monthNames[month] + ' ' + year;
    
    // Get first day of month and number of days
    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const daysInPrevMonth = new Date(year, month, 0).getDate();
    
    const calendarGrid = document.getElementById('calendar-grid');
    calendarGrid.innerHTML = '';
    
    // Day headers
    const dayHeaders = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    dayHeaders.forEach(day => {
        const header = document.createElement('div');
        header.className = 'calendar-day-header';
        header.textContent = day;
        calendarGrid.appendChild(header);
    });
    
    // Previous month days
    for (let i = firstDay - 1; i >= 0; i--) {
        const day = document.createElement('div');
        day.className = 'calendar-day other-month';
        day.innerHTML = '<span class="day-number">' + (daysInPrevMonth - i) + '</span>';
        calendarGrid.appendChild(day);
    }
    
    // Current month days
    const today = new Date();
    for (let i = 1; i <= daysInMonth; i++) {
        const day = document.createElement('div');
        day.className = 'calendar-day';
        
        const dateStr = year + '-' + String(month + 1).padStart(2, '0') + '-' + String(i).padStart(2, '0');
        
        // Check if today
        if (year === today.getFullYear() && month === today.getMonth() && i === today.getDate()) {
            day.classList.add('today');
        }
        
        // Check if weekend
        const dayOfWeek = new Date(year, month, i).getDay();
        if (dayOfWeek === 0 || dayOfWeek === 6) {
            day.classList.add('weekend');
        }
        
        // Check for leave events
        const leaveEvent = leaveEvents.find(event => {
            const startDate = new Date(event.start);
            const endDate = new Date(event.end);
            const currentDate = new Date(dateStr);
            return currentDate >= startDate && currentDate <= endDate;
        });
        
        if (leaveEvent) {
            if (leaveEvent.status === 'approved') {
                day.classList.add('leave');
            } else if (leaveEvent.status === 'pending') {
                day.classList.add('pending-leave');
            }
        }
        
        // Check for public holidays
        const holiday = publicHolidays.find(h => h.start === dateStr);
        if (holiday) {
            day.classList.add('holiday');
        }
        
        // Build day content
        let dayContent = '<span class="day-number">' + i + '</span>';
        
        if (leaveEvent) {
            dayContent += '<span class="day-indicator">' + (leaveEvent.title || leaveEvent.leave_type || 'Leave') + '</span>';
        } else if (holiday) {
            dayContent += '<span class="day-indicator">' + (holiday.title || 'Holiday') + '</span>';
        }
        
        day.innerHTML = dayContent;
        day.title = getDateTooltip(dateStr, leaveEvent, holiday);
        
        calendarGrid.appendChild(day);
    }
    
    // Next month days
    const totalCells = calendarGrid.children.length - 7; // Subtract day headers
    const remainingCells = 42 - totalCells; // 6 rows * 7 days
    for (let i = 1; i <= remainingCells; i++) {
        const day = document.createElement('div');
        day.className = 'calendar-day other-month';
        day.innerHTML = '<span class="day-number">' + i + '</span>';
        calendarGrid.appendChild(day);
    }
}

// Get tooltip for a date
function getDateTooltip(dateStr, leaveEvent, holiday) {
    let tooltip = dateStr;
    
    if (leaveEvent) {
        tooltip += '\n' + (leaveEvent.title || leaveEvent.leave_type || 'Leave');
        tooltip += ' (' + leaveEvent.status + ')';
    }
    
    if (holiday) {
        tooltip += '\n' + (holiday.title || 'Public Holiday');
    }
    
    return tooltip;
}

// Render team list
function renderTeamList() {
    const teamList = document.getElementById('team-list');
    
    if (teamLeaveData.length === 0) {
        teamList.innerHTML = '<div class="no-data">No team leave data available</div>';
        return;
    }
    
    let html = '';
    teamLeaveData.forEach(member => {
        const statusClass = member.status === 'on-leave' ? 'on-leave' : 
                           member.status === 'pending' ? 'pending' : '';
        const statusText = member.status === 'on-leave' ? 'On Leave' : 
                          member.status === 'pending' ? 'Pending' : 'Available';
        
        html += `
            <div class="team-member">
                <div class="team-member-info">
                    <h4>${escapeHtml(member.name || 'Unknown')}</h4>
                    <p>${escapeHtml(member.department || 'No Department')} • ${escapeHtml(member.dates || 'No leave scheduled')}</p>
                </div>
                <span class="team-member-status ${statusClass}">${statusText}</span>
            </div>
        `;
    });
    
    teamList.innerHTML = html;
}

// Escape HTML to prevent XSS
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Navigation functions
function previousMonth() {
    currentDate.setMonth(currentDate.getMonth() - 1);
    loadCalendarData();
}

function nextMonth() {
    currentDate.setMonth(currentDate.getMonth() + 1);
    loadCalendarData();
}

function goToToday() {
    currentDate = new Date();
    loadCalendarData();
}

function updateTeamCalendar() {
    loadTeamData();
}
</script>
