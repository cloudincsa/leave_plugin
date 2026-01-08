<?php
/**
 * WordPress Function Stubs for Standalone Testing
 *
 * These stubs allow unit tests to run without a full WordPress installation.
 * They provide minimal implementations of commonly used WordPress functions.
 *
 * @package Leave_Manager
 * @subpackage Tests
 */

// Prevent direct access
if ( ! defined( 'LEAVE_MANAGER_TESTING' ) ) {
    exit;
}

// Global variables that WordPress would normally set
global $wpdb;

/**
 * Mock wpdb class
 */
if ( ! class_exists( 'wpdb' ) ) {
    class wpdb {
        public $prefix = 'wp_';
        public $last_error = '';
        public $last_query = '';
        public $insert_id = 0;
        
        private $mock_results = array();
        
        public function prepare( $query, ...$args ) {
            return vsprintf( str_replace( '%s', "'%s'", $query ), $args );
        }
        
        public function query( $query ) {
            $this->last_query = $query;
            return true;
        }
        
        public function get_results( $query, $output = OBJECT ) {
            $this->last_query = $query;
            return isset( $this->mock_results['get_results'] ) ? $this->mock_results['get_results'] : array();
        }
        
        public function get_row( $query, $output = OBJECT, $y = 0 ) {
            $this->last_query = $query;
            return isset( $this->mock_results['get_row'] ) ? $this->mock_results['get_row'] : null;
        }
        
        public function get_var( $query = null, $x = 0, $y = 0 ) {
            $this->last_query = $query;
            return isset( $this->mock_results['get_var'] ) ? $this->mock_results['get_var'] : null;
        }
        
        public function insert( $table, $data, $format = null ) {
            $this->insert_id = rand( 1, 1000 );
            return 1;
        }
        
        public function update( $table, $data, $where, $format = null, $where_format = null ) {
            return 1;
        }
        
        public function delete( $table, $where, $where_format = null ) {
            return 1;
        }
        
        public function set_mock_results( $method, $results ) {
            $this->mock_results[$method] = $results;
        }
    }
    
    $wpdb = new wpdb();
}

// Define OBJECT constant if not defined
if ( ! defined( 'OBJECT' ) ) {
    define( 'OBJECT', 'OBJECT' );
}

if ( ! defined( 'ARRAY_A' ) ) {
    define( 'ARRAY_A', 'ARRAY_A' );
}

if ( ! defined( 'ARRAY_N' ) ) {
    define( 'ARRAY_N', 'ARRAY_N' );
}

/**
 * Stub WordPress functions
 */

if ( ! function_exists( 'add_action' ) ) {
    function add_action( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {
        return true;
    }
}

if ( ! function_exists( 'add_filter' ) ) {
    function add_filter( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {
        return true;
    }
}

if ( ! function_exists( 'apply_filters' ) ) {
    function apply_filters( $tag, $value, ...$args ) {
        return $value;
    }
}

if ( ! function_exists( 'do_action' ) ) {
    function do_action( $tag, ...$args ) {
        return;
    }
}

if ( ! function_exists( 'wp_nonce_field' ) ) {
    function wp_nonce_field( $action = -1, $name = '_wpnonce', $referer = true, $echo = true ) {
        $nonce = wp_create_nonce( $action );
        $html = '<input type="hidden" name="' . $name . '" value="' . $nonce . '" />';
        if ( $echo ) {
            echo $html;
        }
        return $html;
    }
}

if ( ! function_exists( 'wp_create_nonce' ) ) {
    function wp_create_nonce( $action = -1 ) {
        return md5( $action . 'test_salt' );
    }
}

if ( ! function_exists( 'wp_verify_nonce' ) ) {
    function wp_verify_nonce( $nonce, $action = -1 ) {
        return $nonce === wp_create_nonce( $action ) ? 1 : false;
    }
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
    function sanitize_text_field( $str ) {
        return htmlspecialchars( strip_tags( trim( $str ) ), ENT_QUOTES, 'UTF-8' );
    }
}

if ( ! function_exists( 'sanitize_email' ) ) {
    function sanitize_email( $email ) {
        return filter_var( $email, FILTER_SANITIZE_EMAIL );
    }
}

if ( ! function_exists( 'esc_html' ) ) {
    function esc_html( $text ) {
        return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
    }
}

if ( ! function_exists( 'esc_attr' ) ) {
    function esc_attr( $text ) {
        return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
    }
}

if ( ! function_exists( 'esc_url' ) ) {
    function esc_url( $url ) {
        return filter_var( $url, FILTER_SANITIZE_URL );
    }
}

if ( ! function_exists( 'wp_mail' ) ) {
    function wp_mail( $to, $subject, $message, $headers = '', $attachments = array() ) {
        // Log the email for testing purposes
        global $wp_mail_log;
        if ( ! isset( $wp_mail_log ) ) {
            $wp_mail_log = array();
        }
        $wp_mail_log[] = compact( 'to', 'subject', 'message', 'headers', 'attachments' );
        return true;
    }
}

if ( ! function_exists( 'get_option' ) ) {
    function get_option( $option, $default = false ) {
        global $wp_options;
        if ( ! isset( $wp_options ) ) {
            $wp_options = array();
        }
        return isset( $wp_options[$option] ) ? $wp_options[$option] : $default;
    }
}

if ( ! function_exists( 'update_option' ) ) {
    function update_option( $option, $value, $autoload = null ) {
        global $wp_options;
        if ( ! isset( $wp_options ) ) {
            $wp_options = array();
        }
        $wp_options[$option] = $value;
        return true;
    }
}

if ( ! function_exists( 'delete_option' ) ) {
    function delete_option( $option ) {
        global $wp_options;
        if ( isset( $wp_options[$option] ) ) {
            unset( $wp_options[$option] );
            return true;
        }
        return false;
    }
}

if ( ! function_exists( 'current_time' ) ) {
    function current_time( $type, $gmt = 0 ) {
        if ( $type === 'mysql' ) {
            return date( 'Y-m-d H:i:s' );
        }
        return time();
    }
}

if ( ! function_exists( 'wp_json_encode' ) ) {
    function wp_json_encode( $data, $options = 0, $depth = 512 ) {
        return json_encode( $data, $options, $depth );
    }
}

if ( ! function_exists( 'wp_send_json' ) ) {
    function wp_send_json( $response, $status_code = null ) {
        echo json_encode( $response );
    }
}

if ( ! function_exists( 'wp_send_json_success' ) ) {
    function wp_send_json_success( $data = null, $status_code = null ) {
        wp_send_json( array( 'success' => true, 'data' => $data ), $status_code );
    }
}

if ( ! function_exists( 'wp_send_json_error' ) ) {
    function wp_send_json_error( $data = null, $status_code = null ) {
        wp_send_json( array( 'success' => false, 'data' => $data ), $status_code );
    }
}

if ( ! function_exists( '__' ) ) {
    function __( $text, $domain = 'default' ) {
        return $text;
    }
}

if ( ! function_exists( '_e' ) ) {
    function _e( $text, $domain = 'default' ) {
        echo $text;
    }
}

if ( ! function_exists( 'esc_html__' ) ) {
    function esc_html__( $text, $domain = 'default' ) {
        return esc_html( $text );
    }
}

if ( ! function_exists( 'esc_attr__' ) ) {
    function esc_attr__( $text, $domain = 'default' ) {
        return esc_attr( $text );
    }
}

if ( ! function_exists( 'plugin_dir_path' ) ) {
    function plugin_dir_path( $file ) {
        return trailingslashit( dirname( $file ) );
    }
}

if ( ! function_exists( 'plugin_dir_url' ) ) {
    function plugin_dir_url( $file ) {
        return 'http://localhost/wp-content/plugins/' . basename( dirname( $file ) ) . '/';
    }
}

if ( ! function_exists( 'trailingslashit' ) ) {
    function trailingslashit( $string ) {
        return rtrim( $string, '/\\' ) . '/';
    }
}

if ( ! function_exists( 'absint' ) ) {
    function absint( $maybeint ) {
        return abs( intval( $maybeint ) );
    }
}

if ( ! function_exists( 'wp_hash_password' ) ) {
    function wp_hash_password( $password ) {
        return password_hash( $password, PASSWORD_DEFAULT );
    }
}

if ( ! function_exists( 'wp_check_password' ) ) {
    function wp_check_password( $password, $hash, $user_id = '' ) {
        return password_verify( $password, $hash );
    }
}

echo "WordPress stubs loaded for standalone testing.\n";
