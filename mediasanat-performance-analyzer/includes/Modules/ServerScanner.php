<?php
namespace Mediasanat\PA\Modules;

if ( ! defined( 'ABSPATH' ) ) exit;

class ServerScanner {

    public function get_server_health() {
        return [
            'php_version'      => PHP_VERSION,
            'memory_limit'     => ini_get('memory_limit'),
            'max_exec_time'    => (int) ini_get('max_execution_time'),
            'max_input_vars'   => (int) ini_get('max_input_vars'),
            'upload_max'       => ini_get('upload_max_filesize'),
            'post_max'         => ini_get('post_max_size'),
            'redis_active'     => class_exists('Redis'),
            'memcached_active' => class_exists('Memcached'),
            'opcache_active'   => $this->is_opcache_enabled(),
            'gd_active'        => extension_loaded('gd'),
            'imagick_active'   => extension_loaded('imagick'),
            'curl_active'      => function_exists('curl_version'),
            'is_https'         => $this->is_https(),
            'wp_version'       => get_bloginfo('version'),
            'wp_debug'         => defined('WP_DEBUG') && WP_DEBUG,
            'disk_free'        => $this->get_disk_free_space(),
            'server_software'  => isset($_SERVER['SERVER_SOFTWARE']) ? sanitize_text_field($_SERVER['SERVER_SOFTWARE']) : 'نامشخص',
            'active_plugins'   => $this->get_active_plugins_count(),
            'cron_events'      => $this->get_cron_events_count(),
            'page_cache'       => defined( 'WP_CACHE' ) && WP_CACHE,
            'object_cache'     => wp_using_ext_object_cache(),
        ];
    }

    private function is_opcache_enabled() {
        if ( function_exists('opcache_get_status') ) {
            $status = @opcache_get_status( false );
            return is_array($status) && ! empty($status['opcache_enabled']);
        }
        return false;
    }

    private function is_https() {
        return ( is_ssl() || strpos( home_url(), 'https://' ) === 0 );
    }

    private function get_disk_free_space() {
        if ( function_exists('disk_free_space') ) {
            $bytes = @disk_free_space( ABSPATH );
            if ( $bytes ) {
                return round( $bytes / (1024 * 1024 * 1024), 2 ) . ' GB';
            }
        }
        return 'نامشخص';
    }

    private function get_active_plugins_count() {
        $plugins = (array) get_option( 'active_plugins', [] );
        if ( is_multisite() ) $plugins = array_unique( array_merge( $plugins, array_keys( (array) get_site_option( 'active_sitewide_plugins', [] ) ) ) );
        return count( $plugins );
    }

    private function get_cron_events_count() {
        $cron = _get_cron_array();
        if ( ! is_array( $cron ) ) return 0;
        $count = 0;
        foreach ( $cron as $hooks ) foreach ( $hooks as $events ) $count += count( $events );
        return $count;
    }
}
