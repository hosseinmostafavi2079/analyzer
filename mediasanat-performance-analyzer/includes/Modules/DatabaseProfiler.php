<?php
namespace Mediasanat\PA\Modules;

if ( ! defined( 'ABSPATH' ) ) exit;

class DatabaseProfiler {
    
    public function get_autoload_size() {
        global $wpdb;
        $query = "SELECT SUM(LENGTH(option_value)) FROM {$wpdb->options} WHERE autoload = 'yes' OR autoload = 'on'";
        $size_in_bytes = $wpdb->get_var( $query );
        return $size_in_bytes ? round( $size_in_bytes / ( 1024 * 1024 ), 2 ) : 0;
    }

    public function count_expired_transients() {
        global $wpdb;
        $now = time();
        $query = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s AND option_value < %d",
            '_transient_timeout_%', $now
        );
        return (int) $wpdb->get_var( $query );
    }
}