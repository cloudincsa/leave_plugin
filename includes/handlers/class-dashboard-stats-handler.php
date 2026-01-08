<?php
class Leave_Manager_Dashboard_Stats_Handler {
public function get_total_staff(): int {
global $wpdb;
$table = $wpdb->prefix . "leave_manager_leave_users";
$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = \"active\"" );
return (int) $count;
}

public function get_pending_requests(): int {
global $wpdb;
$table = $wpdb->prefix . "leave_manager_leave_requests";
$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = \"pending\"" );
return (int) $count;
}

public function get_dashboard_stats(): array {
return array(
"total_staff" => $this->get_total_staff(),
"pending_requests" => $this->get_pending_requests(),
"approved_today" => $this->get_approved_today(),
"total_departments" => $this->get_total_departments(),
);
}

public function get_approved_today(): int {
global $wpdb;
$table = $wpdb->prefix . "leave_manager_leave_requests";
$today = gmdate( "Y-m-d" );
$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE status = \"approved\" AND DATE(approved_date) = %s", $today ) );
return (int) $count;
}

public function get_total_departments(): int {
global $wpdb;
$table = $wpdb->prefix . "leave_manager_departments";
$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
return (int) $count;
}
}
