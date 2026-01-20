<?php

namespace MAD\Admin;

use MAD\Plugin;
use MAD\Woo\ProductMapper;
use MAD\Utils\Logger;

class ProductImportPage {
    public function __construct() {
        add_action( 'admin_post_mad_preview_product', array( $this, 'handle_preview' ) );
        add_action( 'admin_post_mad_search_products', array( $this, 'handle_search' ) );
        add_action( 'admin_post_mad_quick_import_product', array( $this, 'handle_quick_import' ) );
        add_action( 'admin_post_mad_import_product', array( $this, 'handle_import' ) );
    }

    public function render(): void {
        if ( ! current_user_can( 'manage_mad_dropshipping' ) ) {
            wp_die( esc_html__( 'No tienes permiso para acceder a esta página.', 'metaltek-aliexpress-dropshipping' ) );
        }

        $preview = get_transient( 'mad_preview_product' );
        $search_results = get_transient( 'mad_search_results' );
        $settings = get_option( 'mad_settings', array() );

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Importar productos', 'metaltek-aliexpress-dropshipping' ) . '</h1>';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="mad-form">';
        wp_nonce_field( 'mad_search_products', 'mad_nonce' );
        echo '<input type="hidden" name="action" value="mad_search_products" />';
        echo '<p><label>' . esc_html__( 'Buscar por palabra clave', 'metaltek-aliexpress-dropshipping' ) . '</label><br />';
        echo '<input type="text" name="keyword" class="regular-text" /></p>';
        submit_button( __( 'Buscar productos', 'metaltek-aliexpress-dropshipping' ) );
        echo '</form>';

        if ( $search_results ) {
            echo '<h2>' . esc_html__( 'Resultados de búsqueda', 'metaltek-aliexpress-dropshipping' ) . '</h2>';
            echo '<table class="widefat striped mad-table"><thead><tr>';
            echo '<th>' . esc_html__( 'Imagen', 'metaltek-aliexpress-dropshipping' ) . '</th>';
            echo '<th>' . esc_html__( 'ID', 'metaltek-aliexpress-dropshipping' ) . '</th>';
            echo '<th>' . esc_html__( 'Título', 'metaltek-aliexpress-dropshipping' ) . '</th>';
            echo '<th>' . esc_html__( 'SKU', 'metaltek-aliexpress-dropshipping' ) . '</th>';
            echo '<th>' . esc_html__( 'Precio', 'metaltek-aliexpress-dropshipping' ) . '</th>';
            echo '<th>' . esc_html__( 'Stock', 'metaltek-aliexpress-dropshipping' ) . '</th>';
            echo '<th>' . esc_html__( 'Rating', 'metaltek-aliexpress-dropshipping' ) . '</th>';
            echo '<th>' . esc_html__( 'Pedidos', 'metaltek-aliexpress-dropshipping' ) . '</th>';
            echo '<th>' . esc_html__( 'Envío', 'metaltek-aliexpress-dropshipping' ) . '</th>';
            echo '<th>' . esc_html__( 'Acciones', 'metaltek-aliexpress-dropshipping' ) . '</th>';
            echo '</tr></thead><tbody>';
            foreach ( $search_results as $result ) {
                echo '<tr>';
                $image_url = $result['image'] ?? '';
                echo '<td>';
                if ( $image_url ) {
                    echo '<img src="' . esc_url( $image_url ) . '" class="mad-thumb" alt="" />';
                }
                echo '</td>';
                echo '<td>' . esc_html( $result['id'] ?? '' ) . '</td>';
                echo '<td>' . esc_html( $result['title'] ?? '' ) . '</td>';
                echo '<td>' . esc_html( $result['sku'] ?? '' ) . '</td>';
                echo '<td>' . esc_html( $result['price'] ?? '' ) . '</td>';
                echo '<td>' . esc_html( $result['stock'] ?? '' ) . '</td>';
                echo '<td>' . esc_html( $result['rating'] ?? '' ) . '</td>';
                echo '<td>' . esc_html( $result['orders'] ?? '' ) . '</td>';
                echo '<td>' . esc_html( $result['shipping_time'] ?? '' ) . '</td>';
                echo '<td>';
                echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="mad-inline">';
                wp_nonce_field( 'mad_preview_product', 'mad_nonce' );
                echo '<input type="hidden" name="action" value="mad_preview_product" />';
                echo '<input type="hidden" name="product_ref" value="' . esc_attr( $result['id'] ?? '' ) . '" />';
                submit_button( __( 'Previsualizar', 'metaltek-aliexpress-dropshipping' ), 'secondary', 'submit', false );
                echo '</form>';
                echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="mad-inline">';
                wp_nonce_field( 'mad_quick_import_product', 'mad_nonce' );
                echo '<input type="hidden" name="action" value="mad_quick_import_product" />';
                echo '<input type="hidden" name="product_ref" value="' . esc_attr( $result['id'] ?? '' ) . '" />';
                submit_button( __( 'Importar directo', 'metaltek-aliexpress-dropshipping' ), 'primary', 'submit', false );
                echo '</form>';
                if ( ! empty( $result['source_url'] ) ) {
                    echo '<a class="button button-link" href="' . esc_url( $result['source_url'] ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Ver en AliExpress', 'metaltek-aliexpress-dropshipping' ) . '</a>';
                }
                echo '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        }

        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="mad-form">';
        wp_nonce_field( 'mad_preview_product', 'mad_nonce' );
        echo '<input type="hidden" name="action" value="mad_preview_product" />';
        echo '<p><label>' . esc_html__( 'URL o ID del producto de AliExpress', 'metaltek-aliexpress-dropshipping' ) . '</label><br />';
        echo '<input type="text" name="product_ref" class="regular-text" /></p>';
        submit_button( __( 'Previsualizar', 'metaltek-aliexpress-dropshipping' ) );
        echo '</form>';

        if ( $preview ) {
            echo '<hr />';
            echo '<h2>' . esc_html__( 'Previsualización', 'metaltek-aliexpress-dropshipping' ) . '</h2>';
            $preview_image = $preview['images'][0] ?? '';
            if ( $preview_image ) {
                echo '<p><img src="' . esc_url( $preview_image ) . '" class="mad-preview" alt="" /></p>';
            }
            echo '<p><strong>' . esc_html( $preview['title'] ?? '' ) . '</strong></p>';
            echo '<p>' . esc_html__( 'Precio:', 'metaltek-aliexpress-dropshipping' ) . ' ' . esc_html( $preview['price'] ?? '' ) . '</p>';
            echo '<p>' . esc_html__( 'SKU:', 'metaltek-aliexpress-dropshipping' ) . ' ' . esc_html( $preview['sku'] ?? '' ) . '</p>';
            echo '<p>' . esc_html__( 'Stock:', 'metaltek-aliexpress-dropshipping' ) . ' ' . esc_html( $preview['stock'] ?? '' ) . '</p>';
            echo '<p>' . esc_html__( 'Rating:', 'metaltek-aliexpress-dropshipping' ) . ' ' . esc_html( $preview['rating'] ?? '' ) . '</p>';
            echo '<p>' . esc_html__( 'Pedidos:', 'metaltek-aliexpress-dropshipping' ) . ' ' . esc_html( $preview['orders'] ?? '' ) . '</p>';
            echo '<p>' . esc_html__( 'Categoría:', 'metaltek-aliexpress-dropshipping' ) . ' ' . esc_html( $preview['category'] ?? '' ) . '</p>';
            echo '<p>' . esc_html__( 'Envío:', 'metaltek-aliexpress-dropshipping' ) . ' ' . esc_html( $preview['shipping_time'] ?? '' ) . '</p>';
            if ( ! empty( $preview['source_url'] ) ) {
                echo '<p><a class="button button-link" href="' . esc_url( $preview['source_url'] ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Ver en AliExpress', 'metaltek-aliexpress-dropshipping' ) . '</a></p>';
            }
            echo '<p>' . esc_html__( 'Variaciones:', 'metaltek-aliexpress-dropshipping' ) . ' ' . esc_html( count( $preview['variations'] ?? array() ) ) . '</p>';

            echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="mad-form">';
            wp_nonce_field( 'mad_import_product', 'mad_nonce' );
            echo '<input type="hidden" name="action" value="mad_import_product" />';
            echo '<input type="hidden" name="product_ref" value="' . esc_attr( $preview['id'] ?? '' ) . '" />';
            echo '<p><label>' . esc_html__( 'Categoría', 'metaltek-aliexpress-dropshipping' ) . '</label><br />';
            wp_dropdown_categories( array( 'taxonomy' => 'product_cat', 'name' => 'category_id', 'show_option_none' => __( 'Seleccionar categoría', 'metaltek-aliexpress-dropshipping' ) ) );
            echo '</p>';
            echo '<p><label><input type="checkbox" name="import_images" value="1" checked /> ' . esc_html__( 'Importar imágenes', 'metaltek-aliexpress-dropshipping' ) . '</label></p>';
            echo '<p><label><input type="checkbox" name="import_description" value="1" checked /> ' . esc_html__( 'Importar descripción', 'metaltek-aliexpress-dropshipping' ) . '</label></p>';
            echo '<p><label>' . esc_html__( 'Aplicar margen (%)', 'metaltek-aliexpress-dropshipping' ) . '</label><br />';
            echo '<input type="number" step="0.01" name="margin" value="' . esc_attr( $settings['margin_global'] ?? 0 ) . '" /></p>';
            echo '<p><label>' . esc_html__( 'Redondeo', 'metaltek-aliexpress-dropshipping' ) . '</label><br />';
            echo '<select name="rounding">';
            foreach ( array( '0.00', '0.90', '0.99' ) as $round ) {
                echo '<option value="' . esc_attr( $round ) . '" ' . selected( $settings['rounding'] ?? '', $round, false ) . '>' . esc_html( $round ) . '</option>';
            }
            echo '</select></p>';
            submit_button( __( 'Importar', 'metaltek-aliexpress-dropshipping' ) );
            echo '</form>';
        }

        echo '</div>';
    }

    public function handle_preview(): void {
        if ( ! current_user_can( 'manage_mad_dropshipping' ) ) {
            wp_die( esc_html__( 'Permiso denegado.', 'metaltek-aliexpress-dropshipping' ) );
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

    public function handle_search(): void {
        if ( ! current_user_can( 'manage_mad_dropshipping' ) ) {
            wp_die( esc_html__( 'Permiso denegado.', 'metaltek-aliexpress-dropshipping' ) );
        }
        check_admin_referer( 'mad_search_products', 'mad_nonce' );

        $keyword = sanitize_text_field( wp_unslash( $_POST['keyword'] ?? '' ) );
        if ( empty( $keyword ) ) {
            wp_safe_redirect( admin_url( 'admin.php?page=mad-import-products&error=empty' ) );
            exit;
        }

        $connector = Plugin::connector();
        $results = $connector->searchProducts( $keyword );
        set_transient( 'mad_search_results', $results, 10 * MINUTE_IN_SECONDS );

        wp_safe_redirect( admin_url( 'admin.php?page=mad-import-products&search=1' ) );
        exit;
    }

    public function handle_import(): void {
        if ( ! current_user_can( 'manage_mad_dropshipping' ) ) {
            wp_die( esc_html__( 'Permiso denegado.', 'metaltek-aliexpress-dropshipping' ) );
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

    public function handle_quick_import(): void {
        if ( ! current_user_can( 'manage_mad_dropshipping' ) ) {
            wp_die( esc_html__( 'Permiso denegado.', 'metaltek-aliexpress-dropshipping' ) );
        }
        check_admin_referer( 'mad_quick_import_product', 'mad_nonce' );

        $product_ref = sanitize_text_field( wp_unslash( $_POST['product_ref'] ?? '' ) );
        if ( empty( $product_ref ) ) {
            wp_safe_redirect( admin_url( 'admin.php?page=mad-import-products&error=empty' ) );
            exit;
        }

        $settings = get_option( 'mad_settings', array() );
        $connector = Plugin::connector();
        $product = $connector->getProduct( $product_ref );
        $mapper = new ProductMapper();
        $result = $mapper->import( $product, array(
            'category_id' => 0,
            'margin' => (float) ( $settings['margin_global'] ?? 0 ),
            'rounding' => $settings['rounding'] ?? '0.99',
            'import_images' => true,
            'import_description' => true,
        ) );

        Logger::info( 'import', 'Producto importado directamente.', array( 'product' => $result ) );

        wp_safe_redirect( admin_url( 'admin.php?page=mad-import-products&imported=1' ) );
        exit;
    }
}
