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
        
        // اضافه شدن هوک‌های بکاپ
        add_action( 'wp_ajax_ms_create_backup', [ $this, 'ajax_create_backup' ] );
        add_action( 'wp_ajax_ms_delete_backup', [ $this, 'ajax_delete_backup' ] );
        add_action( 'admin_post_ms_download_backup', [ $this, 'handle_download_backup' ] );
    }

    public function add_menu_page() {
        add_menu_page( 'تحلیل‌گر مدیاصنعت', 'عملکرد سایت', 'manage_options', 'mediasanat-performance', [ $this, 'render_view' ], 'dashicons-chart-area', 80 );
    }

    public function enqueue_assets( $hook ) {
        if ( $hook !== 'toplevel_page_mediasanat-performance' ) return;

        wp_enqueue_style( 'ms-pa-admin-css', MEDIASANAT_PA_URL . 'assets/css/admin-rtl.css', [], time() );
        wp_enqueue_script( 'ms-pa-admin-js', MEDIASANAT_PA_URL . 'assets/js/admin-app.js', ['jquery'], time(), true );
        
        wp_localize_script( 'ms-pa-admin-js', 'msPaConfig', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'ms_pa_safe_action' )
        ]);
    }

    public function render_view() {
        // ایجاد نمونه از کلاس‌ها
        $db      = new DatabaseProfiler();
        $server  = new ServerScanner();
        $engine  = new SolutionsEngine();
        $asset   = new AssetAnalyzer();
        $network = new NetworkMonitor();
        $backup  = new BackupManager();

        // استخراج داده‌ها
        $autoload_size  = $db->get_autoload_size();
        $transients     = $db->count_expired_transients();
        $server_health  = $server->get_server_health();
        $heavy_images   = $asset->get_heavy_images();
        $network_logs   = $network->get_logs();
        $blocked_domains= $network->get_blocked_domains();
        
        // دریافت لیست بکاپ‌ها
        $ms_backups = $backup->get_backups();
        
        // استخراج داده‌های صفحه اصلی
        $homepage_stats = $asset->analyze_homepage();
        
        // تولید گزارش هوشمند
        $reports = $engine->generate_report( $autoload_size, $transients, $server_health, $homepage_stats );

        // لود کردن فایل View
        require_once MEDIASANAT_PA_PATH . 'includes/Admin/Views/dashboard-view.php';
    }

    public function ajax_clear_transients() {
        SafetyEngine::verify_ajax_request( 'ms_pa_safe_action' );
        global $wpdb;
        $now = time();
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s AND option_value < %d", '_transient_timeout_%', $now ) );
        wp_send_json_success( 'پاکسازی با موفقیت انجام شد.' );
    }

    public function ajax_toggle_domain_block() {
        SafetyEngine::verify_ajax_request( 'ms_pa_safe_action' );
        
        $domain = isset( $_POST['domain'] ) ? sanitize_text_field( $_POST['domain'] ) : '';
        $action_type = isset( $_POST['type'] ) ? sanitize_text_field( $_POST['type'] ) : '';
        
        if ( empty( $domain ) ) wp_send_json_error( 'دامنه نامعتبر است.' );

        $blocked = get_option( 'ms_blocked_domains', [] );
        
        if ( $action_type === 'block' ) {
            if ( ! in_array( $domain, $blocked ) ) {
                $blocked[] = $domain;
            }
            $msg = 'دامنه با موفقیت مسدود شد و دیگر سرور به آن متصل نخواهد شد.';
        } else {
            $blocked = array_diff( $blocked, [ $domain ] );
            $msg = 'دامنه از لیست سیاه خارج شد.';
        }
        
        update_option( 'ms_blocked_domains', array_values( $blocked ) );
        wp_send_json_success( $msg );
    }

    // --- توابع مربوط به پردازش بکاپ ---
    public function ajax_create_backup() {
        SafetyEngine::verify_ajax_request( 'ms_pa_safe_action' );
        $result = (new BackupManager())->create_db_backup();
        if( $result['success'] ) {
            wp_send_json_success( $result['message'] );
        } else {
            wp_send_json_error( $result['message'] );
        }
    }

    public function ajax_delete_backup() {
        SafetyEngine::verify_ajax_request( 'ms_pa_safe_action' );
        $filename = isset($_POST['filename']) ? sanitize_text_field($_POST['filename']) : '';
        if( (new BackupManager())->delete_backup($filename) ) {
            wp_send_json_success('بکاپ با موفقیت حذف شد.');
        } else {
            wp_send_json_error('خطا در حذف فایل یا عدم دسترسی مجاز.');
        }
    }

    public function handle_download_backup() {
        if( ! current_user_can('manage_options') ) wp_die('دسترسی غیرمجاز.');
        $filename = isset($_GET['file']) ? sanitize_text_field($_GET['file']) : '';
        if( ! empty($filename) ) {
            (new BackupManager())->download_backup($filename);
        }
    }
}