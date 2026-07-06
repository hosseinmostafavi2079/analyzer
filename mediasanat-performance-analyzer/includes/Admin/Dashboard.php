<?php
namespace Mediasanat\PA\Admin;

use Mediasanat\PA\Modules\DatabaseProfiler;
use Mediasanat\PA\Modules\ServerScanner;
use Mediasanat\PA\Modules\SolutionsEngine;
use Mediasanat\PA\Core\SafetyEngine;

if ( ! defined( 'ABSPATH' ) ) exit;

class Dashboard {

    public function init() {
        add_action( 'admin_menu', [ $this, 'add_menu_page' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        // ثبت هوک AJAX برای پاکسازی ترانزینت‌ها
        add_action( 'wp_ajax_ms_clear_transients', [ $this, 'ajax_clear_transients' ] );
    }

    public function add_menu_page() {
        add_menu_page( 'تحلیل‌گر مدیاصنعت', 'عملکرد مدیاصنعت', 'manage_options', 'mediasanat-performance', [ $this, 'render_view' ], 'dashicons-chart-area', 80 );
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
        $db = new DatabaseProfiler();
        $server = new ServerScanner();
        $engine = new SolutionsEngine();

        $autoload_size = $db->get_autoload_size();
        $transients = $db->count_expired_transients();
        $server_health = $server->get_server_health();
        $reports = $engine->generate_report( $autoload_size, $transients, $server_health );

        require_once MEDIASANAT_PA_PATH . 'includes/Admin/Views/dashboard-view.php';
    }

    public function ajax_clear_transients() {
        SafetyEngine::verify_ajax_request( 'ms_pa_safe_action' );
        
        // این بخش صرفا جهت نمایش معماری است و در صورت تایید ادمین اجرا می‌شود
        global $wpdb;
        $now = time();
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s AND option_value < %d", '_transient_timeout_%', $now ) );
        
        wp_send_json_success( 'پاکسازی با موفقیت انجام شد.' );
    }
}