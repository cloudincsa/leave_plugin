<?php
class Leave_Manager_Error_Handler {
public function log_error( string $message, string $level = "error" ): bool {
error_log( sprintf( "[Leave Manager %s] %s", strtoupper( $level ), $message ) );
return true;
}

public function get_last_error(): string {
return get_transient( "leave_manager_last_error" ) ?: "";
}
}
