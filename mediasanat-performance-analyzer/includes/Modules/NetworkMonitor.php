<?php
namespace Mediasanat\PA\Modules;

if ( ! defined( 'ABSPATH' ) ) exit;

class NetworkMonitor {

    private $option_name = 'ms_blocked_domains';
    private $log_name    = 'ms_ext_req_log';

    public function init() {
        // ثبت لحظه شروع هر درخواست خارجی (اصلاح باگ: این هوک قبلاً وصل نشده بود)
        add_filter( 'http_request_args', [ $this, 'mark_request_start' ], 10, 1 );
        // زمان‌بندی: ثبت لحظه پایان و نتیجه درخواست
        add_action( 'http_api_debug', [ $this, 'log_external_requests' ], 10, 5 );
        // مسدودسازی درخواست‌ها پیش از اجرای cURL
        add_filter( 'pre_http_request', [ $this, 'block_external_requests' ], 10, 3 );
        add_filter( 'script_loader_src', [ $this, 'filter_frontend_asset' ], 999 );
        add_filter( 'style_loader_src', [ $this, 'filter_frontend_asset' ], 999 );
    }

    public function log_external_requests( $response, $context, $class, $args, $url ) {
        $host = wp_parse_url( $url, PHP_URL_HOST );
        $home_host = wp_parse_url( home_url(), PHP_URL_HOST );

        // نادیده گرفتن درخواست‌های داخلی
        if ( empty( $host ) || $host === $home_host ) return;

        $logs = get_transient( $this->log_name );
        if ( ! is_array( $logs ) ) $logs = [];

        // محاسبه وضعیت و زمان پاسخ
        $duration = isset( $args['_ms_start'] ) ? round( microtime(true) - $args['_ms_start'], 2 ) : null;

        if ( is_wp_error( $response ) ) {
            $status  = 'خطا: ' . $response->get_error_message();
            $is_error = true;
        } else {
            $status  = 'کد پاسخ: ' . wp_remote_retrieve_response_code( $response );
            $is_error = false;
        }

        // اگر دامنه قبلاً ثبت شده، آمار تجمعی نگه‌دار
        $count = isset( $logs[ $host ]['count'] ) ? $logs[ $host ]['count'] + 1 : 1;

        $logs[ $host ] = [
            'url'      => $this->redact_url( $url ),
            'status'   => $status,
            'is_error' => $is_error,
            'duration' => $duration,   // زمان پاسخ (کلید تشخیص کندی)
            'count'    => $count,      // تعداد دفعات تماس
            'time'     => current_time( 'mysql' ),
        ];

        // نگه‌داری ۵۰ دامنه آخر (اصلاح باگ array_shift روی آرایه associative)
        if ( count( $logs ) > 50 ) {
            $logs = array_slice( $logs, -50, null, true );
        }

        set_transient( $this->log_name, $logs, DAY_IN_SECONDS );
    }

    public function block_external_requests( $pre, $parsed_args, $url ) {
        $blocked = get_option( $this->option_name, [] );
        $host = wp_parse_url( $url, PHP_URL_HOST );

        $home_host = wp_parse_url( home_url(), PHP_URL_HOST );
        $emergency = (bool) get_option( 'ms_resilience_mode', false );
        $allowlist = get_option( 'ms_resilience_allowlist', [] );
        $must_block = is_array( $blocked ) && in_array( $host, $blocked, true );

        if ( $emergency && $host && $host !== $home_host && ! in_array( $host, (array) $allowlist, true ) ) {
            $must_block = true;
        }

        if ( $must_block ) {
            return new \WP_Error(
                'mediasanat_blocked',
                "ارتباط با {$host} توسط پایشگر تاب‌آوری موستک مسدود شده است."
            );
        }
        return $pre;
    }

    public function filter_frontend_asset( $src ) {
        $scan_nonce = isset( $_GET['_ms_nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_ms_nonce'] ) ) : '';
        if ( isset( $_GET['ms_pa_scan'] ) && $scan_nonce && current_user_can( 'manage_options' ) && wp_verify_nonce( $scan_nonce, 'ms_pa_frontend_scan' ) ) return $src;
        $scan_token = isset( $_GET['ms_pa_token'] ) ? sanitize_key( wp_unslash( $_GET['ms_pa_token'] ) ) : '';
        if ( $scan_token && get_transient( 'ms_pa_scan_' . $scan_token ) ) return $src;
        if ( is_admin() || ! get_option( 'ms_resilience_mode', false ) ) return $src;
        $host = wp_parse_url( $src, PHP_URL_HOST );
        $home_host = wp_parse_url( home_url(), PHP_URL_HOST );
        $allowlist = (array) get_option( 'ms_resilience_allowlist', [] );
        if ( $host && $host !== $home_host && ! in_array( $host, $allowlist, true ) ) return false;
        return $src;
    }

    public function is_resilience_mode() {
        return (bool) get_option( 'ms_resilience_mode', false );
    }

    /**
     * ثبت زمان شروع درخواست (متصل به فیلتر http_request_args)
     */
    public function mark_request_start( $args ) {
        $args['_ms_start'] = microtime( true );
        return $args;
    }

    public function get_logs() {
        $logs = get_transient( $this->log_name );
        if ( ! is_array( $logs ) ) return [];
        // مرتب‌سازی: کُندترین دامنه‌ها اول
        uasort( $logs, function( $a, $b ) {
            return ( $b['duration'] ?? 0 ) <=> ( $a['duration'] ?? 0 );
        });
        return $logs;
    }

    public function get_blocked_domains() {
        $blocked = get_option( $this->option_name, [] );
        return is_array( $blocked ) ? $blocked : [];
    }

    public function clear_logs() {
        delete_transient( $this->log_name );
    }

    private function redact_url( $url ) {
        $parts = wp_parse_url( $url );
        if ( empty( $parts['host'] ) ) return '';
        $scheme = isset( $parts['scheme'] ) ? $parts['scheme'] . '://' : 'https://';
        $port   = isset( $parts['port'] ) ? ':' . $parts['port'] : '';
        $path   = isset( $parts['path'] ) ? $parts['path'] : '/';
        return $scheme . $parts['host'] . $port . $path;
    }
}
