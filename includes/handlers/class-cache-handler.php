<?php
/**
 * Cache Handler
 * Handles WordPress transient caching operations
 */
class Leave_Manager_Cache_Handler {
/**
 * Get cached data
 *
 * @param string $key The cache key
 * @return mixed The cached data or false if not found
 */
public function get_cached_data( string $key ): mixed {
 get_transient( $key );
}

/**
 * Set cache
 *
 * @param string $key The cache key
 * @param mixed $data The data to cache
 * @param int $expiry Cache expiration in seconds
 * @return bool True if cache was set
 */
public function set_cache( string $key, mixed $data, int $expiry = 3600 ): bool {
 set_transient( $key, $data, $expiry );
}

/**
 * Clear cache
 *
 * @param string $key The cache key to clear
 * @return bool True if cache was cleared
 */
public function clear_cache( string $key ): bool {
 delete_transient( $key );
}
}
