<?php

namespace MAD\Admin;

use MAD\Utils\Crypto;
use MAD\Utils\Logger;

class SettingsPage {
    public function __construct() {
        add_action( 'admin_post_mad_save_settings', array( $this, 'handle_save' ) );
        add_action( 'admin_post_mad_test_connection', array( $this, 'handle_test' ) );
        add_action( 'admin_post_mad_resync_all', array( $this, 'handle_resync' ) );
        add_action( 'admin_post_mad_clear_queue', array( $this, 'handle_clear_queue' ) );
    }

    public function render(): void {
        if ( ! current_user_can( 'manage_mad_dropshipping' ) ) {
            wp_die( esc_html__( 'No tienes permiso para acceder a esta página.', 'metaltek-aliexpress-dropshipping' ) );
        }

        $settings = get_option( 'mad_settings', array() );
        $redirect_uri = admin_url( 'admin-post.php?action=mad_oauth_callback' );
        $masked_token = ! empty( $settings['access_token'] ) ? '********' : '';
        $masked_refresh = ! empty( $settings['refresh_token'] ) ? '********' : '';
        $masked_secret = ! empty( $settings['client_secret'] ) ? '********' : '';

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Ajustes de AliExpress Dropshipping', 'metaltek-aliexpress-dropshipping' ) . '</h1>';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        wp_nonce_field( 'mad_save_settings', 'mad_nonce' );
        echo '<input type="hidden" name="action" value="mad_save_settings" />';

        echo '<table class="form-table">';
        echo '<tr><th>' . esc_html__( 'Modo del conector', 'metaltek-aliexpress-dropshipping' ) . '</th><td>';
        echo '<select name="connector_mode">';
        echo '<option value="official_api" ' . selected( $settings['connector_mode'] ?? '', 'official_api', false ) . '>' . esc_html__( 'API oficial', 'metaltek-aliexpress-dropshipping' ) . '</option>';
        echo '<option value="mock" ' . selected( $settings['connector_mode'] ?? '', 'mock', false ) . '>' . esc_html__( 'Simulador (Mock)', 'metaltek-aliexpress-dropshipping' ) . '</option>';
        echo '</select></td></tr>';

        echo '<tr><th>' . esc_html__( 'App Key / Client ID', 'metaltek-aliexpress-dropshipping' ) . '</th><td>';
        echo '<input type="text" name="client_id" value="' . esc_attr( $settings['client_id'] ?? '' ) . '" class="regular-text" /></td></tr>';

        echo '<tr><th>' . esc_html__( 'Client Secret', 'metaltek-aliexpress-dropshipping' ) . '</th><td>';
        echo '<input type="password" name="client_secret" value="" placeholder="' . esc_attr( $masked_secret ) . '" class="regular-text" /></td></tr>';

        echo '<tr><th>' . esc_html__( 'Redirect URI', 'metaltek-aliexpress-dropshipping' ) . '</th><td>';
        echo '<input type="text" readonly value="' . esc_url( $redirect_uri ) . '" class="regular-text" /></td></tr>';

        echo '<tr><th>' . esc_html__( 'Token de acceso', 'metaltek-aliexpress-dropshipping' ) . '</th><td>';
        echo '<input type="text" readonly value="' . esc_attr( $masked_token ) . '" class="regular-text" /></td></tr>';

        echo '<tr><th>' . esc_html__( 'Refresh Token', 'metaltek-aliexpress-dropshipping' ) . '</th><td>';
        echo '<input type="text" readonly value="' . esc_attr( $masked_refresh ) . '" class="regular-text" /></td></tr>';

        echo '<tr><th>' . esc_html__( 'Token expira en', 'metaltek-aliexpress-dropshipping' ) . '</th><td>';
        echo '<input type="text" name="expires_at" value="' . esc_attr( $settings['expires_at'] ?? '' ) . '" class="regular-text" /></td></tr>';

        echo '<tr><th>' . esc_html__( 'Moneda AliExpress', 'metaltek-aliexpress-dropshipping' ) . '</th><td>';
        echo '<input type="text" name="currency" value="' . esc_attr( $settings['currency'] ?? 'USD' ) . '" class="regular-text" /></td></tr>';

        echo '<tr><th>' . esc_html__( 'Margen global (%)', 'metaltek-aliexpress-dropshipping' ) . '</th><td>';
        echo '<input type="number" step="0.01" name="margin_global" value="' . esc_attr( $settings['margin_global'] ?? 0 ) . '" /></td></tr>';

        echo '<tr><th>' . esc_html__( 'Redondeo', 'metaltek-aliexpress-dropshipping' ) . '</th><td>';
        echo '<select name="rounding">';
        echo '<option value="0.00" ' . selected( $settings['rounding'] ?? '', '0.00', false ) . '>0.00</option>';
        echo '<option value="0.90" ' . selected( $settings['rounding'] ?? '', '0.90', false ) . '>0.90</option>';
        echo '<option value="0.99" ' . selected( $settings['rounding'] ?? '', '0.99', false ) . '>0.99</option>';
        echo '</select></td></tr>';

        echo '<tr><th>' . esc_html__( 'Política de stock', 'metaltek-aliexpress-dropshipping' ) . '</th><td>';
        echo '<select name="stock_policy">';
        echo '<option value="use_ae" ' . selected( $settings['stock_policy'] ?? '', 'use_ae', false ) . '>' . esc_html__( 'Usar stock de AliExpress', 'metaltek-aliexpress-dropshipping' ) . '</option>';
        echo '<option value="fixed" ' . selected( $settings['stock_policy'] ?? '', 'fixed', false ) . '>' . esc_html__( 'Stock fijo', 'metaltek-aliexpress-dropshipping' ) . '</option>';
        echo '</select></td></tr>';

        echo '<tr><th>' . esc_html__( 'Frecuencia de cron', 'metaltek-aliexpress-dropshipping' ) . '</th><td>';
        echo '<select name="cron_frequency">';
        echo '<option value="fifteen_minutes" ' . selected( $settings['cron_frequency'] ?? '', 'fifteen_minutes', false ) . '>' . esc_html__( '15 minutos', 'metaltek-aliexpress-dropshipping' ) . '</option>';
        echo '<option value="hourly" ' . selected( $settings['cron_frequency'] ?? '', 'hourly', false ) . '>' . esc_html__( '1 hora', 'metaltek-aliexpress-dropshipping' ) . '</option>';
        echo '<option value="six_hours" ' . selected( $settings['cron_frequency'] ?? '', 'six_hours', false ) . '>' . esc_html__( '6 horas', 'metaltek-aliexpress-dropshipping' ) . '</option>';
        echo '<option value="twicedaily" ' . selected( $settings['cron_frequency'] ?? '', 'twicedaily', false ) . '>' . esc_html__( '12 horas', 'metaltek-aliexpress-dropshipping' ) . '</option>';
        echo '<option value="daily" ' . selected( $settings['cron_frequency'] ?? '', 'daily', false ) . '>' . esc_html__( '24 horas', 'metaltek-aliexpress-dropshipping' ) . '</option>';
        echo '</select></td></tr>';

        echo '<tr><th>' . esc_html__( 'Nivel de logs', 'metaltek-aliexpress-dropshipping' ) . '</th><td>';
        echo '<select name="log_level">';
        foreach ( array( 'error', 'warn', 'info', 'debug' ) as $level ) {
            echo '<option value="' . esc_attr( $level ) . '" ' . selected( $settings['log_level'] ?? '', $level, false ) . '>' . esc_html( ucfirst( $level ) ) . '</option>';
        }
        echo '</select></td></tr>';

        echo '<tr><th>' . esc_html__( 'Procesar trabajos por ejecución', 'metaltek-aliexpress-dropshipping' ) . '</th><td>';
        echo '<input type="number" name="process_per_run" value="' . esc_attr( $settings['process_per_run'] ?? 10 ) . '" /></td></tr>';

        echo '<tr><th>' . esc_html__( 'Estado de pedido para enviar', 'metaltek-aliexpress-dropshipping' ) . '</th><td>';
        echo '<input type="text" name="order_status_trigger" value="' . esc_attr( $settings['order_status_trigger'] ?? 'processing' ) . '" /></td></tr>';

        echo '<tr><th>' . esc_html__( 'Completar al entregar', 'metaltek-aliexpress-dropshipping' ) . '</th><td>';
        echo '<select name="complete_on_delivery">';
        echo '<option value="no" ' . selected( $settings['complete_on_delivery'] ?? '', 'no', false ) . '>' . esc_html__( 'No', 'metaltek-aliexpress-dropshipping' ) . '</option>';
        echo '<option value="yes" ' . selected( $settings['complete_on_delivery'] ?? '', 'yes', false ) . '>' . esc_html__( 'Sí', 'metaltek-aliexpress-dropshipping' ) . '</option>';
        echo '</select></td></tr>';

        echo '</table>';
        submit_button( __( 'Guardar ajustes', 'metaltek-aliexpress-dropshipping' ) );
        echo '</form>';

        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="mad-inline">';
        wp_nonce_field( 'mad_test_connection', 'mad_nonce' );
        echo '<input type="hidden" name="action" value="mad_test_connection" />';
        submit_button( __( 'Probar conexión', 'metaltek-aliexpress-dropshipping' ), 'secondary', 'submit', false );
        echo '</form>';

        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="mad-inline">';
        wp_nonce_field( 'mad_resync_all', 'mad_nonce' );
        echo '<input type="hidden" name="action" value="mad_resync_all" />';
        submit_button( __( 'Re-sincronizar todo', 'metaltek-aliexpress-dropshipping' ), 'secondary', 'submit', false );
        echo '</form>';

        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="mad-inline">';
        wp_nonce_field( 'mad_clear_queue', 'mad_nonce' );
        echo '<input type="hidden" name="action" value="mad_clear_queue" />';
        submit_button( __( 'Vaciar cola', 'metaltek-aliexpress-dropshipping' ), 'delete', 'submit', false );
        echo '</form>';

        echo '</div>';
    }

    public function handle_save(): void {
        if ( ! current_user_can( 'manage_mad_dropshipping' ) ) {
            wp_die( esc_html__( 'Permiso denegado.', 'metaltek-aliexpress-dropshipping' ) );
        }
        check_admin_referer( 'mad_save_settings', 'mad_nonce' );

        $settings = get_option( 'mad_settings', array() );
        $settings['connector_mode'] = sanitize_text_field( wp_unslash( $_POST['connector_mode'] ?? 'mock' ) );
        $settings['client_id'] = sanitize_text_field( wp_unslash( $_POST['client_id'] ?? '' ) );
        $new_secret = sanitize_text_field( wp_unslash( $_POST['client_secret'] ?? '' ) );
        if ( ! empty( $new_secret ) ) {
            $settings['client_secret'] = $new_secret;
        }
        $settings['expires_at'] = sanitize_text_field( wp_unslash( $_POST['expires_at'] ?? '' ) );
        $settings['currency'] = sanitize_text_field( wp_unslash( $_POST['currency'] ?? 'USD' ) );
        $settings['margin_global'] = floatval( $_POST['margin_global'] ?? 0 );
        $settings['rounding'] = sanitize_text_field( wp_unslash( $_POST['rounding'] ?? '0.99' ) );
        $settings['stock_policy'] = sanitize_text_field( wp_unslash( $_POST['stock_policy'] ?? 'use_ae' ) );
        $settings['cron_frequency'] = sanitize_text_field( wp_unslash( $_POST['cron_frequency'] ?? 'hourly' ) );
        $settings['log_level'] = sanitize_text_field( wp_unslash( $_POST['log_level'] ?? 'info' ) );
        $settings['process_per_run'] = absint( $_POST['process_per_run'] ?? 10 );
        $settings['order_status_trigger'] = sanitize_text_field( wp_unslash( $_POST['order_status_trigger'] ?? 'processing' ) );
        $settings['complete_on_delivery'] = sanitize_text_field( wp_unslash( $_POST['complete_on_delivery'] ?? 'no' ) );

        if ( ! empty( $new_secret ) ) {
            $settings['client_secret'] = Crypto::encrypt( $new_secret );
        }

        update_option( 'mad_settings', $settings );
        wp_safe_redirect( admin_url( 'admin.php?page=mad-dropshipping&updated=1' ) );
        exit;
    }

    public function handle_test(): void {
        if ( ! current_user_can( 'manage_mad_dropshipping' ) ) {
            wp_die( esc_html__( 'Permiso denegado.', 'metaltek-aliexpress-dropshipping' ) );
        }
        check_admin_referer( 'mad_test_connection', 'mad_nonce' );

        $connector = \MAD\Plugin::connector();
        $result = $connector->testConnection();
        Logger::info( 'auth', 'Test connection executed.', $result );
        wp_safe_redirect( admin_url( 'admin.php?page=mad-dropshipping&test=1' ) );
        exit;
    }

    public function handle_resync(): void {
        if ( ! current_user_can( 'manage_mad_dropshipping' ) ) {
            wp_die( esc_html__( 'Permiso denegado.', 'metaltek-aliexpress-dropshipping' ) );
        }
        check_admin_referer( 'mad_resync_all', 'mad_nonce' );

        \MAD\Sync\Queue::enqueue_bulk_sync();
        wp_safe_redirect( admin_url( 'admin.php?page=mad-dropshipping&resync=1' ) );
        exit;
    }

    public function handle_clear_queue(): void {
        if ( ! current_user_can( 'manage_mad_dropshipping' ) ) {
            wp_die( esc_html__( 'Permiso denegado.', 'metaltek-aliexpress-dropshipping' ) );
        }
        check_admin_referer( 'mad_clear_queue', 'mad_nonce' );

        \MAD\Sync\Queue::clear();
        wp_safe_redirect( admin_url( 'admin.php?page=mad-dropshipping&queue=cleared' ) );
        exit;
    }
}
