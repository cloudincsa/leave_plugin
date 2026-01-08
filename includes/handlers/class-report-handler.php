<?php
class Leave_Manager_Report_Handler {
public function get_leave_report( string $start_date, string $end_date ): array {
global $wpdb;
$table = $wpdb->prefix . "leave_manager_leave_requests";
$results = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE start_date >= %s AND end_date <= %s", $start_date, $end_date ) );
return $results ?: array();
}
}
