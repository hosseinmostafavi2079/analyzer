<?php
namespace Mediasanat\PA\Modules;

if ( ! defined( 'ABSPATH' ) ) exit;

class BackupManager {
    
    private $backup_dir;
    private $prefix = 'mediasanat_db_backup_';

    public function __construct() {
        $upload_dir = wp_upload_dir();
        $this->backup_dir = $upload_dir['basedir'] . '/mediasanat-backups';
        $this->ensure_secure_directory();
    }

    /**
     * ایجاد پوشه بکاپ و محافظت از آن با htaccess (جلوگیری از دانلود عمومی)
     */
    private function ensure_secure_directory() {
        if ( ! file_exists( $this->backup_dir ) ) {
            wp_mkdir_p( $this->backup_dir );
        }
        $htaccess_path = $this->backup_dir . '/.htaccess';
        if ( ! file_exists( $htaccess_path ) ) {
            file_put_contents( $htaccess_path, "Order allow,deny\nDeny from all" );
        }
        $index_path = $this->backup_dir . '/index.php';
        if ( ! file_exists( $index_path ) ) {
            file_put_contents( $index_path, "<?php // Silence is golden." );
        }
    }

    /**
     * تهیه بکاپ کامل از دیتابیس وردپرس با استفاده از PHP
     */
    public function create_db_backup() {
        global $wpdb;
        
        $tables = $wpdb->get_col( "SHOW TABLES" );
        $sql_dump = "-- بکاپ دیتابیس مدیاصنعت\n-- تاریخ: " . current_time('mysql') . "\n\n";

        foreach ( $tables as $table ) {
            $create_table = $wpdb->get_row( "SHOW CREATE TABLE {$table}", ARRAY_N );
            $sql_dump .= "\n\nDROP TABLE IF EXISTS {$table};\n" . $create_table[1] . ";\n\n";

            $rows = $wpdb->get_results( "SELECT * FROM {$table}", ARRAY_A );
            if ( $rows ) {
                foreach ( $rows as $row ) {
                    $vals = array_map( [ $wpdb, 'escape' ], array_values( $row ) );
                    $vals = implode( "','", $vals );
                    $sql_dump .= "INSERT INTO {$table} VALUES ('{$vals}');\n";
                }
            }
        }

        $filename = $this->prefix . date('Ymd_His') . '_' . wp_generate_password(6, false) . '.sql';
        $filepath = $this->backup_dir . '/' . $filename;
        
        if ( file_put_contents( $filepath, $sql_dump ) !== false ) {
            return [ 'success' => true, 'message' => 'بکاپ با موفقیت ایجاد شد.', 'file' => $filename ];
        }
        return [ 'success' => false, 'message' => 'خطا در نوشتن فایل بکاپ در سرور.' ];
    }

    /**
     * دریافت لیست بکاپ‌هایی که فقط توسط همین افزونه گرفته شده‌اند
     */
    public function get_backups() {
        $backups = [];
        if ( ! is_dir( $this->backup_dir ) ) return $backups;

        $files = scandir( $this->backup_dir );
        foreach ( $files as $file ) {
            if ( strpos( $file, $this->prefix ) === 0 && pathinfo( $file, PATHINFO_EXTENSION ) === 'sql' ) {
                $filepath = $this->backup_dir . '/' . $file;
                $backups[] = [
                    'filename' => $file,
                    'date'     => date_i18n( 'Y/m/d H:i:s', filemtime( $filepath ) ),
                    'size'     => round( filesize( $filepath ) / (1024 * 1024), 2 ) . ' MB'
                ];
            }
        }
        usort($backups, function($a, $b) { return strtotime($b['date']) - strtotime($a['date']); });
        return $backups;
    }

    /**
     * حذف بکاپ (با بررسی امنیتی پیشوند)
     */
    public function delete_backup( $filename ) {
        if ( strpos( $filename, $this->prefix ) !== 0 ) {
            return false;
        }
        $filepath = $this->backup_dir . '/' . sanitize_file_name( $filename );
        if ( file_exists( $filepath ) ) {
            return unlink( $filepath );
        }
        return false;
    }

    /**
     * دانلود فایل بکاپ (این متد در کنترلر صدا زده می‌شود)
     */
    public function download_backup( $filename ) {
        if ( strpos( $filename, $this->prefix ) !== 0 ) wp_die('دسترسی غیرمجاز.');
        
        $filepath = $this->backup_dir . '/' . sanitize_file_name( $filename );
        if ( file_exists( $filepath ) ) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="'.basename($filepath).'"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filepath));
            readfile($filepath);
            exit;
        }
        wp_die('فایل یافت نشد.');
    }
}