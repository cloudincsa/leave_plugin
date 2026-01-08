<?php
/**
 * Input Validator
 *
 * @package LeaveManager\Security
 */

namespace LeaveManager\Security;

class InputValidator {

    public static function getString( string $key, string $source = 'request', array $options = array() ): ?string {
        $value = self::getRawValue( $key, $source );

        if ( $value === null || trim( (string) $value ) === '' ) {
            return $options['default'] ?? null;
        }

        $value = sanitize_text_field( $value );

        if ( isset( $options['max_length'] ) && strlen( $value ) > $options['max_length'] ) {
            $value = substr( $value, 0, $options['max_length'] );
        }

        return $value;
    }

    public static function getInt( string $key, string $source = 'request', array $options = array() ): ?int {
        $value = self::getRawValue( $key, $source );

        if ( $value === null || $value === '' ) {
            return $options['default'] ?? null;
        }

        $value = (int) $value;

        if ( isset( $options['min'] ) && $value < $options['min'] ) {
            return null;
        }

        if ( isset( $options['max'] ) && $value > $options['max'] ) {
            return null;
        }

        return $value;
    }

    public static function getDate( string $key, string $source = 'request', array $options = array() ): ?string {
        $value = self::getRawValue( $key, $source );

        if ( $value === null || $value === '' ) {
            return $options['default'] ?? null;
        }

        $format = $options['format'] ?? 'Y-m-d';
        $date = \DateTime::createFromFormat( $format, $value );

        if ( ! $date || $date->format( $format ) !== $value ) {
            return null;
        }

        return $value;
    }

    public static function getBool( string $key, string $source = 'request', array $options = array() ): bool {
        $value = self::getRawValue( $key, $source );

        if ( $value === null ) {
            return $options['default'] ?? false;
        }

        return in_array( strtolower( (string) $value ), array( '1', 'true', 'yes', 'on' ), true );
    }

    public static function getEmail( string $key, string $source = 'request', array $options = array() ): ?string {
        $value = self::getRawValue( $key, $source );

        if ( $value === null || $value === '' ) {
            return $options['default'] ?? null;
        }

        $email = sanitize_email( $value );

        if ( ! is_email( $email ) ) {
            return null;
        }

        return $email;
    }

    public static function verifyNonce( string $action, string $key = 'nonce' ): bool {
        $nonce = self::getRawValue( $key, 'request' );

        if ( empty( $nonce ) ) {
            return false;
        }

        return wp_verify_nonce( $nonce, $action ) !== false;
    }

    private static function getRawValue( string $key, string $source ) {
        switch ( strtolower( $source ) ) {
            case 'get':
                return $_GET[ $key ] ?? null;
            case 'post':
                return $_POST[ $key ] ?? null;
            case 'request':
            default:
                return $_REQUEST[ $key ] ?? null;
        }
    }
}
