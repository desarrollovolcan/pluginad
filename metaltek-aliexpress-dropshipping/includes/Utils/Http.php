<?php

namespace MAD\Utils;

class Http {
    public static function request( string $method, string $url, array $args = array() ): array {
        $defaults = array(
            'method' => $method,
            'timeout' => 20,
            'headers' => array(),
        );
        $response = wp_remote_request( $url, wp_parse_args( $args, $defaults ) );
        if ( is_wp_error( $response ) ) {
            return array(
                'error' => $response->get_error_message(),
                'status' => 0,
                'body' => '',
            );
        }

        return array(
            'status' => wp_remote_retrieve_response_code( $response ),
            'body' => wp_remote_retrieve_body( $response ),
            'headers' => wp_remote_retrieve_headers( $response ),
        );
    }
}
