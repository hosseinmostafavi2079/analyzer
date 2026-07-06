<?php
namespace Mediasanat\PA\Modules;

if ( ! defined( 'ABSPATH' ) ) exit;

class NetworkMonitor {

    private $option_name = 'ms_blocked_domains';
    private $log_name    = 'ms_ext_req_log';
    private $timers      = []; // نگه‌داری زمان شروع هر درخواست

    public function init() {
        // زمان‌بندی: ثبت لحظه شروع درخواست
        add_action( 'http_api_debug', [ $this, 'log_external_requests' ], 10, 5 );
        // مسدودسازی درخواست‌ها پیش از اجرای cURL
        add_filter( 'pre_http_request', [ $this, 'block_external_requests' ], 10, 3 );
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
            'url'      => $url,
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

        if ( is_array( $blocked ) && in_array( $host, $blocked, true ) ) {
            return new \WP_Error(
                'mediasanat_blocked',
                "ارتباط با {$host} توسط مدیاصنعت جهت جلوگیری از افت سرعت مسدود شده است."
            );
        }
        return $pre;
    }

    /**
     * ثبت زمان شروع درخواست (توسط فیلتر http_request_args)
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
}