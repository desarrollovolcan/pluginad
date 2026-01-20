<?php

namespace MAD\Woo;

use MAD\Sync\Queue;

class Webhooks {
    public static function handle_status_change( int $order_id, string $old_status, string $new_status, $order ): void {
        $settings = get_option( 'mad_settings', array() );
        $trigger = $settings['order_status_trigger'] ?? 'processing';
        if ( $new_status !== $trigger ) {
            return;
        }

        Queue::enqueue( 'push_order', (string) $order_id );
    }
}
