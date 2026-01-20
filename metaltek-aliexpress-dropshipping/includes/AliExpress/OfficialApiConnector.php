<?php

namespace MAD\AliExpress;

use MAD\Utils\Logger;

class OfficialApiConnector implements ConnectorInterface {
    public function testConnection(): array {
        Logger::warn( 'auth', 'Official API endpoint not configured.' );
        return array(
            'status' => 'error',
            'message' => 'Official API endpoint not configured.',
        );
    }

    public function getProduct( string $productIdOrUrl ): array {
        Logger::warn( 'import', 'Official API getProduct endpoint not configured.' );
        return array();
    }

    public function searchProducts( string $keyword ): array {
        Logger::warn( 'import', 'Official API searchProducts endpoint not configured.' );
        return array();
    }

    public function importProduct( array $productData ): array {
        return $productData;
    }

    public function syncProduct( string $aeProductId ): array {
        Logger::warn( 'sync', 'Official API syncProduct endpoint not configured.' );
        return array();
    }

    public function createOrder( array $payload ): array {
        Logger::warn( 'order', 'Official API createOrder endpoint not configured.' );
        return array();
    }

    public function getOrder( string $aeOrderId ): array {
        Logger::warn( 'order', 'Official API getOrder endpoint not configured.' );
        return array();
    }
}
