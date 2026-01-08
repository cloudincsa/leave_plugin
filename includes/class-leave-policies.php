<?php
/**
 * Leave Policies class for Leave Manager Plugin
 *
 * Handles leave policy management, rules, and policy-based calculations.
 *
 * @package Leave_Manager
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Leave_Manager_Leave_Policies class
 */
class Leave_Manager_Leave_Policies {

	/**
	 * Database instance
	 *
	 * @var Leave_Manager_Database
	 */
	private $db;

	/**
	 * Logger instance
	 *
	 * @var Leave_Manager_Logger
	 */
	private $logger;

	/**
	 * Policies table name
	 *
	 * @var string
	 */
	private $policies_table;

	/**
	 * Policy rules table name
	 *
	 * @var string
	 */
	private $rules_table;

	/**
	 * Constructor
	 *
	 * @param Leave_Manager_Database $db Database instance
	 * @param Leave_Manager_Logger   $logger Logger instance
	 */
	public function __construct( $db, $logger ) {
		global $wpdb;
		$this->db              = $db;
		$this->logger          = $logger;
		$this->policies_table  = $wpdb->prefix . 'leave_manager_leave_policies';
		$this->rules_table     = $wpdb->prefix . 'leave_manager_policy_rules';
	}

	/**
	 * Create a new leave policy
	 *
	 * @param array $policy_data Policy data
	 * @return int|false Policy ID or false on failure
	 */
	public function create_policy( $policy_data ) {
		global $wpdb;

		// Validate required fields
		if ( empty( $policy_data['policy_name'] ) || empty( $policy_data['description'] ) ) {
			$this->logger->error( 'Policy creation failed: missing required fields' );
			return false;
		}

		$policy = array(
			'policy_name'    => sanitize_text_field( $policy_data['policy_name'] ),
			'description'    => sanitize_textarea_field( $policy_data['description'] ),
			'leave_type'     => sanitize_text_field( $policy_data['leave_type'] ?? 'annual' ),
			'annual_days'    => floatval( $policy_data['annual_days'] ?? 20 ),
			'carryover_days' => floatval( $policy_data['carryover_days'] ?? 5 ),
			'expiry_days'    => intval( $policy_data['expiry_days'] ?? 365 ),
			'status'         => sanitize_text_field( $policy_data['status'] ?? 'active' ),
			'created_at'     => current_time( 'mysql' ),
			'updated_at'     => current_time( 'mysql' ),
		);

		$result = $wpdb->insert( $this->policies_table, $policy );

		if ( $result ) {
			$policy_id = $wpdb->insert_id;
			$this->logger->info( 'Leave policy created', array( 'policy_id' => $policy_id ) );
			return $policy_id;
		} else {
			$this->logger->error( 'Leave policy creation failed', array( 'error' => $wpdb->last_error ) );
			return false;
		}
	}

	/**
	 * Get a policy by ID
	 *
	 * @param int $policy_id Policy ID
	 * @return object|null Policy object or null
	 */
	public function get_policy( $policy_id ) {
		global $wpdb;
		$policy_id = intval( $policy_id );

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->policies_table} WHERE policy_id = %d",
				$policy_id
			)
		);
	}

	/**
	 * Get all policies
	 *
	 * @param array $args Query arguments
	 * @return array Array of policy objects
	 */
public function get_all_policies( $args = array() ) {
			global $wpdb;
	
			$defaults = array(
				'status' => 'active',
				'limit'  => -1,
				'offset' => 0,
			);
	
			$args = wp_parse_args( $args, $defaults );
	
			$query = "SELECT * FROM {$this->policies_table}";
            $params = array();

			if ( ! empty( $args['status'] ) ) {
				$query .= ' WHERE status = %s';
                $params[] = $args['status'];
			}
	
			$query .= ' ORDER BY created_at DESC';
	
			if ( intval( $args['limit'] ) > 0 ) {
				$query .= ' LIMIT %d OFFSET %d';
                $params[] = $args['limit'];
                $params[] = $args['offset'];
			}

            if (empty($params)) {
                return $wpdb->get_results( $query );
            } else {
			    return $wpdb->get_results( $wpdb->prepare( $query, $params ) );
            }
		}

	/**
	 * Update a policy
	 *
	 * @param int   $policy_id Policy ID
	 * @param array $policy_data Policy data to update
	 * @return bool True on success
	 */
	public function update_policy( $policy_id, $policy_data ) {
		global $wpdb;
		$policy_id = intval( $policy_id );

		// Check if policy exists
		if ( ! $this->get_policy( $policy_id ) ) {
			$this->logger->warning( 'Policy update failed: policy not found', array( 'policy_id' => $policy_id ) );
			return false;
		}

		$update_data = array(
			'updated_at' => current_time( 'mysql' ),
		);

		// Update allowed fields
		if ( isset( $policy_data['policy_name'] ) ) {
			$update_data['policy_name'] = sanitize_text_field( $policy_data['policy_name'] );
		}
		if ( isset( $policy_data['description'] ) ) {
			$update_data['description'] = sanitize_textarea_field( $policy_data['description'] );
		}
		if ( isset( $policy_data['annual_days'] ) ) {
			$update_data['annual_days'] = floatval( $policy_data['annual_days'] );
		}
		if ( isset( $policy_data['carryover_days'] ) ) {
			$update_data['carryover_days'] = floatval( $policy_data['carryover_days'] );
		}
		if ( isset( $policy_data['expiry_days'] ) ) {
			$update_data['expiry_days'] = intval( $policy_data['expiry_days'] );
		}
		if ( isset( $policy_data['status'] ) ) {
			$update_data['status'] = sanitize_text_field( $policy_data['status'] );
		}

		$result = $wpdb->update(
			$this->policies_table,
			$update_data,
			array( 'policy_id' => $policy_id ),
			array_fill( 0, count( $update_data ), '%s' ),
			array( '%d' )
		);

		if ( $result > 0 ) {
			$this->logger->info( 'Leave policy updated', array( 'policy_id' => $policy_id ) );
			return true;
		} else {
			$this->logger->error( 'Leave policy update failed', array( 'policy_id' => $policy_id, 'error' => $wpdb->last_error ) );
			return false;
		}
	}

	/**
	 * Delete a policy
	 *
	 * @param int $policy_id Policy ID
	 * @return bool True on success
	 */
	public function delete_policy( $policy_id ) {
		global $wpdb;
		$policy_id = intval( $policy_id );

		// Check if policy exists
		if ( ! $this->get_policy( $policy_id ) ) {
			$this->logger->warning( 'Policy deletion failed: policy not found', array( 'policy_id' => $policy_id ) );
			return false;
		}

		// Delete policy rules first
		$wpdb->delete( $this->rules_table, array( 'policy_id' => $policy_id ), array( '%d' ) );

		// Delete policy
		$result = $wpdb->delete(
			$this->policies_table,
			array( 'policy_id' => $policy_id ),
			array( '%d' )
		);

		if ( $result ) {
			$this->logger->info( 'Leave policy deleted', array( 'policy_id' => $policy_id ) );
			return true;
		} else {
			$this->logger->error( 'Leave policy deletion failed', array( 'policy_id' => $policy_id, 'error' => $wpdb->last_error ) );
			return false;
		}
	}

	/**
	 * Assign policy to user
	 *
	 * @param int $user_id User ID
	 * @param int $policy_id Policy ID
	 * @return bool True on success
	 */
	public function assign_policy_to_user( $user_id, $policy_id ) {
		global $wpdb;

		$user_id   = intval( $user_id );
		$policy_id = intval( $policy_id );

		// Verify policy exists
		if ( ! $this->get_policy( $policy_id ) ) {
			return false;
		}

		$users_table = $wpdb->prefix . 'leave_manager_leave_users';

$result = $wpdb->update(
				$users_table,
				array( 'policy_id' => $policy_id ),
				array( 'user_id' => $user_id ),
				array( '%d' ), // format for value
				array( '%d' )  // format for where
			);

if ( $result > 0 ) {
				$this->logger->info( 'Policy assigned to user', array( 'user_id' => $user_id, 'policy_id' => $policy_id, 'result' => $result ) );
				return true;
			} else {
				$this->logger->error( 'Policy assignment failed', array( 'user_id' => $user_id, 'policy_id' => $policy_id, 'error' => $wpdb->last_error ) );
				return false;
			}
	}

	/**
	 * Get user's assigned policy
	 *
	 * @param int $user_id User ID
	 * @return object|null Policy object or null
	 */
	public function get_user_policy( $user_id ) {
		global $wpdb;

		$user_id = intval( $user_id );

		$users_table = $wpdb->prefix . 'leave_manager_leave_users';

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT p.* FROM {$this->policies_table} p
				 INNER JOIN {$users_table} u ON p.policy_id = u.policy_id
				 WHERE u.user_id = %d",
				$user_id
			)
		);
	}

	/**
	 * Add policy rule
	 *
	 * @param int   $policy_id Policy ID
	 * @param array $rule_data Rule data
	 * @return int|false Rule ID or false on failure
	 */
	public function add_rule( $policy_id, $rule_data ) {
		global $wpdb;

		$policy_id = intval( $policy_id );

		// Verify policy exists
		if ( ! $this->get_policy( $policy_id ) ) {
			return false;
		}

		$rule = array(
			'policy_id'    => $policy_id,
			'rule_name'    => sanitize_text_field( $rule_data['rule_name'] ),
			'rule_type'    => sanitize_text_field( $rule_data['rule_type'] ),
			'rule_value'   => sanitize_text_field( $rule_data['rule_value'] ),
			'description'  => sanitize_textarea_field( $rule_data['description'] ?? '' ),
			'created_at'   => current_time( 'mysql' ),
		);

		$result = $wpdb->insert( $this->rules_table, $rule );

		if ( $result ) {
			return $wpdb->insert_id;
		} else {
			return false;
		}
	}

	/**
	 * Get policy rules
	 *
	 * @param int $policy_id Policy ID
	 * @return array Array of rule objects
	 */
	public function get_policy_rules( $policy_id ) {
		global $wpdb;

		$policy_id = intval( $policy_id );

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->rules_table} WHERE policy_id = %d ORDER BY created_at DESC",
				$policy_id
			)
		);
	}

	/**
	 * Calculate leave days based on policy
	 *
	 * @param int    $user_id User ID
	 * @param string $leave_type Leave type
	 * @param string $start_date Start date (YYYY-MM-DD)
	 * @param string $end_date End date (YYYY-MM-DD)
	 * @return float Leave days calculated
	 */
	public function calculate_leave_days( $user_id, $leave_type, $start_date, $end_date ) {
		$policy = $this->get_user_policy( $user_id );

		if ( ! $policy ) {
			// Default calculation: business days only
			return $this->count_business_days( $start_date, $end_date );
		}

		// Get policy rules for this leave type
		$rules = $this->get_policy_rules( $policy->policy_id );

		// Apply rules to calculate leave days
		$leave_days = $this->count_business_days( $start_date, $end_date );

		// Check for special rules
		foreach ( $rules as $rule ) {
			if ( $rule->rule_type === 'multiplier' && $rule->rule_value ) {
				$leave_days = $leave_days * floatval( $rule->rule_value );
			} elseif ( $rule->rule_type === 'fixed_value' ) {
				$leave_days = floatval( $rule->rule_value );
			}
		}

		return $leave_days;
	}

	/**
	 * Count business days (excluding weekends)
	 *
	 * @param string $start_date Start date (YYYY-MM-DD)
	 * @param string $end_date End date (YYYY-MM-DD)
	 * @return float Number of business days
	 */
	private function count_business_days( $start_date, $end_date ) {
		$start = new DateTime( $start_date );
		$end   = new DateTime( $end_date );
		$end->modify( '+1 day' );

		$interval = new DateInterval( 'P1D' );
		$period   = new DatePeriod( $start, $interval, $end );

		$business_days = 0;
		foreach ( $period as $date ) {
			// 0 = Sunday, 6 = Saturday
			if ( intval( $date->format( 'w' ) ) !== 0 && intval( $date->format( 'w' ) ) !== 6 ) {
				$business_days++;
			}
		}

		return $business_days;
	}

	/**
	 * Get policy statistics
	 *
	 * @return array Statistics array
	 */
	public function get_statistics() {
		global $wpdb;

		$total_policies = $wpdb->get_var( "SELECT COUNT(*) FROM {$this->policies_table}" );
		$active_policies = $wpdb->get_var( "SELECT COUNT(*) FROM {$this->policies_table} WHERE status = 'active'" );

		return array(
			'total_policies'  => intval( $total_policies ),
			'active_policies' => intval( $active_policies ),
		);
	}
}
