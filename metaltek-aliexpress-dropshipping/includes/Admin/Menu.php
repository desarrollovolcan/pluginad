<?php

namespace MAD\Admin;

class Menu {
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'register_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        new SettingsPage();
        new ProductImportPage();
        new OrdersPage();
        new LogsPage();
    }

    public function register_menu(): void {
        add_menu_page(
            __( 'AliExpress Dropshipping', 'metaltek-aliexpress-dropshipping' ),
            __( 'AliExpress Dropshipping', 'metaltek-aliexpress-dropshipping' ),
            'manage_mad_dropshipping',
            'mad-dropshipping',
            array( $this, 'render_settings' ),
            'dashicons-cart',
            56
        );

        add_submenu_page(
            'mad-dropshipping',
            __( 'Settings', 'metaltek-aliexpress-dropshipping' ),
            __( 'Settings', 'metaltek-aliexpress-dropshipping' ),
            'manage_mad_dropshipping',
            'mad-dropshipping',
            array( $this, 'render_settings' )
        );

        add_submenu_page(
            'mad-dropshipping',
            __( 'Import Products', 'metaltek-aliexpress-dropshipping' ),
            __( 'Import Products', 'metaltek-aliexpress-dropshipping' ),
            'manage_mad_dropshipping',
            'mad-import-products',
            array( $this, 'render_import' )
        );

        add_submenu_page(
            'mad-dropshipping',
            __( 'Orders', 'metaltek-aliexpress-dropshipping' ),
            __( 'Orders', 'metaltek-aliexpress-dropshipping' ),
            'manage_mad_dropshipping',
            'mad-orders',
            array( $this, 'render_orders' )
        );

        add_submenu_page(
            'mad-dropshipping',
            __( 'Logs', 'metaltek-aliexpress-dropshipping' ),
            __( 'Logs', 'metaltek-aliexpress-dropshipping' ),
            'manage_mad_dropshipping',
            'mad-logs',
            array( $this, 'render_logs' )
        );
    }

    public function enqueue_assets( string $hook ): void {
        if ( strpos( $hook, 'mad-' ) === false ) {
            return;
        }

        wp_enqueue_style( 'mad-admin' );
        wp_enqueue_script( 'mad-admin' );
    }

    public function render_settings(): void {
        ( new SettingsPage() )->render();
    }

    public function render_import(): void {
        ( new ProductImportPage() )->render();
    }

    public function render_orders(): void {
        ( new OrdersPage() )->render();
    }

    public function render_logs(): void {
        ( new LogsPage() )->render();
    }
}
