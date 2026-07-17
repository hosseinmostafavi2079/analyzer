<?php
namespace Mediasanat\PA\Admin;

use Mediasanat\PA\Modules\ServerScanner;
use Mediasanat\PA\Modules\SolutionsEngine;
use Mediasanat\PA\Modules\AssetAnalyzer;
use Mediasanat\PA\Modules\NetworkMonitor;
use Mediasanat\PA\Core\SafetyEngine;

if ( ! defined( 'ABSPATH' ) ) exit;

class Dashboard {

    public function init() {
        $network = new NetworkMonitor();
        $network->init();

        add_action( 'admin_menu', [ $this, 'add_menu_page' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );

        add_action( 'wp_ajax_ms_toggle_domain_block', [ $this, 'ajax_toggle_domain_block' ] );
        add_action( 'wp_ajax_ms_clear_network_logs', [ $this, 'ajax_clear_network_logs' ] );
        add_action( 'wp_ajax_ms_toggle_resilience', [ $this, 'ajax_toggle_resilience' ] );

        // تست مجدد سرعت
        add_action( 'wp_ajax_ms_retest_speed', [ $this, 'ajax_retest_speed' ] );
        add_action( 'wp_ajax_ms_analyze_browser_html', [ $this, 'ajax_analyze_browser_html' ] );

    }

    public function add_menu_page() {
        add_menu_page( 'پایشگر تاب‌آوری موستک', 'تاب‌آوری سایت', 'manage_options', 'mediasanat-performance', [ $this, 'render_view' ], 'dashicons-shield-alt', 80 );
    }

    /**
     * بارگذاری استایل و اسکریپت — به‌جای اتکا به رشته دقیق $hook (که ممکن است
     * به‌خاطر تداخل با پلاگین‌های دیگر یا نسخه‌های مختلف وردپرس تغییر کند)،
     * مستقیماً پارامتر page را از URL چک می‌کنیم. این روش قابل‌اطمینان‌تر است.
     */
    public function enqueue_assets( $hook ) {
        $page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
        $is_our_page = 'mediasanat-performance' === $page;

        if ( ! $is_our_page ) {
            return;
        }

        wp_enqueue_style( 'ms-pa-admin-css', MEDIASANAT_PA_URL . 'assets/css/admin-rtl.css', [], MEDIASANAT_PA_VERSION );
        wp_enqueue_script( 'ms-pa-admin-js', MEDIASANAT_PA_URL . 'assets/js/admin-app.js', [], MEDIASANAT_PA_VERSION, true );

        wp_localize_script( 'ms-pa-admin-js', 'msPaConfig', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'ms_pa_safe_action' ),
            'home_url' => home_url( '/' ),
            'scan_nonce' => wp_create_nonce( 'ms_pa_frontend_scan' ),
        ]);
    }

    public function render_view() {
        $server   = new ServerScanner();
        $engine   = new SolutionsEngine();
        $asset    = new AssetAnalyzer();
        $network  = new NetworkMonitor();

        $server_health   = $server->get_server_health();
        $heavy_images    = get_transient( 'ms_pa_heavy_images' );
        if ( false === $heavy_images ) {
            $heavy_images = $asset->get_heavy_images();
            set_transient( 'ms_pa_heavy_images', $heavy_images, 10 * MINUTE_IN_SECONDS );
        }
        $network_logs    = $network->get_logs();
        $blocked_domains = $network->get_blocked_domains();
        $resilience_mode = $network->is_resilience_mode();

        // کش کردن نتیجه تحلیل سرعت برای ۱۰ دقیقه (جلوگیری از کندی هر بار باز کردن صفحه)
        $homepage_stats = get_transient( 'ms_homepage_stats' );
        if ( false === $homepage_stats ) {
            $homepage_stats = $asset->analyze_homepage();
            set_transient( 'ms_homepage_stats', $homepage_stats, 10 * MINUTE_IN_SECONDS );
        }

        $external_assets = $this->merge_external_inventory(
            isset( $homepage_stats['external_assets'] ) ? $homepage_stats['external_assets'] : [],
            $network_logs
        );
        $homepage_stats['external_domain_count'] = count( $external_assets );
        $homepage_stats['risky_external_count'] = count( array_filter( $external_assets, function( $item ) {
            return ! empty( array_diff( (array) ( $item['types'] ?? [] ), [ 'اشاره URL در کد' ] ) );
        } ) );

        $reports = $engine->generate_report( $server_health, $homepage_stats );

        require_once MEDIASANAT_PA_PATH . 'includes/Admin/Views/dashboard-view.php';
    }

    public function ajax_toggle_domain_block() {
        SafetyEngine::verify_ajax_request( 'ms_pa_safe_action' );

        $domain      = isset( $_POST['domain'] ) ? sanitize_text_field( wp_unslash( $_POST['domain'] ) ) : '';
        $action_type = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : '';

        // اعتبارسنجی فرمت دامنه
        if ( empty( $domain ) || ! preg_match( '/^[a-zA-Z0-9.\-]+$/', $domain ) ) {
            wp_send_json_error( 'دامنه نامعتبر است.' );
        }
        if ( ! in_array( $action_type, [ 'block', 'unblock' ], true ) ) {
            wp_send_json_error( 'نوع عملیات نامعتبر است.' );
        }

        $blocked = get_option( 'ms_blocked_domains', [] );
        if ( ! is_array( $blocked ) ) $blocked = [];

        if ( $action_type === 'block' ) {
            if ( ! in_array( $domain, $blocked, true ) ) {
                $blocked[] = $domain;
            }
            $msg = 'دامنه با موفقیت مسدود شد.';
        } else {
            $blocked = array_diff( $blocked, [ $domain ] );
            $msg = 'دامنه آزاد شد.';
        }

        update_option( 'ms_blocked_domains', array_values( $blocked ) );
        wp_send_json_success( $msg );
    }

    public function ajax_clear_network_logs() {
        SafetyEngine::verify_ajax_request( 'ms_pa_safe_action' );
        (new NetworkMonitor())->clear_logs();
        wp_send_json_success( 'لاگ ارتباطات پاک شد.' );
    }

    public function ajax_toggle_resilience() {
        SafetyEngine::verify_ajax_request( 'ms_pa_safe_action' );
        $enabled = isset( $_POST['enabled'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['enabled'] ) );
        update_option( 'ms_resilience_mode', $enabled, false );
        wp_send_json_success( $enabled
            ? 'حالت تاب‌آوری فعال شد. تماس‌ها و فایل‌های خارجیِ غیرمجاز متوقف می‌شوند.'
            : 'حالت تاب‌آوری غیرفعال شد و رفتار عادی سایت بازگشت.'
        );
    }

    // متد تست مجدد سرعت سایت
    public function ajax_retest_speed() {
        SafetyEngine::verify_ajax_request( 'ms_pa_safe_action' );
        delete_transient( 'ms_homepage_stats' ); // پاک کردن کش قدیمی
        delete_transient( 'ms_pa_heavy_images' );
        $asset = new \Mediasanat\PA\Modules\AssetAnalyzer();
        $stats = $asset->analyze_homepage();
        set_transient( 'ms_homepage_stats', $stats, 10 * MINUTE_IN_SECONDS );
        wp_send_json_success( $stats );
    }

    public function ajax_analyze_browser_html() {
        SafetyEngine::verify_ajax_request( 'ms_pa_safe_action' );
        delete_transient( 'ms_pa_heavy_images' );
        $html = isset( $_POST['html'] ) ? wp_unslash( $_POST['html'] ) : '';
        if ( ! is_string( $html ) || strlen( $html ) < 100 || strlen( $html ) > 5 * 1024 * 1024 ) {
            wp_send_json_error( 'حجم HTML دریافت‌شده برای تحلیل معتبر نیست.' );
        }
        $duration = isset( $_POST['duration'] ) ? (float) sanitize_text_field( wp_unslash( $_POST['duration'] ) ) : 0.01;
        $duration = max( 0.01, min( 60, $duration ) );
        $stats = ( new AssetAnalyzer() )->analyze_html( $html, $duration, 200, 'browser' );
        set_transient( 'ms_homepage_stats', $stats, 10 * MINUTE_IN_SECONDS );
        wp_send_json_success( $stats );
    }

    private function merge_external_inventory( $page_assets, $network_logs ) {
        $inventory = [];
        foreach ( (array) $page_assets as $item ) {
            if ( empty( $item['domain'] ) ) continue;
            $domain = strtolower( $item['domain'] );
            $item['types'] = array_values( array_unique( (array) ( $item['types'] ?? [] ) ) );
            $inventory[ $domain ] = $item;
        }
        foreach ( (array) $network_logs as $domain => $log ) {
            $domain = strtolower( $domain );
            if ( ! isset( $inventory[ $domain ] ) ) {
                $inventory[ $domain ] = [ 'domain' => $domain, 'count' => 0, 'types' => [], 'samples' => [] ];
            }
            $inventory[ $domain ]['types'][] = 'تماس سروری';
            $inventory[ $domain ]['types'] = array_values( array_unique( $inventory[ $domain ]['types'] ) );
            $inventory[ $domain ]['count'] += max( 1, (int) ( $log['count'] ?? 1 ) );
            if ( ! empty( $log['url'] ) && count( $inventory[ $domain ]['samples'] ) < 2 ) $inventory[ $domain ]['samples'][] = $log['url'];
        }
        uasort( $inventory, function( $a, $b ) { return $b['count'] <=> $a['count']; } );
        return array_values( $inventory );
    }

}
