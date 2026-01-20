<?php

namespace MAD\Sync;

use MAD\DB\Repositories;

class Queue {
    public static function enqueue( string $job_type, string $ref_id, int $delay_seconds = 0 ): void {
        $run_after = gmdate( 'Y-m-d H:i:s', time() + $delay_seconds );
        Repositories::insert_queue( array(
            'job_type' => $job_type,
            'ref_id' => $ref_id,
            'attempts' => 0,
            'run_after' => $run_after,
            'status' => 'pending',
        ) );
    }

    public static function claim_jobs( int $limit ): array {
        return Repositories::claim_queue_jobs( $limit );
    }

    public static function mark_done( int $id ): void {
        Repositories::update_queue( $id, array( 'status' => 'done' ) );
    }

    public static function mark_failed( int $id, string $error, int $attempts ): void {
        Repositories::update_queue( $id, array(
            'status' => 'failed',
            'last_error' => $error,
            'attempts' => $attempts,
        ) );
    }

    public static function reschedule( int $id, int $attempts ): void {
        $delay = pow( 2, $attempts ) * MINUTE_IN_SECONDS;
        Repositories::update_queue( $id, array(
            'status' => 'pending',
            'attempts' => $attempts,
            'run_after' => gmdate( 'Y-m-d H:i:s', time() + $delay ),
        ) );
    }

    public static function enqueue_bulk_sync(): void {
        $products = Repositories::get_products( 200 );
        foreach ( $products as $product ) {
            self::enqueue( 'sync_product', (string) $product['wc_product_id'] );
        }
    }

    public static function enqueue_order_syncs(): void {
        $orders = Repositories::get_orders_to_sync( 200 );
        foreach ( $orders as $order ) {
            self::enqueue( 'sync_order', (string) $order['wc_order_id'] );
        }
    }

    public static function clear(): void {
        Repositories::clear_queue();
    }
}
