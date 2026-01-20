<?php

namespace MAD\AliExpress;

class MockConnector implements ConnectorInterface {
    public function testConnection(): array {
        return array(
            'status' => 'ok',
            'message' => 'Mock connector active.',
        );
    }

    public function getProduct( string $productIdOrUrl ): array {
        $id = preg_replace( '/\D/', '', $productIdOrUrl );
        if ( empty( $id ) ) {
            $id = 'MOCK-' . wp_rand( 1000, 9999 );
        }

        return array(
            'id' => $id,
            'title' => 'Mock AliExpress Product ' . $id,
            'price' => 19.99,
            'stock' => 50,
            'sku' => 'MOCK-SKU-' . $id,
            'description' => '<p>Mock product description for ' . $id . '</p>',
            'source_url' => 'https://www.aliexpress.com/item/' . $id . '.html',
            'variations' => array(
                array(
                    'sku' => 'MOCK-' . $id . '-A',
                    'price' => 19.99,
                    'stock' => 10,
                    'attributes' => array( 'color' => 'Red' ),
                ),
                array(
                    'sku' => 'MOCK-' . $id . '-B',
                    'price' => 21.99,
                    'stock' => 15,
                    'attributes' => array( 'color' => 'Blue' ),
                ),
            ),
        );
    }

    public function importProduct( array $productData ): array {
        return $productData;
    }

    public function syncProduct( string $aeProductId ): array {
        return array(
            'id' => $aeProductId,
            'price' => 19.99,
            'stock' => 40,
            'variations' => array(),
            'hash' => md5( $aeProductId . 'sync' ),
        );
    }

    public function createOrder( array $payload ): array {
        return array(
            'ae_order_id' => 'AE-MOCK-' . wp_rand( 1000, 9999 ),
            'status' => 'created',
            'tracking_number' => '',
            'carrier' => '',
        );
    }

    public function getOrder( string $aeOrderId ): array {
        return array(
            'ae_order_id' => $aeOrderId,
            'status' => 'shipped',
            'tracking_number' => 'TRACK-' . wp_rand( 1000, 9999 ),
            'carrier' => 'MockCarrier',
        );
    }
}
