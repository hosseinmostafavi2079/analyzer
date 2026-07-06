<?php
namespace Mediasanat\PA\Modules;

if ( ! defined( 'ABSPATH' ) ) exit;

class NetworkMonitor {
    
    private $option_name = 'ms_blocked_domains';
    private $log_name = 'ms_ext_req_log';

    public function init() {
        // ثبت لاگ درخواست‌های خروجی سرور
        add_action( 'http_api_debug', [ $this, 'log_external_requests' ], 10, 5 );
        
        // فیلتر کردن و مسدودسازی درخواست‌ها در سطح هسته، پیش از اجرای cURL
        add_filter( 'pre_http_request', [ $this, 'block_external_requests' ], 10, 3 );
    }

    public function log_external_requests( $response, $context, $class, $args, $url ) {
        $host = wp_parse_url( $url, PHP_URL_HOST );
        $home_host = wp_parse_url( home_url(), PHP_URL_HOST );
        
        // نادیده گرفتن درخواست‌های داخلی سرور به خودش
        if ( $host === $home_host || empty( $host ) ) return;

        $logs = get_transient( $this->log_name ) ?: [];
        
        // دریافت کد خطای شبکه (مثلاً cURL error 28 برای تایم‌اوت) یا وضعیت موفقیت
        if ( is_wp_error( $response ) ) {
            $status = 'خطا: ' . $response->get_error_message();
        } else {
            $status = 'کد پاسخ: ' . wp_remote_retrieve_response_code( $response );
        }
        
        $logs[$host] = [
            'url'    => $url,
            'status' => $status,
            'time'   => current_time( 'mysql' )
        ];
        
        // نگهداری ۳۰ ریکوئست آخر برای جلوگیری از پر شدن حافظه (Ring Buffer Pattern)
        if ( count( $logs ) > 30 ) {
            array_shift( $logs );
        }
        
        set_transient( $this->log_name, $logs, HOUR_IN_SECONDS * 24 );
    }

    public function block_external_requests( $pre, $parsed_args, $url ) {
        $blocked = get_option( $this->option_name, [] );
        $host = wp_parse_url( $url, PHP_URL_HOST );
        
        if ( in_array( $host, $blocked ) ) {
            // در صورت مسدود بودن، یک ارور ساختگی برمی‌گرداند تا درخواست بدون اتلاف وقت (Zero-Delay) لغو شود
            return new \WP_Error( 'mediasanat_blocked', "ارتباط با $host جهت جلوگیری از افت سرعت توسط مدیاصنعت مسدود شده است." );
        }
        
        return $pre;
    }

    public function get_logs() {
        return get_transient( $this->log_name ) ?: [];
    }

    public function get_blocked_domains() {
        return get_option( $this->option_name, [] );
    }
}