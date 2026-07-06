<?php
/**
 * Plugin Name: Mediasanat Performance Analyzer
 * Plugin URI:  https://mediasanat.com
 * Description: افزونه پیشرفته و سازمانی بررسی عملکرد و سلامت سرور وردپرس. دارای موتور تحلیل دیتابیس، بررسی المنتور و راهکارهای هوشمند.
 * Version:     1.0.0
 * Author:      hoseinmos
 * Text Domain: mediasanat-performance
 */

if ( ! defined( 'ABSPATH' ) ) exit; // جلوگیری از دسترسی مستقیم

define( 'MEDIASANAT_PA_VERSION', '1.0.0' );
define( 'MEDIASANAT_PA_PATH', plugin_dir_path( __FILE__ ) );
define( 'MEDIASANAT_PA_URL', plugin_dir_url( __FILE__ ) );
define( 'MEDIASANAT_PA_MIN_PHP', '7.4' );
define( 'MEDIASANAT_PA_MIN_WP', '5.5' );

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
    echo '<div class="notice notice-error is-dismissible"><p>افزونه <strong>تحلیل‌گر عملکرد مدیاصنعت</strong> نیازمند PHP نسخه ' . MEDIASANAT_PA_MIN_PHP . ' یا بالاتر است.</p></div>';
}
function mediasanat_pa_wp_notice() {
    echo '<div class="notice notice-error is-dismissible"><p>افزونه <strong>تحلیل‌گر عملکرد مدیاصنعت</strong> نیازمند وردپرس نسخه ' . MEDIASANAT_PA_MIN_WP . ' یا بالاتر است.</p></div>';
}

// 2. راه‌اندازی سیستم در صورت موفقیت
if ( mediasanat_pa_check_compatibility() ) {
    require_once MEDIASANAT_PA_PATH . 'includes/Autoloader.php';
    \Mediasanat\PA\Autoloader::register();

    add_action( 'plugins_loaded', function() {
        $dashboard = new \Mediasanat\PA\Admin\Dashboard();
        $dashboard->init();
    });
}