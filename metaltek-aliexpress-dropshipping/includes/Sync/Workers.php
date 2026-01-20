<?php

namespace MAD\Sync;

use MAD\DB\Repositories;
use MAD\Plugin;
use MAD\Utils\Logger;
use MAD\Woo\OrderMapper;

class Workers {
    public static function process_queue(): void {
        $settings = get_option( 'mad_settings', array() );
        $limit = (int) ( $settings['process_per_run'] ?? 10 );
        $jobs = Queue::claim_jobs( $limit );

        foreach ( $jobs as $job ) {
            $attempts = (int) $job['attempts'];
            try {
                self::process_job( $job );
                Queue::mark_done( (int) $job['id'] );
            } catch ( \Throwable $e ) {
                $attempts++;
                Logger::error( 'sync', 'Job failed: ' . $e->getMessage(), array( 'job' => $job ) );
                if ( $attempts < 5 ) {
                    Queue::reschedule( (int) $job['id'], $attempts );
                } else {
                    Queue::mark_failed( (int) $job['id'], $e->getMessage(), $attempts );
                }
            }
        }
    }

    private static function process_job( array $job ): void {
        switch ( $job['job_type'] ) {
            case 'sync_product':
                self::sync_product( $job['ref_id'] );
                break;
            case 'push_order':
                self::push_order( $job['ref_id'] );
                break;
            case 'sync_order':
                self::sync_order( $job['ref_id'] );
                break;
            default:
                Logger::warn( 'sync', 'Unknown job type.', array( 'job_type' => $job['job_type'] ) );
                break;
        }
    }

    private static function sync_product( string $wc_product_id ): void {
        $ae_product_id = get_post_meta( (int) $wc_product_id, '_mad_ae_product_id', true );
        if ( ! $ae_product_id ) {
            return;
        }

        $connector = Plugin::connector();
        $data = $connector->syncProduct( $ae_product_id );
        $hash = md5( wp_json_encode( $data ) );

        $record = Repositories::get_product_by_wc_id( (int) $wc_product_id );
        if ( $record && $record['last_hash'] === $hash ) {
            Logger::info( 'sync', 'Product unchanged.', array( 'wc_product_id' => $wc_product_id ) );
            return;
        }

        $product = wc_get_product( (int) $wc_product_id );
        if ( ! $product ) {
            return;
        }

        if ( isset( $data['price'] ) ) {
            $product->set_regular_price( (string) $data['price'] );
        }
        if ( isset( $data['stock'] ) ) {
            $product->set_stock_quantity( (int) $data['stock'] );
        }
        $product->save();

        Repositories::update_product( (int) $record['id'], array(
            'last_sync_at' => current_time( 'mysql' ),
            'last_hash' => $hash,
        ) );

        update_post_meta( (int) $wc_product_id, '_mad_last_sync_at', current_time( 'mysql' ) );
        Logger::info( 'sync', 'Product synced.', array( 'wc_product_id' => $wc_product_id ) );
    }

    private static function push_order( string $wc_order_id ): void {
        $order = wc_get_order( (int) $wc_order_id );
        if ( ! $order ) {
            return;
        }

        $mapper = new OrderMapper();
        $payload = $mapper->build_payload( $order );
        $connector = Plugin::connector();
        $response = $connector->createOrder( $payload );

        if ( empty( $response['ae_order_id'] ) ) {
            throw new \RuntimeException( 'AliExpress order ID missing.' );
        }

        Repositories::upsert_order( array(
            'wc_order_id' => $order->get_id(),
            'ae_order_id' => $response['ae_order_id'],
            'ae_status' => $response['status'] ?? '',
            'tracking_number' => $response['tracking_number'] ?? '',
            'carrier' => $response['carrier'] ?? '',
            'last_sync_at' => current_time( 'mysql' ),
            'payload_request' => wp_json_encode( $payload ),
            'payload_response' => wp_json_encode( $response ),
            'status' => 1,
        ) );

        update_post_meta( $order->get_id(), '_mad_ae_order_id', $response['ae_order_id'] );
        update_post_meta( $order->get_id(), '_mad_ae_status', $response['status'] ?? '' );

        $order->add_order_note( __( 'AliExpress order created.', 'metaltek-aliexpress-dropshipping' ) );
    }

    private static function sync_order( string $wc_order_id ): void {
        $order = wc_get_order( (int) $wc_order_id );
        if ( ! $order ) {
            return;
        }

        $ae_order_id = get_post_meta( $order->get_id(), '_mad_ae_order_id', true );
        if ( ! $ae_order_id ) {
            return;
        }

        $connector = Plugin::connector();
        $response = $connector->getOrder( $ae_order_id );
        if ( empty( $response ) ) {
            return;
        }

        Repositories::upsert_order( array(
            'wc_order_id' => $order->get_id(),
            'ae_order_id' => $response['ae_order_id'] ?? $ae_order_id,
            'ae_status' => $response['status'] ?? '',
            'tracking_number' => $response['tracking_number'] ?? '',
            'carrier' => $response['carrier'] ?? '',
            'last_sync_at' => current_time( 'mysql' ),
            'payload_response' => wp_json_encode( $response ),
            'status' => 1,
        ) );

        update_post_meta( $order->get_id(), '_mad_ae_status', $response['status'] ?? '' );
        update_post_meta( $order->get_id(), '_mad_tracking_number', $response['tracking_number'] ?? '' );
        update_post_meta( $order->get_id(), '_mad_carrier', $response['carrier'] ?? '' );

        $order->add_order_note( __( 'AliExpress order status updated.', 'metaltek-aliexpress-dropshipping' ) );

        $settings = get_option( 'mad_settings', array() );
        if ( ( $settings['complete_on_delivery'] ?? 'no' ) === 'yes' && ( $response['status'] ?? '' ) === 'delivered' ) {
            $order->update_status( 'completed' );
        }
    }
}
