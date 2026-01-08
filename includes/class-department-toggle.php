<?php
/**
 * Department Toggle Manager Class
 */
class Leave_Manager_Department_Toggle {
const OPTION_NAME = 'leave_manager_departments_enabled';

public static function is_enabled() {
$enabled = get_option( self::OPTION_NAME, true );
return (bool) $enabled;
}

public static function enable() {
return update_option( self::OPTION_NAME, true );
}

public static function disable() {
return update_option( self::OPTION_NAME, false );
}

public static function toggle() {
$current = self::is_enabled();
$new_status = ! $current;
update_option( self::OPTION_NAME, $new_status );
return $new_status;
}

public static function get_status() {
$enabled = self::is_enabled();
return array(
'enabled' => $enabled,
'label'   => $enabled ? 'Enabled' : 'Disabled',
'status'  => $enabled ? 'active' : 'inactive',
);
}

public static function filter_columns( $columns ) {
if ( ! self::is_enabled() ) {
unset( $columns['department'] );
}
return $columns;
}

public static function get_department_html( $department ) {
if ( ! self::is_enabled() ) {
return '';
}
return '<span class="department-badge">' . esc_html( $department ) . '</span>';
}

public static function should_show_in_form() {
return self::is_enabled();
}

public static function filter_dashboard_summary( $summary ) {
if ( ! self::is_enabled() ) {
unset( $summary['department_summary'] );
}
return $summary;
}

public static function get_options() {
if ( ! self::is_enabled() ) {
return array();
}

global $wpdb;

$options = array();
$departments = $wpdb->get_results( "SELECT id, department_name FROM {$wpdb->prefix}leave_manager_departments ORDER BY department_name" );

if ( $departments ) {
foreach ( $departments as $dept ) {
$options[ $dept->id ] = $dept->department_name;
}
}

return $options;
}
}
