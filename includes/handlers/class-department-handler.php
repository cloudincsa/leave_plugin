<?php
class Leave_Manager_Department_Handler {
public function get_all_departments(): array {
global $wpdb;
$table = $wpdb->prefix . "leave_manager_departments";
$results = $wpdb->get_results( "SELECT * FROM {$table}" );
return $results ?: array();
}

public function get_department( int $department_id ): ?object {
global $wpdb;
$table = $wpdb->prefix . "leave_manager_departments";
return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE department_id = %d", $department_id ) );
}
}
