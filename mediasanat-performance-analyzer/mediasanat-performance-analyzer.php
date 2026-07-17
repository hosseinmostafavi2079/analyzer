<?php
/**
 * Plugin Name: DepGuard – WordPress Dependency Monitor
 * Plugin URI:  https://mostech.ir/
 * Description: پایش و مدیریت وابستگی‌های خارجی وردپرس
 * Version:     1.6.0
 * Author:      hoseinmos
 * Author URI:  https://mostech.ir/
 * Text Domain: depguard
 * Requires at least: 5.5
 * Requires PHP: 7.4
 * License: GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) exit; // جلوگیری از دسترسی مستقیم

define( 'MEDIASANAT_PA_VERSION', '1.6.0' );
define( 'MEDIASANAT_PA_PATH', plugin_dir_path( __FILE__ ) );
define( 'MEDIASANAT_PA_URL', plugin_dir_url( __FILE__ ) );
define( 'MEDIASANAT_PA_MIN_PHP', '7.4' );
define( 'MEDIASANAT_PA_MIN_WP', '5.5' );
// Aliasهای جدید؛ ثابت‌های قدیمی برای سازگاری نسخه‌های قبلی حفظ شده‌اند.
define( 'DEPGUARD_VERSION', MEDIASANAT_PA_VERSION );
define( 'DEPGUARD_PATH', MEDIASANAT_PA_PATH );
define( 'DEPGUARD_URL', MEDIASANAT_PA_URL );

// 1. بررسی سازگاری ایمن
function mediasanat_pa_check_compatibility() {
    if ( version_compare( PHP_VERSION, MEDIASANAT_PA_MIN_PHP, '<' ) ) {
        add_action( 'admin_notices', 'mediasanat_pa_php_notice' );
        return false;
    }
    if ( version_compare( get_bloginfo( 'version' ), MEDIASANAT_PA_MIN_WP, '<' ) ) {
        add_action( 'admin_notices', 'mediasanat_pa_wp_notice' );
        return false;
    }
    return true;
}

function mediasanat_pa_php_notice() {
    echo '<div class="notice notice-error is-dismissible"><p>افزونه <strong>DepGuard</strong> نیازمند PHP نسخه ' . MEDIASANAT_PA_MIN_PHP . ' یا بالاتر است.</p></div>';
}
function mediasanat_pa_wp_notice() {
    echo '<div class="notice notice-error is-dismissible"><p>افزونه <strong>DepGuard</strong> نیازمند وردپرس نسخه ' . MEDIASANAT_PA_MIN_WP . ' یا بالاتر است.</p></div>';
}

// 2. راه‌اندازی سیستم در صورت موفقیت
if ( mediasanat_pa_check_compatibility() ) {
    require_once MEDIASANAT_PA_PATH . 'includes/Autoloader.php';
    \Mediasanat\PA\Autoloader::register();

    add_action( 'plugins_loaded', function() {
        load_plugin_textdomain( 'depguard', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
        if ( get_option( 'ms_pa_version' ) !== MEDIASANAT_PA_VERSION ) {
            delete_transient( 'ms_homepage_stats' );
            delete_transient( 'ms_pa_heavy_images' );
            update_option( 'ms_pa_version', MEDIASANAT_PA_VERSION, false );
        }
        if ( ! get_option( 'depguard_migrated_150' ) ) {
            // داده‌های ms_pa و تنظیمات نسخه‌های قبلی عمداً در جای خود باقی می‌مانند.
            add_option( 'ms_pa_domain_categories', [], '', false );
            add_option( 'depguard_migrated_150', 1, '', false );
        }
        $dashboard = new \Mediasanat\PA\Admin\Dashboard();
        $dashboard->init();
    });
}

register_activation_hook( __FILE__, function() {
    add_option( 'ms_resilience_mode', false, '', false );
    add_option( 'ms_blocked_domains', [], '', false );
    add_option( 'ms_resilience_allowlist', [], '', false );
    add_option( 'ms_pa_version', MEDIASANAT_PA_VERSION, '', false );
    add_option( 'ms_pa_operation_mode', 'monitor', '', false );
    add_option( 'ms_pa_domain_rules', [], '', false );
    add_option( 'ms_pa_domain_categories', [], '', false );
    add_option( 'depguard_migrated_150', 1, '', false );
} );
