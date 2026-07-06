<?php
namespace Mediasanat\PA\Modules;

if ( ! defined( 'ABSPATH' ) ) exit;

class DatabaseProfiler {

    public function get_autoload_size() {
        global $wpdb;
        $query = "SELECT SUM(LENGTH(option_value)) FROM {$wpdb->options} WHERE autoload = 'yes' OR autoload = 'on'";
        $size_in_bytes = $wpdb->get_var( $query );
        return $size_in_bytes ? round( $size_in_bytes / ( 1024 * 1024 ), 2 ) : 0;
    }

    public function count_expired_transients() {
        global $wpdb;
        $now = time();
        $query = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s AND option_value < %d",
            '_transient_timeout_%', $now
        );
        return (int) $wpdb->get_var( $query );
    }

    /**
     * شمارش ریویژن‌های پست (نسخه‌های قدیمی نوشته‌ها)
     */
    public function count_post_revisions() {
        global $wpdb;
        return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'revision'" );
    }

    /**
     * شمارش نظرات اسپم و در انتظار حذف
     */
    public function count_spam_comments() {
        global $wpdb;
        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved = 'spam' OR comment_approved = 'trash'"
        );
    }

    /**
     * شمارش نوشته‌های داخل سطل زباله
     */
    public function count_trashed_posts() {
        global $wpdb;
        return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'trash'" );
    }

    /**
     * حجم کل دیتابیس
     */
    public function get_total_db_size() {
        global $wpdb;
        $size = $wpdb->get_var( $wpdb->prepare(
            "SELECT SUM(data_length + index_length) FROM information_schema.TABLES WHERE table_schema = %s",
            DB_NAME
        ) );
        return $size ? round( $size / (1024 * 1024), 2 ) : 0;
    }

    /**
     * بزرگ‌ترین ۱۰ آپشن Autoload (برای شناسایی افزونه مقصر)
     */
    public function get_largest_autoloads( $limit = 10 ) {
        global $wpdb;
        $results = $wpdb->get_results( $wpdb->prepare(
            "SELECT option_name, ROUND(LENGTH(option_value)/1024, 2) as size_kb
             FROM {$wpdb->options}
             WHERE autoload = 'yes' OR autoload = 'on'
             ORDER BY LENGTH(option_value) DESC
             LIMIT %d",
            $limit
        ), ARRAY_A );
        return $results ?: [];
    }

    /**
     * جداول اضافی احتمالی (جداولی که پیشوند وردپرس ندارند یا یتیم هستند)
     */
    public function get_all_tables_info() {
        global $wpdb;
        $tables = $wpdb->get_results( $wpdb->prepare(
            "SELECT table_name as name, ROUND((data_length + index_length)/1024/1024, 2) as size_mb, table_rows as rows_count
             FROM information_schema.TABLES
             WHERE table_schema = %s
             ORDER BY (data_length + index_length) DESC",
            DB_NAME
        ), ARRAY_A );
        return $tables ?: [];
    }
}