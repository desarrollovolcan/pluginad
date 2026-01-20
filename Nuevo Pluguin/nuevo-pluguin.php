<?php
/*
Plugin Name: Nuevo Pluguin Dropshipping
Plugin URI: https://example.com
Description: Plugin moderno para conectar WooCommerce con AliExpress y gestionar dropshipping.
Version: 0.1.0
Author: Tu Equipo
License: GPLv2+
Text Domain: nuevo-pluguin
Domain Path: /languages
*/

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('NP_PLUGIN_FILE')) {
    define('NP_PLUGIN_FILE', __FILE__);
}

require_once __DIR__ . '/includes/class-np-aliexpress-client.php';

if (!class_exists('NP_Main')) {
    class NP_Main
    {
        public static function init()
        {
            add_action('plugins_loaded', array(__CLASS__, 'load_textdomain'));
            add_action('before_woocommerce_init', array(__CLASS__, 'declare_wc_compat'));
            add_action('admin_menu', array(__CLASS__, 'register_menu'));
            add_action('admin_init', array(__CLASS__, 'register_settings'));
        }

        public static function load_textdomain()
        {
            load_plugin_textdomain('nuevo-pluguin', false, basename(dirname(__FILE__)) . '/languages');
        }

        public static function declare_wc_compat()
        {
            if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
                \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
            }
        }

        public static function register_menu()
        {
            add_menu_page(
                __('Nuevo Pluguin', 'nuevo-pluguin'),
                __('Nuevo Pluguin', 'nuevo-pluguin'),
                'manage_woocommerce',
                'np_settings',
                array(__CLASS__, 'render_settings_page'),
                'dashicons-admin-generic'
            );
        }

        public static function register_settings()
        {
            register_setting('np_settings_group', 'np_settings', array(
                'type' => 'array',
                'sanitize_callback' => array(__CLASS__, 'sanitize_settings'),
                'default' => array(),
            ));

            add_settings_section(
                'np_section_aliexpress',
                __('Conexión AliExpress', 'nuevo-pluguin'),
                '__return_false',
                'np_settings'
            );

            add_settings_field(
                'np_app_key',
                __('APP Key', 'nuevo-pluguin'),
                array(__CLASS__, 'render_text_field'),
                'np_settings',
                'np_section_aliexpress',
                array('label_for' => 'np_app_key')
            );

            add_settings_field(
                'np_secret_key',
                __('Secret Key', 'nuevo-pluguin'),
                array(__CLASS__, 'render_text_field'),
                'np_settings',
                'np_section_aliexpress',
                array('label_for' => 'np_secret_key')
            );

            add_settings_field(
                'np_tracking_id',
                __('Tracking ID', 'nuevo-pluguin'),
                array(__CLASS__, 'render_text_field'),
                'np_settings',
                'np_section_aliexpress',
                array('label_for' => 'np_tracking_id')
            );

            add_settings_field(
                'np_store_url',
                __('Store URL', 'nuevo-pluguin'),
                array(__CLASS__, 'render_text_field'),
                'np_settings',
                'np_section_aliexpress',
                array('label_for' => 'np_store_url')
            );
        }

        public static function sanitize_settings($input)
        {
            $output = array();
            $output['np_app_key'] = isset($input['np_app_key']) ? sanitize_text_field($input['np_app_key']) : '';
            $output['np_secret_key'] = isset($input['np_secret_key']) ? sanitize_text_field($input['np_secret_key']) : '';
            $output['np_tracking_id'] = isset($input['np_tracking_id']) ? sanitize_text_field($input['np_tracking_id']) : '';
            $output['np_store_url'] = isset($input['np_store_url']) ? esc_url_raw($input['np_store_url']) : '';

            return $output;
        }

        public static function render_text_field($args)
        {
            $options = get_option('np_settings', array());
            $field_id = $args['label_for'];
            $value = isset($options[$field_id]) ? $options[$field_id] : '';
            printf(
                '<input class="regular-text" type="text" id="%1$s" name="np_settings[%1$s]" value="%2$s" />',
                esc_attr($field_id),
                esc_attr($value)
            );
        }

        public static function render_settings_page()
        {
            if (!class_exists('WooCommerce')) {
                echo '<div class="notice notice-error"><p>' . esc_html__('WooCommerce debe estar activo para usar este plugin.', 'nuevo-pluguin') . '</p></div>';
            }

            echo '<div class="wrap">';
            echo '<h1>' . esc_html__('Nuevo Pluguin', 'nuevo-pluguin') . '</h1>';
            echo '<p>' . esc_html__('Configura tu conexión con AliExpress para habilitar el dropshipping desde WooCommerce.', 'nuevo-pluguin') . '</p>';
            echo '<form method="post" action="options.php">';
            settings_fields('np_settings_group');
            do_settings_sections('np_settings');
            submit_button(__('Guardar cambios', 'nuevo-pluguin'));
            echo '</form>';
            echo '</div>';
        }
    }
}

NP_Main::init();
