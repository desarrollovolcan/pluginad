<?php

namespace MAD\Admin;

use MAD\DB\Repositories;
use MAD\Sync\Queue;

class OrdersPage {
    public function __construct() {
        add_action( 'admin_post_mad_retry_order', array( $this, 'handle_retry' ) );
        add_action( 'admin_post_mad_sync_order', array( $this, 'handle_sync' ) );
    }

    public function render(): void {
        if ( ! current_user_can( 'manage_mad_dropshipping' ) ) {
            wp_die( esc_html__( 'You do not have permission to access this page.', 'metaltek-aliexpress-dropshipping' ) );
        }

        $orders = Repositories::get_orders( 50 );

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'AliExpress Orders', 'metaltek-aliexpress-dropshipping' ) . '</h1>';
        echo '<table class="widefat striped">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__( 'WC Order', 'metaltek-aliexpress-dropshipping' ) . '</th>';
        echo '<th>' . esc_html__( 'AE Order', 'metaltek-aliexpress-dropshipping' ) . '</th>';
        echo '<th>' . esc_html__( 'AE Status', 'metaltek-aliexpress-dropshipping' ) . '</th>';
        echo '<th>' . esc_html__( 'Tracking', 'metaltek-aliexpress-dropshipping' ) . '</th>';
        echo '<th>' . esc_html__( 'Last Sync', 'metaltek-aliexpress-dropshipping' ) . '</th>';
        echo '<th>' . esc_html__( 'Actions', 'metaltek-aliexpress-dropshipping' ) . '</th>';
        echo '</tr></thead><tbody>';

        if ( empty( $orders ) ) {
            echo '<tr><td colspan="6">' . esc_html__( 'No orders found.', 'metaltek-aliexpress-dropshipping' ) . '</td></tr>';
        } else {
            foreach ( $orders as $order ) {
                echo '<tr>';
                echo '<td>' . esc_html( $order['wc_order_id'] ) . '</td>';
                echo '<td>' . esc_html( $order['ae_order_id'] ?? '' ) . '</td>';
                echo '<td>' . esc_html( $order['ae_status'] ?? '' ) . '</td>';
                echo '<td>' . esc_html( $order['tracking_number'] ?? '' ) . '</td>';
                echo '<td>' . esc_html( $order['last_sync_at'] ?? '' ) . '</td>';
                echo '<td>';
                echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="mad-inline">';
                wp_nonce_field( 'mad_retry_order', 'mad_nonce' );
                echo '<input type="hidden" name="action" value="mad_retry_order" />';
                echo '<input type="hidden" name="order_id" value="' . esc_attr( $order['wc_order_id'] ) . '" />';
                submit_button( __( 'Retry', 'metaltek-aliexpress-dropshipping' ), 'secondary', 'submit', false );
                echo '</form>';

                echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="mad-inline">';
                wp_nonce_field( 'mad_sync_order', 'mad_nonce' );
                echo '<input type="hidden" name="action" value="mad_sync_order" />';
                echo '<input type="hidden" name="order_id" value="' . esc_attr( $order['wc_order_id'] ) . '" />';
                submit_button( __( 'Sync Status', 'metaltek-aliexpress-dropshipping' ), 'secondary', 'submit', false );
                echo '</form>';
                echo '</td>';
                echo '</tr>';
            }
        }

        echo '</tbody></table>';
        echo '</div>';
    }

    public function handle_retry(): void {
        if ( ! current_user_can( 'manage_mad_dropshipping' ) ) {
            wp_die( esc_html__( 'Permission denied.', 'metaltek-aliexpress-dropshipping' ) );
        }
        check_admin_referer( 'mad_retry_order', 'mad_nonce' );
        $order_id = absint( $_POST['order_id'] ?? 0 );
        if ( $order_id ) {
            Queue::enqueue( 'push_order', (string) $order_id );
        }
        wp_safe_redirect( admin_url( 'admin.php?page=mad-orders&retry=1' ) );
        exit;
    }

    public function handle_sync(): void {
        if ( ! current_user_can( 'manage_mad_dropshipping' ) ) {
            wp_die( esc_html__( 'Permission denied.', 'metaltek-aliexpress-dropshipping' ) );
        }
        check_admin_referer( 'mad_sync_order', 'mad_nonce' );
        $order_id = absint( $_POST['order_id'] ?? 0 );
        if ( $order_id ) {
            Queue::enqueue( 'sync_order', (string) $order_id );
        }
        wp_safe_redirect( admin_url( 'admin.php?page=mad-orders&sync=1' ) );
        exit;
    }
}
