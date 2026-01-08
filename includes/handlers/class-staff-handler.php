<?php
class Leave_Manager_Staff_Handler {
public function get_all_staff(): array {
global $wpdb;
$table = $wpdb->prefix . "leave_manager_leave_users";
$results = $wpdb->get_results( "SELECT * FROM {$table} WHERE status = \"active\"" );
return $results ?: array();
}

public function get_staff( int $staff_id ): ?object {
global $wpdb;
$table = $wpdb->prefix . "leave_manager_leave_users";
return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE user_id = %d", $staff_id ) );
}
}
