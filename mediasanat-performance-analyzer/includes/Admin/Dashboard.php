<?php
namespace Mediasanat\PA\Admin;

use Mediasanat\PA\Modules\ServerScanner;
use Mediasanat\PA\Modules\SolutionsEngine;
use Mediasanat\PA\Modules\AssetAnalyzer;
use Mediasanat\PA\Modules\NetworkMonitor;
use Mediasanat\PA\Modules\PolicyManager;
use Mediasanat\PA\Core\SafetyEngine;

if ( ! defined( 'ABSPATH' ) ) exit;

class Dashboard {
    public function init() {
        $policy = new PolicyManager();
        $policy->init();
        ( new NetworkMonitor( $policy ) )->init();

        add_action( 'admin_menu', [ $this, 'add_menu_page' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'wp_ajax_ms_toggle_domain_block', [ $this, 'ajax_toggle_domain_block' ] );
        add_action( 'wp_ajax_ms_save_domain_rule', [ $this, 'ajax_save_domain_rule' ] );
        add_action( 'wp_ajax_ms_delete_domain_rule', [ $this, 'ajax_delete_domain_rule' ] );
        add_action( 'wp_ajax_ms_set_operation_mode', [ $this, 'ajax_set_operation_mode' ] );
        add_action( 'wp_ajax_ms_clear_network_logs', [ $this, 'ajax_clear_network_logs' ] );
        add_action( 'wp_ajax_ms_retest_speed', [ $this, 'ajax_retest_speed' ] );
        add_action( 'wp_ajax_ms_analyze_browser_html', [ $this, 'ajax_analyze_browser_html' ] );
        add_action( 'wp_ajax_ms_scan_heavy_images', [ $this, 'ajax_scan_heavy_images' ] );
    }

    public function add_menu_page() {
        add_menu_page( 'پایشگر تاب‌آوری موستک', 'تاب‌آوری سایت', 'manage_options', 'mediasanat-performance', [ $this, 'render_view' ], 'dashicons-shield-alt', 80 );
    }

    public function enqueue_assets() {
        $page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
        if ( 'mediasanat-performance' !== $page ) return;

        wp_enqueue_style( 'ms-pa-admin-css', MEDIASANAT_PA_URL . 'assets/css/admin-rtl.css', [], MEDIASANAT_PA_VERSION );
        wp_enqueue_script( 'ms-pa-admin-js', MEDIASANAT_PA_URL . 'assets/js/admin-app.js', [], MEDIASANAT_PA_VERSION, true );
        $mode_state = ( new PolicyManager() )->get_mode_state();
        wp_localize_script( 'ms-pa-admin-js', 'msPaConfig', [
            'ajax_url'   => admin_url( 'admin-ajax.php' ),
            'nonce'      => wp_create_nonce( 'ms_pa_safe_action' ),
            'home_url'   => home_url( '/' ),
            'scan_nonce' => wp_create_nonce( 'ms_pa_frontend_scan' ),
            'current_mode' => $mode_state['mode'],
        ] );
    }

    public function render_view() {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( esc_html__( 'شما اجازه دسترسی به این بخش را ندارید.', 'mostech-resilience-monitor' ) );
        $server = new ServerScanner();
        $engine = new SolutionsEngine();
        $policy = new PolicyManager();
        $network = new NetworkMonitor( $policy );

        $server_health = $server->get_server_health();
        $heavy_images = get_transient( 'ms_pa_heavy_images' );
        $heavy_scan_ready = is_array( $heavy_images );
        if ( ! $heavy_scan_ready ) $heavy_images = [];

        $network_logs = $network->get_logs();
        $policy_state = $policy->get_mode_state();
        $domain_rules = $policy->get_rules();
        $categories = $policy->get_categories();

        $homepage_stats = get_transient( 'ms_homepage_stats' );
        if ( false === $homepage_stats ) {
            $homepage_stats = [
                'status'  => 'not_scanned',
                'message' => 'هنوز اسکن صفحه اصلی اجرا نشده است.',
                'reason'  => 'برای شروع، مدیر باید روی دکمه «اسکن دوباره» کلیک کند.',
            ];
        }

        $external_assets = $this->merge_external_inventory( $homepage_stats['external_assets'] ?? [], $network_logs );
        $homepage_stats['external_domain_count'] = count( $external_assets );
        $homepage_stats['risky_external_count'] = count( array_filter( $external_assets, function( $item ) {
            return ! empty( array_diff( (array) ( $item['types'] ?? [] ), [ 'اشاره URL در کد' ] ) );
        } ) );

        foreach ( $external_assets as &$item ) {
            $rule = $policy->get_rule( $item['domain'] );
            $category = $rule['category'] ?? $policy->auto_category( $item['domain'], $item['types'] ?? [] );
            $category_data = $policy->get_category_data( $category );
            $item['category'] = $category;
            $item['category_label'] = $category_data['label'];
            $item['impact'] = $category_data['impact'];
            $item['rule_list'] = $rule['list'] ?? '';
        }
        unset( $item );

        $reports = $engine->generate_report( $server_health, $homepage_stats );
        require MEDIASANAT_PA_PATH . 'includes/Admin/Views/dashboard-view.php';
    }

    public function ajax_toggle_domain_block() {
        SafetyEngine::verify_ajax_request( 'ms_pa_safe_action' );
        $domain = isset( $_POST['domain'] ) ? sanitize_text_field( wp_unslash( $_POST['domain'] ) ) : '';
        $action = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : '';
        if ( ! in_array( $action, [ 'block', 'unblock' ], true ) ) wp_send_json_error( 'نوع عملیات نامعتبر است.' );
        $policy = new PolicyManager();
        $result = 'block' === $action
            ? $policy->set_rule( $domain, 'block', $policy->auto_category( $domain ) )
            : $policy->delete_rule( $domain );
        if ( is_wp_error( $result ) ) wp_send_json_error( $result->get_error_message() );
        wp_send_json_success( 'block' === $action ? 'دامنه به Blocklist اضافه شد؛ در حالت پایش یا شبیه‌سازی قطع نمی‌شود.' : 'قانون دامنه حذف شد.' );
    }

    public function ajax_save_domain_rule() {
        SafetyEngine::verify_ajax_request( 'ms_pa_safe_action' );
        $domain = isset( $_POST['domain'] ) ? sanitize_text_field( wp_unslash( $_POST['domain'] ) ) : '';
        $list = isset( $_POST['list'] ) ? sanitize_key( wp_unslash( $_POST['list'] ) ) : '';
        $category = isset( $_POST['category'] ) ? sanitize_key( wp_unslash( $_POST['category'] ) ) : '';
        $result = ( new PolicyManager() )->set_rule( $domain, $list, $category );
        if ( is_wp_error( $result ) ) wp_send_json_error( $result->get_error_message() );
        wp_send_json_success( 'قانون دامنه ذخیره شد.' );
    }

    public function ajax_delete_domain_rule() {
        SafetyEngine::verify_ajax_request( 'ms_pa_safe_action' );
        $domain = isset( $_POST['domain'] ) ? sanitize_text_field( wp_unslash( $_POST['domain'] ) ) : '';
        $result = ( new PolicyManager() )->delete_rule( $domain );
        if ( is_wp_error( $result ) ) wp_send_json_error( $result->get_error_message() );
        wp_send_json_success( 'قانون دامنه حذف شد.' );
    }

    public function ajax_set_operation_mode() {
        SafetyEngine::verify_ajax_request( 'ms_pa_safe_action' );
        $mode = isset( $_POST['mode'] ) ? sanitize_key( wp_unslash( $_POST['mode'] ) ) : '';
        $duration = isset( $_POST['duration'] ) ? absint( wp_unslash( $_POST['duration'] ) ) : 0;
        $confirmed = isset( $_POST['confirmed'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['confirmed'] ) );
        $result = ( new PolicyManager() )->set_mode( $mode, $duration, $confirmed );
        if ( is_wp_error( $result ) ) wp_send_json_error( $result->get_error_message() );
        wp_send_json_success( $result );
    }

    public function ajax_clear_network_logs() {
        SafetyEngine::verify_ajax_request( 'ms_pa_safe_action' );
        ( new NetworkMonitor( new PolicyManager() ) )->clear_logs();
        wp_send_json_success( 'لاگ‌های تجمیعی شبکه پاک شدند.' );
    }

    public function ajax_retest_speed() {
        SafetyEngine::verify_ajax_request( 'ms_pa_safe_action' );
        delete_transient( 'ms_homepage_stats' );
        $stats = ( new AssetAnalyzer() )->analyze_homepage();
        set_transient( 'ms_homepage_stats', $stats, 30 * MINUTE_IN_SECONDS );
        wp_send_json_success( $stats );
    }

    public function ajax_analyze_browser_html() {
        SafetyEngine::verify_ajax_request( 'ms_pa_safe_action' );
        $html = isset( $_POST['html'] ) ? wp_unslash( $_POST['html'] ) : '';
        if ( ! is_string( $html ) || strlen( $html ) < 100 || strlen( $html ) > 5 * 1024 * 1024 ) wp_send_json_error( 'حجم HTML دریافت‌شده برای تحلیل معتبر نیست.' );
        $duration = isset( $_POST['duration'] ) ? (float) sanitize_text_field( wp_unslash( $_POST['duration'] ) ) : 0.01;
        $duration = max( 0.01, min( 60, $duration ) );
        $stats = ( new AssetAnalyzer() )->analyze_html( $html, $duration, 200, 'browser' );
        set_transient( 'ms_homepage_stats', $stats, 30 * MINUTE_IN_SECONDS );
        wp_send_json_success( $stats );
    }

    public function ajax_scan_heavy_images() {
        SafetyEngine::verify_ajax_request( 'ms_pa_safe_action' );
        $images = ( new AssetAnalyzer() )->get_heavy_images();
        set_transient( 'ms_pa_heavy_images', $images, HOUR_IN_SECONDS );
        wp_send_json_success( [ 'count' => count( $images ) ] );
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
            if ( ! isset( $inventory[ $domain ] ) ) $inventory[ $domain ] = [ 'domain' => $domain, 'count' => 0, 'types' => [], 'samples' => [] ];
            $inventory[ $domain ]['types'][] = 'frontend' === ( $log['channel'] ?? '' ) ? 'فایل Frontend' : 'تماس سروری';
            $inventory[ $domain ]['types'] = array_values( array_unique( $inventory[ $domain ]['types'] ) );
            $inventory[ $domain ]['count'] += max( 1, (int) ( $log['count'] ?? 1 ) );
            if ( ! empty( $log['origin'] ) && count( $inventory[ $domain ]['samples'] ) < 2 ) $inventory[ $domain ]['samples'][] = $log['origin'];
        }
        uasort( $inventory, function( $a, $b ) { return $b['count'] <=> $a['count']; } );
        return array_values( $inventory );
    }
}
