<?php

namespace MAD\Woo;

use MAD\Utils\Validators;

class OrderMapper {
    public function build_payload( \WC_Order $order ): array {
        $items = array();
        foreach ( $order->get_items() as $item ) {
            $product_id = $item->get_product_id();
            $ae_product_id = get_post_meta( $product_id, '_mad_ae_product_id', true );
            if ( ! $ae_product_id ) {
                continue;
            }
            $items[] = array(
                'ae_product_id' => $ae_product_id,
                'sku' => $item->get_variation_id() ? (string) $item->get_variation_id() : '',
                'quantity' => $item->get_quantity(),
            );
        }

        $address = array(
            'first_name' => $order->get_shipping_first_name(),
            'last_name' => $order->get_shipping_last_name(),
            'phone' => $order->get_billing_phone(),
            'country' => $order->get_shipping_country(),
            'state' => $order->get_shipping_state(),
            'city' => $order->get_shipping_city(),
            'address_1' => $order->get_shipping_address_1(),
            'address_2' => $order->get_shipping_address_2(),
            'postcode' => $order->get_shipping_postcode(),
        );

        Validators::validate_address( $address );

        return array(
            'order_id' => $order->get_id(),
            'buyer_notes' => $order->get_customer_note(),
            'line_items' => $items,
            'shipping_address' => $address,
        );
    }
}
