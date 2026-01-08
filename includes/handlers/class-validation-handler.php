<?php
/**
 * Validation Handler
 * Handles data validation for leave requests
 */
class Leave_Manager_Validation_Handler {
/**
 * Validate email
 *
 * @param string $email Email to validate
 * @return bool True if email is valid
 */
public function validate_email( string $email ): bool {
 is_email( $email );
}

/**
 * Validate date range
 *
 * @param string $start_date Start date
 * @param string $end_date End date
 * @return bool True if date range is valid
 */
public function validate_date_range( string $start_date, string $end_date ): bool {
 strtotime( $start_date ) <= strtotime( $end_date );
}
}
