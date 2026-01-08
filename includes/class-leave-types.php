<?php
/**
 * Leave Types Management Class
 * 
 * Handles CRUD operations for leave types (Annual, Sick, Study, etc.)
 *
 * @package Leave_Manager
 * @version 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Leave_Manager_Leave_Types {

    /**
     * Database table name
     *
     * @var string
     */
    private $table_name;

    /**
     * WordPress database object
     *
     * @var wpdb
     */
    private $wpdb;

    /**
     * Default leave types
     *
     * @var array
     */
    private $default_types = array(
        array(
            'type_name'         => 'Annual Leave',
            'type_code'         => 'annual',
            'description'       => 'Standard annual leave entitlement for employees',
            'default_days'      => 20,
            'color'             => '#3498db',
            'requires_approval' => 1,
            'is_paid'           => 1,
            'status'            => 'active'
        ),
        array(
            'type_name'         => 'Sick Leave',
            'type_code'         => 'sick',
            'description'       => 'Leave for illness or medical appointments',
            'default_days'      => 10,
            'color'             => '#e74c3c',
            'requires_approval' => 1,
            'is_paid'           => 1,
            'status'            => 'active'
        ),
        array(
            'type_name'         => 'Study Leave',
            'type_code'         => 'study',
            'description'       => 'Leave for educational purposes and examinations',
            'default_days'      => 5,
            'color'             => '#9b59b6',
            'requires_approval' => 1,
            'is_paid'           => 1,
            'status'            => 'active'
        ),
        array(
            'type_name'         => 'Other Leave',
            'type_code'         => 'other',
            'description'       => 'Miscellaneous leave types (compassionate, family, etc.)',
            'default_days'      => 3,
            'color'             => '#95a5a6',
            'requires_approval' => 1,
            'is_paid'           => 0,
            'status'            => 'active'
        )
    );

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'leave_manager_leave_types';
    }

    /**
     * Get all leave types
     *
     * @param array $args Query arguments (status, orderby, order, limit, offset)
     * @return array Array of leave types
     */
    public function get_all( $args = array() ) {
        $defaults = array(
            'status'  => '',
            'orderby' => 'type_id',
            'order'   => 'ASC',
            'limit'   => 0,
            'offset'  => 0
        );

        $args = wp_parse_args( $args, $defaults );

        $sql = "SELECT * FROM {$this->table_name}";
        $where = array();
        $values = array();

        // Filter by status
        if ( ! empty( $args['status'] ) ) {
            $where[] = 'status = %s';
            $values[] = sanitize_text_field( $args['status'] );
        }

        // Add WHERE clause
        if ( ! empty( $where ) ) {
            $sql .= ' WHERE ' . implode( ' AND ', $where );
        }

        // Add ORDER BY
        $allowed_orderby = array( 'type_id', 'type_name', 'type_code', 'default_days', 'created_at' );
        $orderby = in_array( $args['orderby'], $allowed_orderby ) ? $args['orderby'] : 'type_id';
        $order = strtoupper( $args['order'] ) === 'DESC' ? 'DESC' : 'ASC';
        $sql .= " ORDER BY {$orderby} {$order}";

        // Add LIMIT
        if ( $args['limit'] > 0 ) {
            $sql .= $this->wpdb->prepare( ' LIMIT %d OFFSET %d', absint( $args['limit'] ), absint( $args['offset'] ) );
        }

        // Execute query
        if ( ! empty( $values ) ) {
            $sql = $this->wpdb->prepare( $sql, $values );
        }

        return $this->wpdb->get_results( $sql, ARRAY_A );
    }

    /**
     * Get a single leave type by ID
     *
     * @param int $type_id Leave type ID
     * @return array|null Leave type data or null
     */
    public function get( $type_id ) {
        $type_id = absint( $type_id );
        
        if ( $type_id <= 0 ) {
            return null;
        }

        return $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE type_id = %d",
                $type_id
            ),
            ARRAY_A
        );
    }

    /**
     * Get a leave type by code
     *
     * @param string $type_code Leave type code
     * @return array|null Leave type data or null
     */
    public function get_by_code( $type_code ) {
        $type_code = sanitize_text_field( $type_code );
        
        if ( empty( $type_code ) ) {
            return null;
        }

        return $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE type_code = %s",
                $type_code
            ),
            ARRAY_A
        );
    }

    /**
     * Create a new leave type
     *
     * @param array $data Leave type data
     * @return int|false Insert ID on success, false on failure
     */
    public function create( $data ) {
        // Validate required fields
        if ( empty( $data['type_name'] ) || empty( $data['type_code'] ) ) {
            return false;
        }

        // Check for duplicate type_code
        $existing = $this->get_by_code( $data['type_code'] );
        if ( $existing ) {
            return false;
        }

        // Sanitize data
        $insert_data = array(
            'type_name'         => sanitize_text_field( $data['type_name'] ),
            'type_code'         => sanitize_key( $data['type_code'] ),
            'description'       => isset( $data['description'] ) ? sanitize_textarea_field( $data['description'] ) : '',
            'default_days'      => isset( $data['default_days'] ) ? floatval( $data['default_days'] ) : 0,
            'color'             => isset( $data['color'] ) ? sanitize_hex_color( $data['color'] ) : '#3498db',
            'requires_approval' => isset( $data['requires_approval'] ) ? absint( $data['requires_approval'] ) : 1,
            'is_paid'           => isset( $data['is_paid'] ) ? absint( $data['is_paid'] ) : 1,
            'status'            => isset( $data['status'] ) ? sanitize_text_field( $data['status'] ) : 'active',
            'created_at'        => current_time( 'mysql' ),
            'updated_at'        => current_time( 'mysql' )
        );

        // Validate color
        if ( empty( $insert_data['color'] ) || ! preg_match( '/^#[a-f0-9]{6}$/i', $insert_data['color'] ) ) {
            $insert_data['color'] = '#3498db';
        }

        // Validate status
        if ( ! in_array( $insert_data['status'], array( 'active', 'inactive' ) ) ) {
            $insert_data['status'] = 'active';
        }

        $result = $this->wpdb->insert(
            $this->table_name,
            $insert_data,
            array( '%s', '%s', '%s', '%f', '%s', '%d', '%d', '%s', '%s', '%s' )
        );

        if ( $result === false ) {
            return false;
        }

        return $this->wpdb->insert_id;
    }

    /**
     * Update a leave type
     *
     * @param int   $type_id Leave type ID
     * @param array $data    Leave type data to update
     * @return bool True on success, false on failure
     */
    public function update( $type_id, $data ) {
        $type_id = absint( $type_id );
        
        if ( $type_id <= 0 ) {
            return false;
        }

        // Check if type exists
        $existing = $this->get( $type_id );
        if ( ! $existing ) {
            return false;
        }

        // Build update data
        $update_data = array();
        $format = array();

        if ( isset( $data['type_name'] ) ) {
            $update_data['type_name'] = sanitize_text_field( $data['type_name'] );
            $format[] = '%s';
        }

        if ( isset( $data['type_code'] ) ) {
            // Check for duplicate type_code (excluding current record)
            $duplicate = $this->wpdb->get_var(
                $this->wpdb->prepare(
                    "SELECT type_id FROM {$this->table_name} WHERE type_code = %s AND type_id != %d",
                    sanitize_key( $data['type_code'] ),
                    $type_id
                )
            );
            if ( $duplicate ) {
                return false;
            }
            $update_data['type_code'] = sanitize_key( $data['type_code'] );
            $format[] = '%s';
        }

        if ( isset( $data['description'] ) ) {
            $update_data['description'] = sanitize_textarea_field( $data['description'] );
            $format[] = '%s';
        }

        if ( isset( $data['default_days'] ) ) {
            $update_data['default_days'] = floatval( $data['default_days'] );
            $format[] = '%f';
        }

        if ( isset( $data['color'] ) ) {
            $color = sanitize_hex_color( $data['color'] );
            $update_data['color'] = ! empty( $color ) ? $color : '#3498db';
            $format[] = '%s';
        }

        if ( isset( $data['requires_approval'] ) ) {
            $update_data['requires_approval'] = absint( $data['requires_approval'] );
            $format[] = '%d';
        }

        if ( isset( $data['is_paid'] ) ) {
            $update_data['is_paid'] = absint( $data['is_paid'] );
            $format[] = '%d';
        }

        if ( isset( $data['status'] ) ) {
            $status = sanitize_text_field( $data['status'] );
            $update_data['status'] = in_array( $status, array( 'active', 'inactive' ) ) ? $status : 'active';
            $format[] = '%s';
        }

        if ( empty( $update_data ) ) {
            return false;
        }

        // Add updated_at
        $update_data['updated_at'] = current_time( 'mysql' );
        $format[] = '%s';

        $result = $this->wpdb->update(
            $this->table_name,
            $update_data,
            array( 'type_id' => $type_id ),
            $format,
            array( '%d' )
        );

        return $result !== false;
    }

    /**
     * Delete a leave type
     *
     * @param int $type_id Leave type ID
     * @return bool True on success, false on failure
     */
    public function delete( $type_id ) {
        $type_id = absint( $type_id );
        
        if ( $type_id <= 0 ) {
            return false;
        }

        // Check if type exists
        $existing = $this->get( $type_id );
        if ( ! $existing ) {
            return false;
        }

        // Check if type is in use (has leave requests)
        $in_use = $this->is_in_use( $type_id );
        if ( $in_use ) {
            // Instead of deleting, set status to inactive
            return $this->update( $type_id, array( 'status' => 'inactive' ) );
        }

        $result = $this->wpdb->delete(
            $this->table_name,
            array( 'type_id' => $type_id ),
            array( '%d' )
        );

        return $result !== false;
    }

    /**
     * Check if a leave type is in use
     *
     * @param int $type_id Leave type ID
     * @return bool True if in use, false otherwise
     */
    public function is_in_use( $type_id ) {
        $type_id = absint( $type_id );
        
        // Get the type to check its code
        $type = $this->get( $type_id );
        if ( ! $type ) {
            return false;
        }

        $requests_table = $this->wpdb->prefix . 'leave_manager_leave_requests';
        
        $count = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$requests_table} WHERE leave_type = %s",
                $type['type_code']
            )
        );

        return $count > 0;
    }

    /**
     * Get count of leave types
     *
     * @param string $status Optional status filter
     * @return int Count of leave types
     */
    public function get_count( $status = '' ) {
        $sql = "SELECT COUNT(*) FROM {$this->table_name}";
        
        if ( ! empty( $status ) ) {
            $sql = $this->wpdb->prepare(
                "{$sql} WHERE status = %s",
                sanitize_text_field( $status )
            );
        }

        return (int) $this->wpdb->get_var( $sql );
    }

    /**
     * Install default leave types
     *
     * @return bool True on success, false on failure
     */
    public function install_defaults() {
        $installed = 0;

        foreach ( $this->default_types as $type ) {
            // Check if type already exists
            $existing = $this->get_by_code( $type['type_code'] );
            if ( ! $existing ) {
                $result = $this->create( $type );
                if ( $result ) {
                    $installed++;
                }
            }
        }

        return $installed > 0;
    }

    /**
     * Get leave types for dropdown/select
     *
     * @param bool $include_inactive Include inactive types
     * @return array Array of type_code => type_name
     */
    public function get_for_dropdown( $include_inactive = false ) {
        $args = array(
            'orderby' => 'type_name',
            'order'   => 'ASC'
        );

        if ( ! $include_inactive ) {
            $args['status'] = 'active';
        }

        $types = $this->get_all( $args );
        $dropdown = array();

        foreach ( $types as $type ) {
            $dropdown[ $type['type_code'] ] = $type['type_name'];
        }

        return $dropdown;
    }

    /**
     * Get leave type colors for calendar/charts
     *
     * @return array Array of type_code => color
     */
    public function get_colors() {
        $types = $this->get_all( array( 'status' => 'active' ) );
        $colors = array();

        foreach ( $types as $type ) {
            $colors[ $type['type_code'] ] = $type['color'];
        }

        return $colors;
    }

    /**
     * Validate leave type data
     *
     * @param array $data Leave type data
     * @return array|true Array of errors or true if valid
     */
    public function validate( $data ) {
        $errors = array();

        // Required fields
        if ( empty( $data['type_name'] ) ) {
            $errors['type_name'] = 'Leave type name is required';
        } elseif ( strlen( $data['type_name'] ) > 100 ) {
            $errors['type_name'] = 'Leave type name must be 100 characters or less';
        }

        if ( empty( $data['type_code'] ) ) {
            $errors['type_code'] = 'Leave type code is required';
        } elseif ( strlen( $data['type_code'] ) > 50 ) {
            $errors['type_code'] = 'Leave type code must be 50 characters or less';
        } elseif ( ! preg_match( '/^[a-z0-9_]+$/', $data['type_code'] ) ) {
            $errors['type_code'] = 'Leave type code must contain only lowercase letters, numbers, and underscores';
        }

        // Optional fields validation
        if ( isset( $data['default_days'] ) && $data['default_days'] < 0 ) {
            $errors['default_days'] = 'Default days cannot be negative';
        }

        if ( isset( $data['color'] ) && ! empty( $data['color'] ) ) {
            if ( ! preg_match( '/^#[a-f0-9]{6}$/i', $data['color'] ) ) {
                $errors['color'] = 'Color must be a valid hex color (e.g., #3498db)';
            }
        }

        if ( isset( $data['status'] ) && ! in_array( $data['status'], array( 'active', 'inactive' ) ) ) {
            $errors['status'] = 'Status must be either active or inactive';
        }

        return empty( $errors ) ? true : $errors;
    }
}
