<?php

namespace MAD\Utils;

use MAD\DB\Repositories;

class Logger {
    private static array $levels = array(
        'error' => 0,
        'warn' => 1,
        'info' => 2,
        'debug' => 3,
    );

    public static function error( string $context, string $message, array $data = array() ): void {
        self::log( 'error', $context, $message, $data );
    }

    public static function warn( string $context, string $message, array $data = array() ): void {
        self::log( 'warn', $context, $message, $data );
    }

    public static function info( string $context, string $message, array $data = array() ): void {
        self::log( 'info', $context, $message, $data );
    }

    public static function debug( string $context, string $message, array $data = array() ): void {
        self::log( 'debug', $context, $message, $data );
    }

    private static function log( string $level, string $context, string $message, array $data = array() ): void {
        $settings = get_option( 'mad_settings', array() );
        $current = $settings['log_level'] ?? 'info';
        if ( self::$levels[ $level ] > self::$levels[ $current ] ) {
            return;
        }

        Repositories::insert_log( array(
            'level' => $level,
            'context' => $context,
            'message' => $message,
            'data' => $data ? wp_json_encode( $data ) : null,
        ) );

        if ( class_exists( 'WC_Logger' ) ) {
            $logger = wc_get_logger();
            $logger->log( $level, $message, array( 'source' => 'mad-dropshipping' ) );
        }
    }
}
