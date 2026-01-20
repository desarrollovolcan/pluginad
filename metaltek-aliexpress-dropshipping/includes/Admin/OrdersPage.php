<?php

namespace MAD\Admin;

use MAD\DB\Repositories;
use MAD\Sync\Queue;

class OrdersPage {
    public function __construct() {
        add_action( 'admin_post_mad_retry_order', array( $this, 'handle_retry' ) );
        add_action( 'admin_post_mad_sync_order', array( $this, 'handle_sync' ) );
        add_action( 'admin_post_mad_push_paid_order', array( $this, 'handle_push_paid' ) );
        add_action( 'admin_post_mad_push_paid_bulk', array( $this, 'handle_push_paid_bulk' ) );
    }

    public function render(): void {
        if ( ! current_user_can( 'manage_mad_dropshipping' ) ) {
            wp_die( esc_html__( 'No tienes permiso para acceder a esta página.', 'metaltek-aliexpress-dropshipping' ) );
        }

        $orders = Repositories::get_orders( 50 );
        $paid_orders = $this->get_paid_orders();

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Pedidos de AliExpress', 'metaltek-aliexpress-dropshipping' ) . '</h1>';
        echo '<table class="widefat striped">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__( 'Pedido WC', 'metaltek-aliexpress-dropshipping' ) . '</th>';
        echo '<th>' . esc_html__( 'Pedido AE', 'metaltek-aliexpress-dropshipping' ) . '</th>';
        echo '<th>' . esc_html__( 'Estado AE', 'metaltek-aliexpress-dropshipping' ) . '</th>';
        echo '<th>' . esc_html__( 'Tracking', 'metaltek-aliexpress-dropshipping' ) . '</th>';
        echo '<th>' . esc_html__( 'Última sincronización', 'metaltek-aliexpress-dropshipping' ) . '</th>';
        echo '<th>' . esc_html__( 'Acciones', 'metaltek-aliexpress-dropshipping' ) . '</th>';
        echo '</tr></thead><tbody>';

        if ( empty( $orders ) ) {
            echo '<tr><td colspan="6">' . esc_html__( 'No se encontraron pedidos.', 'metaltek-aliexpress-dropshipping' ) . '</td></tr>';
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
                submit_button( __( 'Reintentar', 'metaltek-aliexpress-dropshipping' ), 'secondary', 'submit', false );
                echo '</form>';

                echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="mad-inline">';
                wp_nonce_field( 'mad_sync_order', 'mad_nonce' );
                echo '<input type="hidden" name="action" value="mad_sync_order" />';
                echo '<input type="hidden" name="order_id" value="' . esc_attr( $order['wc_order_id'] ) . '" />';
                submit_button( __( 'Sincronizar estado', 'metaltek-aliexpress-dropshipping' ), 'secondary', 'submit', false );
                echo '</form>';
                echo '</td>';
                echo '</tr>';
            }
        }

        echo '</tbody></table>';

        echo '<h2>' . esc_html__( 'Pedidos pagados (WooCommerce)', 'metaltek-aliexpress-dropshipping' ) . '</h2>';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="mad-inline">';
        wp_nonce_field( 'mad_push_paid_bulk', 'mad_nonce' );
        echo '<input type="hidden" name="action" value="mad_push_paid_bulk" />';
        submit_button( __( 'Enviar todos los pagados', 'metaltek-aliexpress-dropshipping' ), 'primary', 'submit', false );
        echo '</form>';

        echo '<table class="widefat striped">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__( 'Pedido WC', 'metaltek-aliexpress-dropshipping' ) . '</th>';
        echo '<th>' . esc_html__( 'Cliente', 'metaltek-aliexpress-dropshipping' ) . '</th>';
        echo '<th>' . esc_html__( 'Total', 'metaltek-aliexpress-dropshipping' ) . '</th>';
        echo '<th>' . esc_html__( 'Estado', 'metaltek-aliexpress-dropshipping' ) . '</th>';
        echo '<th>' . esc_html__( 'Acciones', 'metaltek-aliexpress-dropshipping' ) . '</th>';
        echo '</tr></thead><tbody>';

        if ( empty( $paid_orders ) ) {
            echo '<tr><td colspan="5">' . esc_html__( 'No hay pedidos pagados pendientes.', 'metaltek-aliexpress-dropshipping' ) . '</td></tr>';
        } else {
            foreach ( $paid_orders as $order ) {
                echo '<tr>';
                echo '<td>' . esc_html( $order->get_id() ) . '</td>';
                echo '<td>' . esc_html( $order->get_formatted_billing_full_name() ) . '</td>';
                echo '<td>' . wp_kses_post( $order->get_formatted_order_total() ) . '</td>';
                echo '<td>' . esc_html( wc_get_order_status_name( $order->get_status() ) ) . '</td>';
                echo '<td>';
                echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="mad-inline">';
                wp_nonce_field( 'mad_push_paid_order', 'mad_nonce' );
                echo '<input type="hidden" name="action" value="mad_push_paid_order" />';
                echo '<input type="hidden" name="order_id" value="' . esc_attr( $order->get_id() ) . '" />';
                submit_button( __( 'Enviar a AliExpress', 'metaltek-aliexpress-dropshipping' ), 'secondary', 'submit', false );
                echo '</form>';
                echo '</td>';
                echo '</tr>';
            }
        }

        echo '</tbody></table>';
        echo '</div>';
    }

    private function get_paid_orders(): array {
        $statuses = function_exists( 'wc_get_is_paid_statuses' ) ? wc_get_is_paid_statuses() : array( 'processing', 'completed' );
        $args = array(
            'status' => $statuses,
            'limit' => 20,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => array(
                array(
                    'key' => '_mad_ae_order_id',
                    'compare' => 'NOT EXISTS',
                ),
            ),
        );

        return wc_get_orders( $args );
    }

    public function handle_retry(): void {
        if ( ! current_user_can( 'manage_mad_dropshipping' ) ) {
            wp_die( esc_html__( 'Permiso denegado.', 'metaltek-aliexpress-dropshipping' ) );
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
            wp_die( esc_html__( 'Permiso denegado.', 'metaltek-aliexpress-dropshipping' ) );
        }
        check_admin_referer( 'mad_sync_order', 'mad_nonce' );
        $order_id = absint( $_POST['order_id'] ?? 0 );
        if ( $order_id ) {
            Queue::enqueue( 'sync_order', (string) $order_id );
        }
        wp_safe_redirect( admin_url( 'admin.php?page=mad-orders&sync=1' ) );
        exit;
    }

    public function handle_push_paid(): void {
        if ( ! current_user_can( 'manage_mad_dropshipping' ) ) {
            wp_die( esc_html__( 'Permiso denegado.', 'metaltek-aliexpress-dropshipping' ) );
        }
        check_admin_referer( 'mad_push_paid_order', 'mad_nonce' );
        $order_id = absint( $_POST['order_id'] ?? 0 );
        if ( $order_id ) {
            Queue::enqueue( 'push_order', (string) $order_id );
        }
        wp_safe_redirect( admin_url( 'admin.php?page=mad-orders&push=1' ) );
        exit;
    }

    public function handle_push_paid_bulk(): void {
        if ( ! current_user_can( 'manage_mad_dropshipping' ) ) {
            wp_die( esc_html__( 'Permiso denegado.', 'metaltek-aliexpress-dropshipping' ) );
        }
        check_admin_referer( 'mad_push_paid_bulk', 'mad_nonce' );
        $orders = $this->get_paid_orders();
        foreach ( $orders as $order ) {
            Queue::enqueue( 'push_order', (string) $order->get_id() );
        }
        wp_safe_redirect( admin_url( 'admin.php?page=mad-orders&push=bulk' ) );
        exit;
    }
}
