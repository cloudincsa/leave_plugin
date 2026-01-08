<?php
/**
 * Team Management class for Leave Manager Plugin
 *
 * Handles team definitions, team members, and team-based operations.
 *
 * @package Leave_Manager
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Leave_Manager_Team_Management class
 */
class Leave_Manager_Team_Management {

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
	 * Teams table name
	 *
	 * @var string
	 */
	private $teams_table;

	/**
	 * Team members table name
	 *
	 * @var string
	 */
	private $members_table;

	/**
	 * Constructor
	 *
	 * @param Leave_Manager_Database $db Database instance
	 * @param Leave_Manager_Logger   $logger Logger instance
	 */
	public function __construct( $db, $logger ) {
		global $wpdb;
		$this->db            = $db;
		$this->logger        = $logger;
		$this->teams_table   = $wpdb->prefix . 'leave_manager_teams';
		$this->members_table = $wpdb->prefix . 'leave_manager_team_members';
	}

	/**
	 * Create a team
	 *
	 * @param array $team_data Team data
	 * @return int|false Team ID or false on failure
	 */
	public function create_team( $team_data ) {
		global $wpdb;

		// Validate required fields
		if ( empty( $team_data['team_name'] ) ) {
			return false;
		}

		$team = array(
			'team_name'    => sanitize_text_field( $team_data['team_name'] ),
			'description'  => sanitize_textarea_field( $team_data['description'] ?? '' ),
			'department'   => sanitize_text_field( $team_data['department'] ?? '' ),
			'manager_id'   => intval( $team_data['manager_id'] ?? 0 ),
			'status'       => 'active',
			'created_at'   => current_time( 'mysql' ),
			'updated_at'   => current_time( 'mysql' ),
		);

		$result = $wpdb->insert( $this->teams_table, $team );

		if ( $result ) {
			$team_id = $wpdb->insert_id;
			$this->logger->info( 'Team created', array( 'team_id' => $team_id ) );
			return $team_id;
		} else {
			$this->logger->error( 'Team creation failed', array( 'error' => $wpdb->last_error ) );
			return false;
		}
	}

	/**
	 * Get team by ID
	 *
	 * @param int $team_id Team ID
	 * @return object|null Team object or null
	 */
	public function get_team( $team_id ) {
		global $wpdb;

		$team_id = intval( $team_id );

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->teams_table} WHERE team_id = %d",
				$team_id
			)
		);
	}

	/**
	 * Get all teams
	 *
	 * @param array $args Query arguments
	 * @return array Array of team objects
	 */
	public function get_teams( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'status' => 'active',
			'limit'  => -1,
			'offset' => 0,
		);

		$args = wp_parse_args( $args, $defaults );

		$query = "SELECT * FROM {$this->teams_table}";

		if ( ! empty( $args['status'] ) ) {
			$query .= $wpdb->prepare( ' WHERE status = %s', $args['status'] );
		}

		$query .= ' ORDER BY team_name ASC';

		if ( intval( $args['limit'] ) > 0 ) {
			$query .= $wpdb->prepare( ' LIMIT %d OFFSET %d', $args['limit'], $args['offset'] );
		}

		return $wpdb->get_results( $query );
	}

	/**
	 * Update team
	 *
	 * @param int   $team_id Team ID
	 * @param array $team_data Team data to update
	 * @return bool True on success
	 */
	public function update_team( $team_id, $team_data ) {
		global $wpdb;

		$team_id = intval( $team_id );

		if ( ! $this->get_team( $team_id ) ) {
			return false;
		}

		$update_data = array(
			'updated_at' => current_time( 'mysql' ),
		);

		if ( isset( $team_data['team_name'] ) ) {
			$update_data['team_name'] = sanitize_text_field( $team_data['team_name'] );
		}
		if ( isset( $team_data['description'] ) ) {
			$update_data['description'] = sanitize_textarea_field( $team_data['description'] );
		}
		if ( isset( $team_data['department'] ) ) {
			$update_data['department'] = sanitize_text_field( $team_data['department'] );
		}
		if ( isset( $team_data['manager_id'] ) ) {
			$update_data['manager_id'] = intval( $team_data['manager_id'] );
		}
		if ( isset( $team_data['status'] ) ) {
			$update_data['status'] = sanitize_text_field( $team_data['status'] );
		}

		$result = $wpdb->update(
			$this->teams_table,
			$update_data,
			array( 'team_id' => $team_id ),
			array_fill( 0, count( $update_data ), '%s' ),
			array( '%d' )
		);

		if ( $result !== false ) {
			$this->logger->info( 'Team updated', array( 'team_id' => $team_id ) );
			return true;
		}

		return false;
	}

	/**
	 * Delete team
	 *
	 * @param int $team_id Team ID
	 * @return bool True on success
	 */
	public function delete_team( $team_id ) {
		global $wpdb;

		$team_id = intval( $team_id );

		if ( ! $this->get_team( $team_id ) ) {
			return false;
		}

		// Delete team members first
		$wpdb->delete( $this->members_table, array( 'team_id' => $team_id ), array( '%d' ) );

		// Delete team
		$result = $wpdb->delete(
			$this->teams_table,
			array( 'team_id' => $team_id ),
			array( '%d' )
		);

		if ( $result ) {
			$this->logger->info( 'Team deleted', array( 'team_id' => $team_id ) );
			return true;
		}

		return false;
	}

	/**
	 * Add member to team
	 *
	 * @param int $team_id Team ID
	 * @param int $user_id User ID
	 * @param string $role Member role (member, lead, etc.)
	 * @return bool True on success
	 */
	public function add_member( $team_id, $user_id, $role = 'member' ) {
		global $wpdb;

		$team_id = intval( $team_id );
		$user_id = intval( $user_id );

		// Check if already a member
		$existing = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->members_table} WHERE team_id = %d AND user_id = %d",
				$team_id,
				$user_id
			)
		);

		if ( $existing ) {
			return false;
		}

		$member = array(
			'team_id'    => $team_id,
			'user_id'    => $user_id,
			'role'       => sanitize_text_field( $role ),
			'joined_at'  => current_time( 'mysql' ),
		);

		$result = $wpdb->insert( $this->members_table, $member );

		if ( $result ) {
			$this->logger->info( 'Member added to team', array( 'team_id' => $team_id, 'user_id' => $user_id ) );
			return true;
		}

		return false;
	}

	/**
	 * Remove member from team
	 *
	 * @param int $team_id Team ID
	 * @param int $user_id User ID
	 * @return bool True on success
	 */
	public function remove_member( $team_id, $user_id ) {
		global $wpdb;

		$team_id = intval( $team_id );
		$user_id = intval( $user_id );

		$result = $wpdb->delete(
			$this->members_table,
			array(
				'team_id' => $team_id,
				'user_id' => $user_id,
			),
			array( '%d', '%d' )
		);

		if ( $result ) {
			$this->logger->info( 'Member removed from team', array( 'team_id' => $team_id, 'user_id' => $user_id ) );
			return true;
		}

		return false;
	}

	/**
	 * Get team members
	 *
	 * @param int $team_id Team ID
	 * @return array Array of member objects
	 */
	public function get_team_members( $team_id ) {
		global $wpdb;

		$team_id = intval( $team_id );

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT m.*, u.first_name, u.last_name, u.email, u.role
				 FROM {$this->members_table} m
				 INNER JOIN {$wpdb->prefix}leave_manager_leave_users u ON m.user_id = u.user_id
				 WHERE m.team_id = %d
				 ORDER BY m.joined_at DESC",
				$team_id
			)
		);
	}

	/**
	 * Get user's teams
	 *
	 * @param int $user_id User ID
	 * @return array Array of team objects
	 */
	public function get_user_teams( $user_id ) {
		global $wpdb;

		$user_id = intval( $user_id );

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT t.*, m.role
				 FROM {$this->teams_table} t
				 INNER JOIN {$this->members_table} m ON t.team_id = m.team_id
				 WHERE m.user_id = %d AND t.status = 'active'
				 ORDER BY t.team_name ASC",
				$user_id
			)
		);
	}

	/**
	 * Get team statistics
	 *
	 * @param int $team_id Team ID
	 * @return array Statistics array
	 */
	public function get_team_statistics( $team_id ) {
		global $wpdb;

		$team_id = intval( $team_id );

		$total_members = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->members_table} WHERE team_id = %d",
				$team_id
			)
		);

		$team_leads = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->members_table} WHERE team_id = %d AND role = 'lead'",
				$team_id
			)
		);

		return array(
			'total_members' => intval( $total_members ),
			'team_leads'    => intval( $team_leads ),
		);
	}
}
