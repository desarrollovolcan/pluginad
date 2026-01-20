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
            'rating' => 4.6,
            'orders' => 312,
            'category' => 'Mock Category',
            'shipping_time' => '10-15 días',
            'source_url' => 'https://www.aliexpress.com/item/' . $id . '.html',
            'images' => array(
                'https://via.placeholder.com/300x300.png?text=AliExpress+' . $id,
            ),
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

    public function searchProducts( string $keyword ): array {
        $results = array();
        for ( $i = 0; $i < 5; $i++ ) {
            $id = wp_rand( 100000, 999999 );
            $results[] = array(
                'id' => (string) $id,
                'title' => sprintf( 'Mock %s Producto %d', $keyword, $id ),
                'price' => 9.99 + $i,
                'stock' => 25 + $i,
                'sku' => 'MOCK-' . $id,
                'rating' => 4.5,
                'orders' => 120 + $i,
                'shipping_time' => '12-18 días',
                'source_url' => 'https://www.aliexpress.com/item/' . $id . '.html',
                'image' => 'https://via.placeholder.com/120x120.png?text=AE+' . $id,
            );
        }

        return $results;
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
