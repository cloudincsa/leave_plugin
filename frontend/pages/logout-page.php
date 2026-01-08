<?php
/**
 * Logout Handler
 */

// Logout user
leave_manager_logout();

// Redirect to login
wp_redirect( leave_manager_login_url() );
exit;
