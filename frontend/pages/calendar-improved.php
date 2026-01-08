<?php
/**
 * Frontend Calendar Page - Improved with better navigation and styling
 *
 * @package Leave_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get instances
$db = new Leave_Manager_Database();
$logger = new Leave_Manager_Logger();
$calendar = new Leave_Manager_Calendar( $db, $logger );

// Get current user
$current_user_id = get_current_user_id();
if ( empty( $current_user_id ) ) {
	wp_die( 'You must be logged in to view this page.' );
}

// Get month and year from request
$month = isset( $_GET['month'] ) ? intval( $_GET['month'] ) : intval( date( 'm' ) );
$year = isset( $_GET['year'] ) ? intval( $_GET['year'] ) : intval( date( 'Y' ) );

// Validate month and year - ensure they are within reasonable bounds
$month = max( 1, min( 12, $month ) );
$year = max( 2000, min( 2100, $year ) );

// Get calendar data
$events = $calendar->get_user_events( $current_user_id, $year );
$upcoming = $calendar->get_upcoming_leaves( 30, 5 );
$stats = $calendar->get_statistics( date( 'Y-m-01' ), date( 'Y-m-t' ) );

// Calculate previous and next month/year
$prev_month = $month - 1;
$prev_year = $year;
if ( $prev_month < 1 ) {
	$prev_month = 12;
	$prev_year--;
}

$next_month = $month + 1;
$next_year = $year;
if ( $next_month > 12 ) {
	$next_month = 1;
	$next_year++;
}

// Build navigation URLs using the current page URL
$current_page_url = remove_query_arg( array( 'month', 'year' ) );
$prev_url = add_query_arg( array( 'month' => $prev_month, 'year' => $prev_year ), $current_page_url );
$next_url = add_query_arg( array( 'month' => $next_month, 'year' => $next_year ), $current_page_url );
$today_url = add_query_arg( array( 'month' => intval( date( 'm' ) ), 'year' => intval( date( 'Y' ) ) ), $current_page_url );

?>

<style>
.leave-manager-calendar-container {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 30px;
    padding: 20px;
    background: #fff;
    border-radius: 12px;
    max-width: 1400px;
    margin: 0 auto;
}

.calendar-wrapper {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    overflow: hidden;
}

.calendar-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    background: linear-gradient(135deg, #2172B1 0%, #1a5a8a 100%);
    color: white;
    border-radius: 12px 12px 0 0;
}

.calendar-header h2 {
    margin: 0;
    font-size: 24px;
    font-weight: 700;
}

.nav-button {
    padding: 10px 20px;
    background: rgba(255, 255, 255, 0.2);
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 6px;
    text-decoration: none;
    transition: all 0.3s ease;
    font-weight: 600;
    cursor: pointer;
}

.nav-button:hover {
    background: rgba(255, 255, 255, 0.3);
    border-color: rgba(255, 255, 255, 0.5);
}

.calendar-content {
    padding: 20px;
}

.calendar-legend {
    padding: 20px;
    background: #f8f9fa;
    border-top: 1px solid #e9ecef;
}

.calendar-legend h4 {
    margin: 0 0 15px 0;
    font-size: 14px;
    font-weight: 700;
    color: #333;
}

.legend-items {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 10px;
}

.legend-color {
    width: 20px;
    height: 20px;
    border-radius: 4px;
}

.leave-annual {
    background: #4A5FFF;
}

.leave-sick {
    background: #f44336;
}

.leave-other {
    background: #2196f3;
}

.legend-label {
    font-size: 13px;
    color: #666;
}

.calendar-sidebar {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.sidebar-section {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    padding: 20px;
}

.sidebar-section h3 {
    margin: 0 0 15px 0;
    font-size: 16px;
    font-weight: 700;
    color: #333;
}

.leaves-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.leave-item {
    padding: 12px;
    border-left: 4px solid #2172B1;
    background: #f8f9fa;
    border-radius: 6px;
    margin-bottom: 10px;
}

.leave-item:last-child {
    margin-bottom: 0;
}

.leave-date {
    font-size: 13px;
    font-weight: 600;
    color: #333;
    margin-bottom: 5px;
}

.leave-type-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
    color: white;
}

.leave-annual {
    background: #4A5FFF;
    color: #333;
}

.leave-sick {
    background: #f44336;
}

.leave-other {
    background: #2196f3;
}

.stats-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 10px;
}

.stat-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 6px;
}

.stat-label {
    font-size: 13px;
    color: #666;
    font-weight: 600;
}

.stat-value {
    font-size: 18px;
    font-weight: 700;
    color: #2172B1;
}

/* Calendar table styles */
.calendar-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}

.calendar-table th {
    background: #f8f9fa;
    padding: 12px;
    text-align: center;
    font-weight: 600;
    color: #333;
    border-bottom: 2px solid #e9ecef;
}

.calendar-table td {
    padding: 12px;
    text-align: center;
    border: 1px solid #e9ecef;
    height: 80px;
    vertical-align: top;
    position: relative;
}

.calendar-table td.other-month {
    background: #f8f9fa;
    color: #999;
}

.calendar-table td.today {
    background: #e3f2fd;
    border: 2px solid #2172B1;
}

.calendar-date {
    font-weight: 600;
    color: #333;
    margin-bottom: 5px;
    display: block;
}

.calendar-events {
    font-size: 11px;
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.calendar-event {
    padding: 2px 4px;
    border-radius: 3px;
    color: white;
    font-weight: 600;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.calendar-event.annual {
    background: #4A5FFF;
    color: #333;
}

.calendar-event.sick {
    background: #f44336;
}

.calendar-event.other {
    background: #2196f3;
}

/* Responsive */
@media (max-width: 768px) {
    .leave-manager-calendar-container {
        grid-template-columns: 1fr;
    }

    .calendar-header {
        flex-wrap: wrap;
        gap: 10px;
    }

    .calendar-table td {
        padding: 8px;
        height: 60px;
        font-size: 12px;
    }

    .calendar-date {
        font-size: 12px;
    }

    .calendar-events {
        font-size: 10px;
    }
}
</style>

<div class="leave-manager-calendar-container">
    <div class="calendar-wrapper">
        <div class="calendar-header">
            <a href="<?php echo esc_url( $prev_url ); ?>" class="nav-button prev">&laquo; Previous</a>
            <h2><?php echo esc_html( date( 'F Y', mktime( 0, 0, 0, $month, 1, $year ) ) ); ?></h2>
            <a href="<?php echo esc_url( $next_url ); ?>" class="nav-button next">Next &raquo;</a>
        </div>

        <div class="calendar-content">
            <?php 
            // Render calendar table
            $calendar_html = $calendar->render_calendar( $month, $year, $current_user_id );
            if ( ! empty( $calendar_html ) ) {
                echo wp_kses_post( $calendar_html );
            } else {
                echo '<p style="text-align: center; padding: 20px; color: #999;">Unable to load calendar. Please try again.</p>';
            }
            ?>
        </div>

        <div class="calendar-legend">
            <h4>Leave Types</h4>
            <div class="legend-items">
                <div class="legend-item">
                    <span class="legend-color leave-annual"></span>
                    <span class="legend-label">Annual Leave</span>
                </div>
                <div class="legend-item">
                    <span class="legend-color leave-sick"></span>
                    <span class="legend-label">Sick Leave</span>
                </div>
                <div class="legend-item">
                    <span class="legend-color leave-other"></span>
                    <span class="legend-label">Other Leave</span>
                </div>
            </div>
        </div>
    </div>

    <div class="calendar-sidebar">
        <!-- Upcoming Leaves -->
        <div class="sidebar-section upcoming-leaves">
            <h3>Upcoming Leaves</h3>
            <?php if ( ! empty( $upcoming ) ) : ?>
                <ul class="leaves-list">
                    <?php foreach ( $upcoming as $leave ) : ?>
                        <li class="leave-item">
                            <div class="leave-date">
                                <?php echo esc_html( date_i18n( 'M d', strtotime( $leave->start_date ) ) ); ?>
                                -
                                <?php echo esc_html( date_i18n( 'M d', strtotime( $leave->end_date ) ) ); ?>
                            </div>
                            <span class="leave-type-badge leave-<?php echo esc_attr( $leave->leave_type ); ?>">
                                <?php echo esc_html( ucfirst( str_replace( '_', ' ', $leave->leave_type ) ) ); ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <p style="color: #999; font-size: 13px; margin: 0;">No upcoming leaves scheduled.</p>
            <?php endif; ?>
        </div>

        <!-- Statistics -->
        <div class="sidebar-section statistics">
            <h3>Statistics</h3>
            <div class="stats-grid">
                <div class="stat-item">
                    <span class="stat-label">Total Requests</span>
                    <span class="stat-value"><?php echo isset( $stats['total'] ) ? esc_html( $stats['total'] ) : '0'; ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Approved</span>
                    <span class="stat-value" style="color: #4caf50;"><?php echo isset( $stats['approved'] ) ? esc_html( $stats['approved'] ) : '0'; ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Pending</span>
                    <span class="stat-value" style="color: #ff9800;"><?php echo isset( $stats['pending'] ) ? esc_html( $stats['pending'] ) : '0'; ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Rejected</span>
                    <span class="stat-value" style="color: #f44336;"><?php echo isset( $stats['rejected'] ) ? esc_html( $stats['rejected'] ) : '0'; ?></span>
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <div class="sidebar-section">
            <a href="<?php echo esc_url( $today_url ); ?>" style="display: block; text-align: center; padding: 10px; background: #2172B1; color: white; border-radius: 6px; text-decoration: none; font-weight: 600; transition: all 0.3s ease;" onmouseover="this.style.background='#1a5a8a'" onmouseout="this.style.background='#2172B1'">
                Go to Today
            </a>
        </div>
    </div>
</div>
