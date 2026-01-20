<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

$settings = get_option( 'mad_settings', array() );
if ( ( $settings['delete_on_uninstall'] ?? 'no' ) !== 'yes' ) {
    return;
}

global $wpdb;
$tables = array( 'mad_products', 'mad_orders', 'mad_queue', 'mad_logs' );
foreach ( $tables as $table ) {
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}{$table}" );
}

delete_option( 'mad_settings' );
