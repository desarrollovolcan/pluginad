<?php

namespace MAD\Admin;

use MAD\DB\Repositories;

class LogsPage {
    public function __construct() {
        add_action( 'admin_post_mad_export_logs', array( $this, 'handle_export' ) );
    }

    public function render(): void {
        if ( ! current_user_can( 'manage_mad_dropshipping' ) ) {
            wp_die( esc_html__( 'You do not have permission to access this page.', 'metaltek-aliexpress-dropshipping' ) );
        }

        $level = sanitize_text_field( wp_unslash( $_GET['level'] ?? '' ) );
        $context = sanitize_text_field( wp_unslash( $_GET['context'] ?? '' ) );
        $date = sanitize_text_field( wp_unslash( $_GET['date'] ?? '' ) );

        $logs = Repositories::get_logs( array(
            'level' => $level,
            'context' => $context,
            'date' => $date,
        ) );

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Logs', 'metaltek-aliexpress-dropshipping' ) . '</h1>';

        echo '<form method="get" action="' . esc_url( admin_url( 'admin.php' ) ) . '" class="mad-form">';
        echo '<input type="hidden" name="page" value="mad-logs" />';
        echo '<select name="level">';
        echo '<option value="">' . esc_html__( 'All levels', 'metaltek-aliexpress-dropshipping' ) . '</option>';
        foreach ( array( 'error', 'warn', 'info', 'debug' ) as $lvl ) {
            echo '<option value="' . esc_attr( $lvl ) . '" ' . selected( $level, $lvl, false ) . '>' . esc_html( ucfirst( $lvl ) ) . '</option>';
        }
        echo '</select>';

        echo '<select name="context">';
        echo '<option value="">' . esc_html__( 'All contexts', 'metaltek-aliexpress-dropshipping' ) . '</option>';
        foreach ( array( 'import', 'sync', 'order', 'auth' ) as $ctx ) {
            echo '<option value="' . esc_attr( $ctx ) . '" ' . selected( $context, $ctx, false ) . '>' . esc_html( ucfirst( $ctx ) ) . '</option>';
        }
        echo '</select>';

        echo '<input type="date" name="date" value="' . esc_attr( $date ) . '" />';
        submit_button( __( 'Filter', 'metaltek-aliexpress-dropshipping' ), 'secondary', 'submit', false );
        echo '</form>';

        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="mad-inline">';
        wp_nonce_field( 'mad_export_logs', 'mad_nonce' );
        echo '<input type="hidden" name="action" value="mad_export_logs" />';
        submit_button( __( 'Export CSV', 'metaltek-aliexpress-dropshipping' ), 'secondary', 'submit', false );
        echo '</form>';

        echo '<table class="widefat striped">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__( 'Date', 'metaltek-aliexpress-dropshipping' ) . '</th>';
        echo '<th>' . esc_html__( 'Level', 'metaltek-aliexpress-dropshipping' ) . '</th>';
        echo '<th>' . esc_html__( 'Context', 'metaltek-aliexpress-dropshipping' ) . '</th>';
        echo '<th>' . esc_html__( 'Message', 'metaltek-aliexpress-dropshipping' ) . '</th>';
        echo '</tr></thead><tbody>';

        if ( empty( $logs ) ) {
            echo '<tr><td colspan="4">' . esc_html__( 'No logs found.', 'metaltek-aliexpress-dropshipping' ) . '</td></tr>';
        } else {
            foreach ( $logs as $log ) {
                echo '<tr>';
                echo '<td>' . esc_html( $log['created_at'] ) . '</td>';
                echo '<td>' . esc_html( $log['level'] ) . '</td>';
                echo '<td>' . esc_html( $log['context'] ) . '</td>';
                echo '<td>' . esc_html( $log['message'] ) . '</td>';
                echo '</tr>';
            }
        }

        echo '</tbody></table>';
        echo '</div>';
    }

    public function handle_export(): void {
        if ( ! current_user_can( 'manage_mad_dropshipping' ) ) {
            wp_die( esc_html__( 'Permission denied.', 'metaltek-aliexpress-dropshipping' ) );
        }
        check_admin_referer( 'mad_export_logs', 'mad_nonce' );

        $logs = Repositories::get_logs( array() );

        header( 'Content-Type: text/csv' );
        header( 'Content-Disposition: attachment; filename="mad-logs.csv"' );

        $output = fopen( 'php://output', 'w' );
        fputcsv( $output, array( 'date', 'level', 'context', 'message', 'data' ) );
        foreach ( $logs as $log ) {
            fputcsv( $output, array( $log['created_at'], $log['level'], $log['context'], $log['message'], $log['data'] ) );
        }
        fclose( $output );
        exit;
    }
}
