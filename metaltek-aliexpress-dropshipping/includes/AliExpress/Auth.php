<?php

namespace MAD\AliExpress;

use MAD\Utils\Crypto;
use MAD\Utils\Logger;

class Auth {
    public static function get_access_token(): string {
        $settings = get_option( 'mad_settings', array() );
        $token = $settings['access_token'] ?? '';
        return $token ? Crypto::decrypt( $token ) : '';
    }

    public static function get_refresh_token(): string {
        $settings = get_option( 'mad_settings', array() );
        $token = $settings['refresh_token'] ?? '';
        return $token ? Crypto::decrypt( $token ) : '';
    }

    public static function save_tokens( array $tokens ): void {
        $settings = get_option( 'mad_settings', array() );
        if ( ! empty( $tokens['access_token'] ) ) {
            $settings['access_token'] = Crypto::encrypt( $tokens['access_token'] );
        }
        if ( ! empty( $tokens['refresh_token'] ) ) {
            $settings['refresh_token'] = Crypto::encrypt( $tokens['refresh_token'] );
        }
        if ( ! empty( $tokens['expires_at'] ) ) {
            $settings['expires_at'] = sanitize_text_field( $tokens['expires_at'] );
        }
        update_option( 'mad_settings', $settings );
        Logger::info( 'auth', 'Tokens updated.' );
    }
}
