<?php

namespace MAD\Admin;

use MAD\Plugin;
use MAD\Woo\ProductMapper;
use MAD\Utils\Logger;

class ProductImportPage {
    public function __construct() {
        add_action( 'admin_post_mad_preview_product', array( $this, 'handle_preview' ) );
        add_action( 'admin_post_mad_import_product', array( $this, 'handle_import' ) );
    }

    public function render(): void {
        if ( ! current_user_can( 'manage_mad_dropshipping' ) ) {
            wp_die( esc_html__( 'You do not have permission to access this page.', 'metaltek-aliexpress-dropshipping' ) );
        }

        $preview = get_transient( 'mad_preview_product' );
        $settings = get_option( 'mad_settings', array() );

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Import Products', 'metaltek-aliexpress-dropshipping' ) . '</h1>';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="mad-form">';
        wp_nonce_field( 'mad_preview_product', 'mad_nonce' );
        echo '<input type="hidden" name="action" value="mad_preview_product" />';
        echo '<p><label>' . esc_html__( 'AliExpress Product URL or ID', 'metaltek-aliexpress-dropshipping' ) . '</label><br />';
        echo '<input type="text" name="product_ref" class="regular-text" /></p>';
        submit_button( __( 'Preview', 'metaltek-aliexpress-dropshipping' ) );
        echo '</form>';

        if ( $preview ) {
            echo '<hr />';
            echo '<h2>' . esc_html__( 'Preview', 'metaltek-aliexpress-dropshipping' ) . '</h2>';
            echo '<p><strong>' . esc_html( $preview['title'] ?? '' ) . '</strong></p>';
            echo '<p>' . esc_html__( 'Price:', 'metaltek-aliexpress-dropshipping' ) . ' ' . esc_html( $preview['price'] ?? '' ) . '</p>';
            echo '<p>' . esc_html__( 'Variations:', 'metaltek-aliexpress-dropshipping' ) . ' ' . esc_html( count( $preview['variations'] ?? array() ) ) . '</p>';

            echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="mad-form">';
            wp_nonce_field( 'mad_import_product', 'mad_nonce' );
            echo '<input type="hidden" name="action" value="mad_import_product" />';
            echo '<input type="hidden" name="product_ref" value="' . esc_attr( $preview['id'] ?? '' ) . '" />';
            echo '<p><label>' . esc_html__( 'Category', 'metaltek-aliexpress-dropshipping' ) . '</label><br />';
            wp_dropdown_categories( array( 'taxonomy' => 'product_cat', 'name' => 'category_id', 'show_option_none' => __( 'Select category', 'metaltek-aliexpress-dropshipping' ) ) );
            echo '</p>';
            echo '<p><label><input type="checkbox" name="import_images" value="1" checked /> ' . esc_html__( 'Import images', 'metaltek-aliexpress-dropshipping' ) . '</label></p>';
            echo '<p><label><input type="checkbox" name="import_description" value="1" checked /> ' . esc_html__( 'Import description', 'metaltek-aliexpress-dropshipping' ) . '</label></p>';
            echo '<p><label>' . esc_html__( 'Apply margin (%)', 'metaltek-aliexpress-dropshipping' ) . '</label><br />';
            echo '<input type="number" step="0.01" name="margin" value="' . esc_attr( $settings['margin_global'] ?? 0 ) . '" /></p>';
            echo '<p><label>' . esc_html__( 'Rounding', 'metaltek-aliexpress-dropshipping' ) . '</label><br />';
            echo '<select name="rounding">';
            foreach ( array( '0.00', '0.90', '0.99' ) as $round ) {
                echo '<option value="' . esc_attr( $round ) . '" ' . selected( $settings['rounding'] ?? '', $round, false ) . '>' . esc_html( $round ) . '</option>';
            }
            echo '</select></p>';
            submit_button( __( 'Import', 'metaltek-aliexpress-dropshipping' ) );
            echo '</form>';
        }

        echo '</div>';
    }

    public function handle_preview(): void {
        if ( ! current_user_can( 'manage_mad_dropshipping' ) ) {
            wp_die( esc_html__( 'Permission denied.', 'metaltek-aliexpress-dropshipping' ) );
        }
        check_admin_referer( 'mad_preview_product', 'mad_nonce' );

        $product_ref = sanitize_text_field( wp_unslash( $_POST['product_ref'] ?? '' ) );
        if ( empty( $product_ref ) ) {
            wp_safe_redirect( admin_url( 'admin.php?page=mad-import-products&error=empty' ) );
            exit;
        }

        $connector = Plugin::connector();
        $product = $connector->getProduct( $product_ref );
        set_transient( 'mad_preview_product', $product, 10 * MINUTE_IN_SECONDS );

        wp_safe_redirect( admin_url( 'admin.php?page=mad-import-products&preview=1' ) );
        exit;
    }

    public function handle_import(): void {
        if ( ! current_user_can( 'manage_mad_dropshipping' ) ) {
            wp_die( esc_html__( 'Permission denied.', 'metaltek-aliexpress-dropshipping' ) );
        }
        check_admin_referer( 'mad_import_product', 'mad_nonce' );

        $product_ref = sanitize_text_field( wp_unslash( $_POST['product_ref'] ?? '' ) );
        $category_id = absint( $_POST['category_id'] ?? 0 );
        $margin = floatval( $_POST['margin'] ?? 0 );
        $rounding = sanitize_text_field( wp_unslash( $_POST['rounding'] ?? '0.99' ) );
        $import_images = ! empty( $_POST['import_images'] );
        $import_description = ! empty( $_POST['import_description'] );

        $connector = Plugin::connector();
        $product = $connector->getProduct( $product_ref );
        $mapper = new ProductMapper();
        $result = $mapper->import( $product, array(
            'category_id' => $category_id,
            'margin' => $margin,
            'rounding' => $rounding,
            'import_images' => $import_images,
            'import_description' => $import_description,
        ) );

        Logger::info( 'import', 'Product imported.', array( 'product' => $result ) );

        delete_transient( 'mad_preview_product' );
        wp_safe_redirect( admin_url( 'admin.php?page=mad-import-products&imported=1' ) );
        exit;
    }
}
