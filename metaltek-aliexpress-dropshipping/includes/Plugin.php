<?php

namespace MAD;

use MAD\Admin\Menu;
use MAD\AliExpress\ConnectorInterface;
use MAD\AliExpress\MockConnector;
use MAD\AliExpress\OfficialApiConnector;
use MAD\Sync\Scheduler;
use MAD\Woo\Webhooks;
use MAD\Utils\Logger;

class Plugin {
    private static ?Plugin $instance = null;

    private function __construct() {}

    public static function instance(): Plugin {
        if ( null === self::$instance ) {
            self::$instance = new self();
            self::$instance->init();
        }

        return self::$instance;
    }

    private function init(): void {
        load_plugin_textdomain( 'metaltek-aliexpress-dropshipping', false, dirname( plugin_basename( MAD_PLUGIN_FILE ) ) . '/languages' );

        add_action( 'init', array( $this, 'register_assets' ) );
        add_action( 'init', array( $this, 'register_rest' ) );
        add_action( 'init', array( $this, 'register_order_hooks' ) );

        if ( is_admin() ) {
            new Menu();
        }

        Scheduler::init();
    }

    public function register_assets(): void {
        wp_register_style( 'mad-admin', MAD_PLUGIN_URL . 'assets/admin.css', array(), MAD_VERSION );
        wp_register_script( 'mad-admin', MAD_PLUGIN_URL . 'assets/admin.js', array( 'jquery' ), MAD_VERSION, true );
    }

    public function register_rest(): void {
        register_rest_route(
            'mad/v1',
            '/auth/callback',
            array(
                'methods' => 'POST',
                'callback' => array( $this, 'handle_auth_callback' ),
                'permission_callback' => '__return_true',
            )
        );

        register_rest_route(
            'mad/v1',
            '/webhook/aliexpress',
            array(
                'methods' => 'POST',
                'callback' => array( $this, 'handle_webhook' ),
                'permission_callback' => array( $this, 'verify_webhook' ),
            )
        );
    }

    public function register_order_hooks(): void {
        add_action( 'woocommerce_order_status_changed', array( Webhooks::class, 'handle_status_change' ), 10, 4 );
    }

    public function handle_auth_callback( $request ) {
        Logger::info( 'auth', 'Auth callback received.', array( 'payload' => $request->get_json_params() ) );
        return rest_ensure_response( array( 'status' => 'ok' ) );
    }

    public function handle_webhook( $request ) {
        Logger::info( 'sync', 'Webhook received.', array( 'payload' => $request->get_json_params() ) );
        return rest_ensure_response( array( 'status' => 'ok' ) );
    }

    public function verify_webhook(): bool {
        return current_user_can( 'manage_mad_dropshipping' );
    }

    public static function connector(): ConnectorInterface {
        $settings = get_option( 'mad_settings', array() );
        $mode = $settings['connector_mode'] ?? 'mock';
        if ( 'official_api' === $mode ) {
            return new OfficialApiConnector();
        }

        return new MockConnector();
    }
}
