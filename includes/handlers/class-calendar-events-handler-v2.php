<?php
/**
 * Calendar Events AJAX Handler (Refactored)
 *
 * @package Leave_Manager
 * @version 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once LEAVE_MANAGER_PLUGIN_DIR . 'vendor/autoload.php';

use LeaveManager\Core\ServiceContainer;
use LeaveManager\Security\InputValidator;
use LeaveManager\Security\OutputEscaper;

class Leave_Manager_Calendar_Events_Handler_V2 {

    private $container;

    public function __construct() {
        $this->container = ServiceContainer::getInstance();

        add_action( 'wp_ajax_leave_manager_get_calendar_events_v2', array( $this, 'get_events' ) );
        add_action( 'wp_ajax_nopriv_leave_manager_get_calendar_events_v2', array( $this, 'get_events' ) );
        add_action( 'wp_ajax_leave_manager_get_team_calendar_v2', array( $this, 'get_team_calendar' ) );
    }

    public function get_events() {
        $start = InputValidator::getDate( 'start', 'get', array( 'required' => true ) );
        $end = InputValidator::getDate( 'end', 'get', array( 'required' => true ) );

        if ( empty( $start ) || empty( $end ) ) {
            wp_send_json_error( array( 'message' => 'Start and end dates are required.' ) );
        }

        $custom_auth = new Leave_Manager_Custom_Auth();
        if ( ! $custom_auth->is_logged_in() ) {
            wp_send_json_error( array( 'message' => 'Not authenticated.' ) );
        }

        $current_user = $custom_auth->get_current_user();
        $requestRepo = $this->container->getLeaveRequestRepository();

        $requests = $requestRepo->findByDateRange( $start, $end, (int) $current_user->user_id );

        $events = array();
        foreach ( $requests as $request ) {
            $color = $this->get_status_color( $request->status );
            $events[] = array(
                'id'        => (int) $request->request_id,
                'title'     => OutputEscaper::html( $request->leave_type ?? 'Leave' ),
                'start'     => $request->start_date,
                'end'       => date( 'Y-m-d', strtotime( $request->end_date . ' +1 day' ) ),
                'color'     => $color,
                'status'    => $request->status,
                'allDay'    => true,
            );
        }

        global $wpdb;
        $holidays_table = $wpdb->prefix . 'leave_manager_public_holidays';
        $holidays = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$holidays_table} WHERE holiday_date BETWEEN %s AND %s",
            $start, $end
        ) );

        foreach ( $holidays as $holiday ) {
            $events[] = array(
                'id'        => 'holiday_' . $holiday->id,
                'title'     => OutputEscaper::html( $holiday->name ),
                'start'     => $holiday->holiday_date,
                'end'       => $holiday->holiday_date,
                'color'     => '#e74c3c',
                'status'    => 'holiday',
                'allDay'    => true,
            );
        }

        wp_send_json_success( array( 'events' => $events ) );
    }

    public function get_team_calendar() {
        if ( ! InputValidator::verifyNonce( 'leave_manager_nonce' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed.' ) );
        }

        $custom_auth = new Leave_Manager_Custom_Auth();
        if ( ! $custom_auth->is_logged_in() ) {
            wp_send_json_error( array( 'message' => 'Not authenticated.' ) );
        }

        $current_user = $custom_auth->get_current_user();
        if ( ! in_array( $current_user->role, array( 'admin', 'hr', 'manager' ), true ) ) {
            wp_send_json_error( array( 'message' => 'Permission denied.' ) );
        }

        $start = InputValidator::getDate( 'start', 'get', array( 'required' => true ) );
        $end = InputValidator::getDate( 'end', 'get', array( 'required' => true ) );

        if ( empty( $start ) || empty( $end ) ) {
            wp_send_json_error( array( 'message' => 'Start and end dates are required.' ) );
        }

        $requestRepo = $this->container->getLeaveRequestRepository();
        $userRepo = $this->container->getLeaveUserRepository();

        $requests = $requestRepo->findByDateRange( $start, $end );

        $events = array();
        foreach ( $requests as $request ) {
            if ( $request->status !== 'approved' ) {
                continue;
            }

            $user = $userRepo->find( (int) $request->user_id );
            if ( ! $user ) {
                continue;
            }

            $events[] = array(
                'id'         => (int) $request->request_id,
                'title'      => OutputEscaper::html( $user->getFullName() . ' - ' . ( $request->leave_type ?? 'Leave' ) ),
                'start'      => $request->start_date,
                'end'        => date( 'Y-m-d', strtotime( $request->end_date . ' +1 day' ) ),
                'color'      => '#27ae60',
                'user_id'    => (int) $request->user_id,
                'allDay'     => true,
            );
        }

        wp_send_json_success( array( 'events' => $events ) );
    }

    private function get_status_color( $status ) {
        $colors = array(
            'pending'   => '#f39c12',
            'approved'  => '#27ae60',
            'rejected'  => '#e74c3c',
            'cancelled' => '#95a5a6',
        );
        return $colors[ $status ] ?? '#3498db';
    }
}

new Leave_Manager_Calendar_Events_Handler_V2();
