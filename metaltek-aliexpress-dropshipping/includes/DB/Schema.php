<?php

namespace MAD\DB;

class Schema {
    public static function create_tables(): void {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();

        $tables = array();

        $tables[] = "CREATE TABLE {$wpdb->prefix}mad_products (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            ae_product_id VARCHAR(191) NOT NULL,
            wc_product_id BIGINT UNSIGNED NOT NULL,
            source_url TEXT NULL,
            last_sync_at DATETIME NULL,
            last_hash VARCHAR(191) NULL,
            status TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY ae_product_id (ae_product_id),
            KEY wc_product_id (wc_product_id)
        ) $charset_collate;";

        $tables[] = "CREATE TABLE {$wpdb->prefix}mad_orders (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            wc_order_id BIGINT UNSIGNED NOT NULL,
            ae_order_id VARCHAR(191) NULL,
            ae_status VARCHAR(191) NULL,
            tracking_number VARCHAR(191) NULL,
            carrier VARCHAR(191) NULL,
            last_sync_at DATETIME NULL,
            payload_request LONGTEXT NULL,
            payload_response LONGTEXT NULL,
            status TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY wc_order_id (wc_order_id),
            KEY ae_order_id (ae_order_id)
        ) $charset_collate;";

        $tables[] = "CREATE TABLE {$wpdb->prefix}mad_queue (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            job_type VARCHAR(50) NOT NULL,
            ref_id VARCHAR(191) NOT NULL,
            attempts INT NOT NULL DEFAULT 0,
            run_after DATETIME NOT NULL,
            locked_at DATETIME NULL,
            last_error TEXT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY job_type (job_type),
            KEY status (status)
        ) $charset_collate;";

        $tables[] = "CREATE TABLE {$wpdb->prefix}mad_logs (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            level VARCHAR(20) NOT NULL,
            context VARCHAR(50) NOT NULL,
            message TEXT NOT NULL,
            data LONGTEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY level (level),
            KEY context (context)
        ) $charset_collate;";

        foreach ( $tables as $sql ) {
            dbDelta( $sql );
        }
    }
}
