<?php
/**
 * Departments Management Class
 *
 * @package Leave_Manager
 * @version 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Leave_Manager_Departments {

    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'leave_manager_departments';
    }

    /**
     * Get all departments
     *
     * @param array $args Query arguments
     * @return array
     */
    public function get_all( $args = array() ) {
        global $wpdb;

        $defaults = array(
            'status' => '',
            'orderby' => 'department_name',
            'order' => 'ASC',
            'limit' => 0,
            'offset' => 0,
        );

        $args = wp_parse_args( $args, $defaults );

        $sql = "SELECT d.*, 
                       u.first_name as manager_first_name, 
                       u.last_name as manager_last_name,
                       (SELECT COUNT(*) FROM {$wpdb->prefix}leave_manager_leave_users WHERE department = d.department_name) as user_count
                FROM {$this->table_name} d
                LEFT JOIN {$wpdb->prefix}leave_manager_leave_users u ON d.manager_id = u.user_id
                WHERE 1=1";

        if ( ! empty( $args['status'] ) ) {
            $sql .= $wpdb->prepare( " AND d.status = %s", $args['status'] );
        }

        $sql .= " ORDER BY d.{$args['orderby']} {$args['order']}";

        if ( $args['limit'] > 0 ) {
            $sql .= $wpdb->prepare( " LIMIT %d OFFSET %d", $args['limit'], $args['offset'] );
        }

        return $wpdb->get_results( $sql );
    }

    /**
     * Get a single department
     *
     * @param int $department_id
     * @return object|null
     */
    public function get( $department_id ) {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT d.*, 
                        u.first_name as manager_first_name, 
                        u.last_name as manager_last_name
                 FROM {$this->table_name} d
                 LEFT JOIN {$wpdb->prefix}leave_manager_leave_users u ON d.manager_id = u.user_id
                 WHERE d.department_id = %d",
                $department_id
            )
        );
    }

    /**
     * Create a new department
     *
     * @param array $data Department data
     * @return int|false
     */
    public function create( $data ) {
        global $wpdb;

        $defaults = array(
            'department_name' => '',
            'department_code' => '',
            'description' => '',
            'manager_id' => null,
            'parent_id' => null,
            'status' => 'active',
        );

        $data = wp_parse_args( $data, $defaults );

        // Generate code if not provided
        if ( empty( $data['department_code'] ) ) {
            $data['department_code'] = $this->generate_code( $data['department_name'] );
        }

        $result = $wpdb->insert(
            $this->table_name,
            array(
                'department_name' => sanitize_text_field( $data['department_name'] ),
                'department_code' => sanitize_text_field( $data['department_code'] ),
                'description' => sanitize_textarea_field( $data['description'] ),
                'manager_id' => $data['manager_id'] ? intval( $data['manager_id'] ) : null,
                'parent_id' => $data['parent_id'] ? intval( $data['parent_id'] ) : null,
                'status' => sanitize_text_field( $data['status'] ),
            ),
            array( '%s', '%s', '%s', '%d', '%d', '%s' )
        );

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Update a department
     *
     * @param int $department_id
     * @param array $data
     * @return bool
     */
    public function update( $department_id, $data ) {
        global $wpdb;

        $update_data = array();
        $format = array();

        if ( isset( $data['department_name'] ) ) {
            $update_data['department_name'] = sanitize_text_field( $data['department_name'] );
            $format[] = '%s';
        }

        if ( isset( $data['department_code'] ) ) {
            $update_data['department_code'] = sanitize_text_field( $data['department_code'] );
            $format[] = '%s';
        }

        if ( isset( $data['description'] ) ) {
            $update_data['description'] = sanitize_textarea_field( $data['description'] );
            $format[] = '%s';
        }

        if ( isset( $data['manager_id'] ) ) {
            $update_data['manager_id'] = $data['manager_id'] ? intval( $data['manager_id'] ) : null;
            $format[] = '%d';
        }

        if ( isset( $data['parent_id'] ) ) {
            $update_data['parent_id'] = $data['parent_id'] ? intval( $data['parent_id'] ) : null;
            $format[] = '%d';
        }

        if ( isset( $data['status'] ) ) {
            $update_data['status'] = sanitize_text_field( $data['status'] );
            $format[] = '%s';
        }

        if ( empty( $update_data ) ) {
            return false;
        }

        $result = $wpdb->update(
            $this->table_name,
            $update_data,
            array( 'department_id' => $department_id ),
            $format,
            array( '%d' )
        );

        return $result !== false;
    }

    /**
     * Delete a department
     *
     * @param int $department_id
     * @return bool
     */
    public function delete( $department_id ) {
        global $wpdb;

        return $wpdb->delete(
            $this->table_name,
            array( 'department_id' => $department_id ),
            array( '%d' )
        ) !== false;
    }

    /**
     * Get department statistics
     *
     * @return array
     */
    public function get_stats() {
        global $wpdb;

        $total = $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_name}" );
        $active = $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_name} WHERE status = 'active'" );
        $inactive = $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_name} WHERE status = 'inactive'" );

        return array(
            'total' => intval( $total ),
            'active' => intval( $active ),
            'inactive' => intval( $inactive ),
        );
    }

    /**
     * Generate department code from name
     *
     * @param string $name
     * @return string
     */
    private function generate_code( $name ) {
        $code = strtoupper( substr( preg_replace( '/[^a-zA-Z0-9]/', '', $name ), 0, 4 ) );
        return $code ?: 'DEPT';
    }

    /**
     * Sync departments from user data
     * Creates departments based on unique department names in users table
     *
     * @return int Number of departments created
     */
    public function sync_from_users() {
        global $wpdb;

        $users_table = $wpdb->prefix . 'leave_manager_leave_users';

        // Get unique department names from users
        $departments = $wpdb->get_col(
            "SELECT DISTINCT department FROM {$users_table} WHERE department IS NOT NULL AND department != ''"
        );

        $created = 0;
        foreach ( $departments as $dept_name ) {
            // Check if department already exists
            $exists = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT department_id FROM {$this->table_name} WHERE department_name = %s",
                    $dept_name
                )
            );

            if ( ! $exists ) {
                $result = $this->create( array( 'department_name' => $dept_name ) );
                if ( $result ) {
                    $created++;
                }
            }
        }

        return $created;
    }

    /**
     * Get users in a department
     *
     * @param int|string $department Department ID or name
     * @return array
     */
    public function get_users( $department ) {
        global $wpdb;

        $users_table = $wpdb->prefix . 'leave_manager_leave_users';

        if ( is_numeric( $department ) ) {
            // Get department name first
            $dept = $this->get( $department );
            if ( ! $dept ) {
                return array();
            }
            $department = $dept->department_name;
        }

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$users_table} WHERE department = %s ORDER BY last_name, first_name",
                $department
            )
        );
    }
}
