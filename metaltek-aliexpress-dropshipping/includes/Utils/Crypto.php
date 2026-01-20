<?php

namespace MAD\Utils;

class Crypto {
    private static function key(): string {
        $raw = AUTH_KEY . SECURE_AUTH_KEY;
        return hash( 'sha256', $raw, true );
    }

    public static function encrypt( string $plain ): string {
        $iv = random_bytes( 16 );
        $ciphertext = openssl_encrypt( $plain, 'AES-256-CBC', self::key(), OPENSSL_RAW_DATA, $iv );
        return base64_encode( $iv . $ciphertext );
    }

    public static function decrypt( string $cipher ): string {
        $data = base64_decode( $cipher );
        if ( false === $data || strlen( $data ) < 16 ) {
            return '';
        }
        $iv = substr( $data, 0, 16 );
        $ciphertext = substr( $data, 16 );
        $plain = openssl_decrypt( $ciphertext, 'AES-256-CBC', self::key(), OPENSSL_RAW_DATA, $iv );
        return $plain ?: '';
    }
}
