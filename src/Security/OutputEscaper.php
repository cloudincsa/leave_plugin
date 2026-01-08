<?php
/**
 * Output Escaper
 *
 * @package LeaveManager\Security
 */

namespace LeaveManager\Security;

class OutputEscaper {

    public static function html( ?string $value ): string {
        if ( $value === null ) {
            return '';
        }
        return esc_html( $value );
    }

    public static function attr( ?string $value ): string {
        if ( $value === null ) {
            return '';
        }
        return esc_attr( $value );
    }

    public static function url( ?string $value ): string {
        if ( $value === null ) {
            return '';
        }
        return esc_url( $value );
    }

    public static function js( ?string $value ): string {
        if ( $value === null ) {
            return '';
        }
        return esc_js( $value );
    }

    public static function textarea( ?string $value ): string {
        if ( $value === null ) {
            return '';
        }
        return esc_textarea( $value );
    }

    public static function json( $value ): string {
        return wp_json_encode( $value );
    }
}
