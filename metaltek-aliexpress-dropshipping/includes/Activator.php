<?php

namespace MAD;

use MAD\DB\Schema;
use MAD\Sync\Scheduler;

class Activator {
    public static function activate(): void {
        Schema::create_tables();
        self::add_capabilities();
        self::set_default_options();
        Scheduler::schedule();
    }

    private static function add_capabilities(): void {
        $role = get_role( 'administrator' );
        if ( $role && ! $role->has_cap( 'manage_mad_dropshipping' ) ) {
            $role->add_cap( 'manage_mad_dropshipping' );
        }
    }

    private static function set_default_options(): void {
        $defaults = array(
            'connector_mode' => 'mock',
            'currency' => 'USD',
            'margin_global' => 0,
            'rounding' => '0.99',
            'stock_policy' => 'use_ae',
            'cron_frequency' => 'hourly',
            'log_level' => 'info',
            'delete_on_uninstall' => 'no',
            'process_per_run' => 10,
            'order_status_trigger' => 'processing',
            'complete_on_delivery' => 'no',
        );

        add_option( 'mad_settings', $defaults );
    }
}
