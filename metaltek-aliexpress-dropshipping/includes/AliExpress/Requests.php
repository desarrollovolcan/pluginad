<?php

namespace MAD\AliExpress;

class Requests {
    public static function buildAuthUrl( string $clientId, string $redirectUri ): string {
        return add_query_arg(
            array(
                'client_id' => rawurlencode( $clientId ),
                'redirect_uri' => rawurlencode( $redirectUri ),
                'response_type' => 'code',
            ),
            'https://auth.aliexpress.com/oauth/authorize'
        );
    }
}
