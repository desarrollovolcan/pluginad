<?php

namespace MAD\Woo;

use MAD\DB\Repositories;
use MAD\Utils\Logger;
use WC_Product;
use WC_Product_Simple;
use WC_Product_Variable;
use WC_Product_Variation;

class ProductMapper {
    public function import( array $product, array $options ): array {
        $is_variable = ! empty( $product['variations'] );
        $wc_product = $is_variable ? new WC_Product_Variable() : new WC_Product_Simple();

        $wc_product->set_name( $product['title'] ?? '' );
        $wc_product->set_status( 'publish' );
        $wc_product->set_sku( $product['sku'] ?? '' );

        $price = $this->apply_margin( (float) ( $product['price'] ?? 0 ), $options['margin'] ?? 0 );
        $price = $this->apply_rounding( $price, $options['rounding'] ?? '0.99' );
        $wc_product->set_regular_price( (string) $price );
        $wc_product->set_manage_stock( true );
        $wc_product->set_stock_quantity( (int) ( $product['stock'] ?? 0 ) );

        if ( ! empty( $options['import_description'] ) ) {
            $wc_product->set_description( wp_kses_post( $product['description'] ?? '' ) );
        }

        if ( ! empty( $options['category_id'] ) ) {
            $wc_product->set_category_ids( array( (int) $options['category_id'] ) );
        }

        $product_id = $wc_product->save();

        if ( $is_variable ) {
            $this->create_variations( $product_id, $product['variations'], $options );
        }

        update_post_meta( $product_id, '_mad_ae_product_id', $product['id'] ?? '' );
        update_post_meta( $product_id, '_mad_source_url', $product['source_url'] ?? '' );
        update_post_meta( $product_id, '_mad_last_sync_at', current_time( 'mysql' ) );

        Repositories::upsert_product( array(
            'ae_product_id' => $product['id'] ?? '',
            'wc_product_id' => $product_id,
            'source_url' => $product['source_url'] ?? '',
            'last_sync_at' => current_time( 'mysql' ),
            'last_hash' => md5( wp_json_encode( $product ) ),
            'status' => 1,
        ) );

        Logger::info( 'import', 'Product mapped to WooCommerce.', array( 'wc_product_id' => $product_id ) );

        return array(
            'wc_product_id' => $product_id,
            'ae_product_id' => $product['id'] ?? '',
        );
    }

    private function create_variations( int $product_id, array $variations, array $options ): void {
        foreach ( $variations as $variation_data ) {
            $variation = new WC_Product_Variation();
            $variation->set_parent_id( $product_id );
            $variation->set_sku( $variation_data['sku'] ?? '' );
            $price = $this->apply_margin( (float) ( $variation_data['price'] ?? 0 ), $options['margin'] ?? 0 );
            $price = $this->apply_rounding( $price, $options['rounding'] ?? '0.99' );
            $variation->set_regular_price( (string) $price );
            $variation->set_manage_stock( true );
            $variation->set_stock_quantity( (int) ( $variation_data['stock'] ?? 0 ) );
            $variation->set_attributes( $variation_data['attributes'] ?? array() );
            $variation->save();
        }
    }

    private function apply_margin( float $price, float $margin ): float {
        return $price + ( $price * ( $margin / 100 ) );
    }

    private function apply_rounding( float $price, string $rounding ): float {
        $rounded = floor( $price ) + (float) $rounding;
        return $rounded > $price ? $rounded : $price;
    }
}
