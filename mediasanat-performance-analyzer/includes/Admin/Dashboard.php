<?php
namespace Mediasanat\PA\Admin;

use Mediasanat\PA\Modules\DatabaseProfiler;
use Mediasanat\PA\Modules\ServerScanner;
use Mediasanat\PA\Modules\SolutionsEngine;
use Mediasanat\PA\Modules\AssetAnalyzer;
use Mediasanat\PA\Modules\NetworkMonitor;
use Mediasanat\PA\Core\SafetyEngine;
use Mediasanat\PA\Modules\BackupManager;

if ( ! defined( 'ABSPATH' ) ) exit;

class Dashboard {

    public function init() {
        $network = new NetworkMonitor();
        $network->init();

        add_action( 'admin_menu', [ $this, 'add_menu_page' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );

        add_action( 'wp_ajax_ms_clear_transients', [ $this, 'ajax_clear_transients' ] );
        add_action( 'wp_ajax_ms_toggle_domain_block', [ $this, 'ajax_toggle_domain_block' ] );
        add_action( 'wp_ajax_ms_clear_network_logs', [ $this, 'ajax_clear_network_logs' ] );

        // هوک‌های بکاپ
        add_action( 'wp_ajax_ms_create_backup', [ $this, 'ajax_create_backup' ] );
        add_action( 'wp_ajax_ms_delete_backup', [ $this, 'ajax_delete_backup' ] );
        add_action( 'admin_post_ms_download_backup', [ $this, 'handle_download_backup' ] );
        
        // تست مجدد سرعت
        add_action( 'wp_ajax_ms_retest_speed', [ $this, 'ajax_retest_speed' ] );
    }

    public function add_menu_page() {
        add_menu_page( 'تحلیل‌گر مدیاصنعت', 'عملکرد سایت', 'manage_options', 'mediasanat-performance', [ $this, 'render_view' ], 'dashicons-chart-area', 80 );
    }

    public function enqueue_assets( $hook ) {
        if ( $hook !== 'toplevel_page_mediasanat-performance' ) return;

        wp_enqueue_style( 'ms-pa-admin-css', MEDIASANAT_PA_URL . 'assets/css/admin-rtl.css', [], MEDIASANAT_PA_VERSION );
        wp_enqueue_script( 'ms-pa-admin-js', MEDIASANAT_PA_URL . 'assets/js/admin-app.js', ['jquery'], MEDIASANAT_PA_VERSION, true );

        wp_localize_script( 'ms-pa-admin-js', 'msPaConfig', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'ms_pa_safe_action' )
        ]);
    }

    public function render_view() {
        $db      = new DatabaseProfiler();
        $server  = new ServerScanner();
        $engine  = new SolutionsEngine();
        $asset   = new AssetAnalyzer();
        $network = new NetworkMonitor();
        $backup  = new BackupManager();

        $autoload_size   = $db->get_autoload_size();
        $transients      = $db->count_expired_transients();
        $server_health   = $server->get_server_health();
        $heavy_images    = $asset->get_heavy_images();
        $network_logs    = $network->get_logs();
        $blocked_domains = $network->get_blocked_domains();
        $ms_backups      = $backup->get_backups();

        // کش کردن نتیجه تحلیل سرعت برای ۱۰ دقیقه (جلوگیری از کندی هر بار باز کردن صفحه)
        $homepage_stats = get_transient( 'ms_homepage_stats' );
        if ( false === $homepage_stats ) {
            $homepage_stats = $asset->analyze_homepage();
            set_transient( 'ms_homepage_stats', $homepage_stats, 10 * MINUTE_IN_SECONDS );
        }

        $reports = $engine->generate_report( $autoload_size, $transients, $server_health, $homepage_stats );

        require_once MEDIASANAT_PA_PATH . 'includes/Admin/Views/dashboard-view.php';
    }

    public function ajax_clear_transients() {
        SafetyEngine::verify_ajax_request( 'ms_pa_safe_action' );
        global $wpdb;
        $now = time();
        // حذف هم timeout و هم مقدار transient منقضی‌شده
        $expired = $wpdb->get_col( $wpdb->prepare(
            "SELECT REPLACE(option_name, '_transient_timeout_', '') FROM {$wpdb->options}
             WHERE option_name LIKE %s AND option_value < %d",
            '_transient_timeout_%', $now
        ) );
        $count = 0;
        foreach ( $expired as $name ) {
            delete_transient( $name );
            $count++;
        }
        wp_send_json_success( "پاکسازی انجام شد. تعداد {$count} مورد حذف شد." );
    }

    public function ajax_toggle_domain_block() {
        SafetyEngine::verify_ajax_request( 'ms_pa_safe_action' );

        $domain      = isset( $_POST['domain'] ) ? sanitize_text_field( wp_unslash( $_POST['domain'] ) ) : '';
        $action_type = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : '';

        // اعتبارسنجی فرمت دامنه
        if ( empty( $domain ) || ! preg_match( '/^[a-zA-Z0-9.\-]+$/', $domain ) ) {
            wp_send_json_error( 'دامنه نامعتبر است.' );
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

    public function ajax_create_backup() {
        SafetyEngine::verify_ajax_request( 'ms_pa_safe_action' );
        $result = (new BackupManager())->create_db_backup();
        if ( $result['success'] ) {
            wp_send_json_success( $result['message'] );
        } else {
            wp_send_json_error( $result['message'] );
        }
    }

    public function ajax_delete_backup() {
        SafetyEngine::verify_ajax_request( 'ms_pa_safe_action' );
        $filename = isset($_POST['filename']) ? sanitize_file_name( wp_unslash( $_POST['filename'] ) ) : '';
        if ( (new BackupManager())->delete_backup($filename) ) {
            wp_send_json_success('بکاپ حذف شد.');
        } else {
            wp_send_json_error('خطا در حذف فایل یا عدم دسترسی مجاز.');
        }
    }

    /**
     * دانلود بکاپ - با بررسی نانس و دسترسی (رفع آسیب‌پذیری امنیتی)
     */
    public function handle_download_backup() {
        if ( ! current_user_can('manage_options') ) {
            wp_die('دسترسی غیرمجاز.');
        }
        // بررسی نانس (بسیار مهم برای امنیت)
        if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'ms_download_backup' ) ) {
            wp_die('خطای امنیتی: لینک دانلود نامعتبر یا منقضی شده است.');
        }
        $filename = isset($_GET['file']) ? sanitize_file_name( wp_unslash( $_GET['file'] ) ) : '';
        if ( ! empty($filename) ) {
            (new BackupManager())->download_backup($filename);
        }
        wp_die('فایل مشخص نشده است.');
    }

    // متد تست مجدد سرعت سایت
    public function ajax_retest_speed() {
        SafetyEngine::verify_ajax_request( 'ms_pa_safe_action' );
        delete_transient( 'ms_homepage_stats' ); // پاک کردن کش قدیمی
        $asset = new \Mediasanat\PA\Modules\AssetAnalyzer();
        $stats = $asset->analyze_homepage();
        set_transient( 'ms_homepage_stats', $stats, 10 * MINUTE_IN_SECONDS );
        wp_send_json_success( 'تست سرعت مجدداً انجام شد.' );
    }
}