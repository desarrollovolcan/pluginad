<?php

namespace MAD\Sync;

class Scheduler {
    public static function init(): void {
        add_filter( 'cron_schedules', array( __CLASS__, 'register_intervals' ) );
        add_action( 'mad_cron_process_queue', array( Workers::class, 'process_queue' ) );
        add_action( 'mad_cron_enqueue_order_sync', array( Queue::class, 'enqueue_order_syncs' ) );
    }

    public static function schedule(): void {
        if ( ! wp_next_scheduled( 'mad_cron_process_queue' ) ) {
            $settings = get_option( 'mad_settings', array() );
            $frequency = $settings['cron_frequency'] ?? 'hourly';
            wp_schedule_event( time() + MINUTE_IN_SECONDS, $frequency, 'mad_cron_process_queue' );
        }
        if ( ! wp_next_scheduled( 'mad_cron_enqueue_order_sync' ) ) {
            $settings = get_option( 'mad_settings', array() );
            $frequency = $settings['cron_frequency'] ?? 'hourly';
            wp_schedule_event( time() + ( 2 * MINUTE_IN_SECONDS ), $frequency, 'mad_cron_enqueue_order_sync' );
        }
    }

    public static function clear(): void {
        $timestamp = wp_next_scheduled( 'mad_cron_process_queue' );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, 'mad_cron_process_queue' );
        }
        $sync_timestamp = wp_next_scheduled( 'mad_cron_enqueue_order_sync' );
        if ( $sync_timestamp ) {
            wp_unschedule_event( $sync_timestamp, 'mad_cron_enqueue_order_sync' );
        }
    }

    public static function register_intervals( array $schedules ): array {
        $schedules['fifteen_minutes'] = array(
            'interval' => 15 * MINUTE_IN_SECONDS,
            'display' => __( 'Cada 15 minutos', 'metaltek-aliexpress-dropshipping' ),
        );
        $schedules['six_hours'] = array(
            'interval' => 6 * HOUR_IN_SECONDS,
            'display' => __( 'Cada 6 horas', 'metaltek-aliexpress-dropshipping' ),
        );

        return $schedules;
    }
}
