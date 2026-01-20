<?php

namespace MAD;

class Autoloader {
    public static function register(): void {
        spl_autoload_register( array( __CLASS__, 'autoload' ) );
    }

    public static function autoload( string $class ): void {
        if ( strpos( $class, __NAMESPACE__ . '\\' ) !== 0 ) {
            return;
        }

        $relative = substr( $class, strlen( __NAMESPACE__ ) + 1 );
        $relative = str_replace( '\\', '/', $relative );
        $path = MAD_PLUGIN_DIR . 'includes/' . $relative . '.php';

        if ( file_exists( $path ) ) {
            require_once $path;
        }
    }
}
