<?php

namespace MAD\DB;

class Repositories {
    public static function upsert_product( array $data ): void {
        global $wpdb;
        $table = $wpdb->prefix . 'mad_products';
        $existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE ae_product_id = %s", $data['ae_product_id'] ) );
        if ( $existing ) {
            $wpdb->update( $table, $data, array( 'id' => $existing ) );
        } else {
            $wpdb->insert( $table, $data );
        }
    }

    public static function update_product( int $id, array $data ): void {
        global $wpdb;
        $table = $wpdb->prefix . 'mad_products';
        $wpdb->update( $table, $data, array( 'id' => $id ) );
    }

    public static function get_products( int $limit = 50 ): array {
        global $wpdb;
        $table = $wpdb->prefix . 'mad_products';
        return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} ORDER BY id DESC LIMIT %d", $limit ), ARRAY_A );
    }

    public static function get_product_by_wc_id( int $wc_id ): ?array {
        global $wpdb;
        $table = $wpdb->prefix . 'mad_products';
        $result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE wc_product_id = %d", $wc_id ), ARRAY_A );
        return $result ?: null;
    }

    public static function upsert_order( array $data ): void {
        global $wpdb;
        $table = $wpdb->prefix . 'mad_orders';
        $existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE wc_order_id = %d", $data['wc_order_id'] ) );
        if ( $existing ) {
            $wpdb->update( $table, $data, array( 'id' => $existing ) );
        } else {
            $wpdb->insert( $table, $data );
        }
    }

    public static function get_orders( int $limit = 50 ): array {
        global $wpdb;
        $table = $wpdb->prefix . 'mad_orders';
        return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} ORDER BY id DESC LIMIT %d", $limit ), ARRAY_A );
    }

    public static function insert_queue( array $data ): void {
        global $wpdb;
        $table = $wpdb->prefix . 'mad_queue';
        $wpdb->insert( $table, $data );
    }

    public static function claim_queue_jobs( int $limit ): array {
        global $wpdb;
        $table = $wpdb->prefix . 'mad_queue';
        $now = gmdate( 'Y-m-d H:i:s' );
        $jobs = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE status = 'pending' AND run_after <= %s ORDER BY id ASC LIMIT %d", $now, $limit ), ARRAY_A );
        if ( empty( $jobs ) ) {
            return array();
        }
        $ids = wp_list_pluck( $jobs, 'id' );
        $ids_placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
        $wpdb->query( $wpdb->prepare( "UPDATE {$table} SET status = 'running', locked_at = %s WHERE id IN ({$ids_placeholders})", array_merge( array( $now ), $ids ) ) );
        return $jobs;
    }

    public static function update_queue( int $id, array $data ): void {
        global $wpdb;
        $table = $wpdb->prefix . 'mad_queue';
        $wpdb->update( $table, $data, array( 'id' => $id ) );
    }

    public static function clear_queue(): void {
        global $wpdb;
        $table = $wpdb->prefix . 'mad_queue';
        $wpdb->query( "TRUNCATE TABLE {$table}" );
    }

    public static function insert_log( array $data ): void {
        global $wpdb;
        $table = $wpdb->prefix . 'mad_logs';
        $wpdb->insert( $table, $data );
    }

    public static function get_logs( array $filters ): array {
        global $wpdb;
        $table = $wpdb->prefix . 'mad_logs';
        $where = array();
        $params = array();

        if ( ! empty( $filters['level'] ) ) {
            $where[] = 'level = %s';
            $params[] = $filters['level'];
        }
        if ( ! empty( $filters['context'] ) ) {
            $where[] = 'context = %s';
            $params[] = $filters['context'];
        }
        if ( ! empty( $filters['date'] ) ) {
            $where[] = 'DATE(created_at) = %s';
            $params[] = $filters['date'];
        }

        $sql = "SELECT * FROM {$table}";
        if ( $where ) {
            $sql .= ' WHERE ' . implode( ' AND ', $where );
        }
        $sql .= ' ORDER BY id DESC LIMIT 200';

        if ( $params ) {
            return $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A );
        }

        return $wpdb->get_results( $sql, ARRAY_A );
    }
}
