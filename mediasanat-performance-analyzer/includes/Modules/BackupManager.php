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
     * ایجاد پوشه بکاپ و محافظت از آن (جلوگیری از دانلود عمومی)
     */
    private function ensure_secure_directory() {
        if ( ! file_exists( $this->backup_dir ) ) {
            wp_mkdir_p( $this->backup_dir );
        }
        $htaccess_path = $this->backup_dir . '/.htaccess';
        if ( ! file_exists( $htaccess_path ) ) {
            // پشتیبانی از Apache 2.2 و 2.4
            $rules  = "<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n";
            $rules .= "<IfModule !mod_authz_core.c>\nOrder allow,deny\nDeny from all\n</IfModule>";
            file_put_contents( $htaccess_path, $rules );
        }
        $index_path = $this->backup_dir . '/index.php';
        if ( ! file_exists( $index_path ) ) {
            file_put_contents( $index_path, "<?php // Silence is golden." );
        }
    }

    /**
     * تهیه بکاپ کامل از دیتابیس با مدیریت صحیح NULL و مقادیر باینری
     */
    public function create_db_backup() {
        global $wpdb;

        // افزایش موقت محدودیت‌ها برای دیتابیس‌های بزرگ
        @set_time_limit( 300 );
        @ini_set( 'memory_limit', '512M' );

        $tables = $wpdb->get_col( "SHOW TABLES" );
        if ( empty( $tables ) ) {
            return [ 'success' => false, 'message' => 'هیچ جدولی در دیتابیس یافت نشد.' ];
        }

        $filename = $this->prefix . date('Ymd_His') . '_' . wp_generate_password(8, false) . '.sql';
        $filepath = $this->backup_dir . '/' . $filename;

        // نوشتن تدریجی روی فایل (به‌جای نگه‌داری کل دیتابیس در حافظه)
        $handle = @fopen( $filepath, 'w' );
        if ( ! $handle ) {
            return [ 'success' => false, 'message' => 'خطا در ایجاد فایل بکاپ. دسترسی نوشتن روی سرور را بررسی کنید.' ];
        }

        fwrite( $handle, "-- بکاپ دیتابیس مدیاصنعت\n-- تاریخ: " . current_time('mysql') . "\n" );
        fwrite( $handle, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n" );

        foreach ( $tables as $table ) {
            $create_table = $wpdb->get_row( "SHOW CREATE TABLE `{$table}`", ARRAY_N );
            fwrite( $handle, "\n\nDROP TABLE IF EXISTS `{$table}`;\n" . $create_table[1] . ";\n\n" );

            // خواندن سطرها به‌صورت دسته‌ای (Batch) برای جلوگیری از پر شدن حافظه
            $offset = 0;
            $batch  = 500;
            do {
                $rows = $wpdb->get_results( "SELECT * FROM `{$table}` LIMIT {$offset}, {$batch}", ARRAY_A );
                if ( $rows ) {
                    foreach ( $rows as $row ) {
                        $values = [];
                        foreach ( $row as $value ) {
                            if ( is_null( $value ) ) {
                                $values[] = 'NULL'; // حفظ مقدار NULL
                            } else {
                                // escape امن با متد استاندارد وردپرس
                                $values[] = "'" . esc_sql( $value ) . "'";
                            }
                        }
                        $vals = implode( ',', $values );
                        fwrite( $handle, "INSERT INTO `{$table}` VALUES ({$vals});\n" );
                    }
                }
                $offset += $batch;
            } while ( count( $rows ) === $batch );
        }

        fwrite( $handle, "\nSET FOREIGN_KEY_CHECKS=1;\n" );
        fclose( $handle );

        return [ 'success' => true, 'message' => 'بکاپ با موفقیت ایجاد شد.', 'file' => $filename ];
    }

    /**
     * دریافت لیست بکاپ‌های ساخته‌شده توسط همین افزونه
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

    public function delete_backup( $filename ) {
        $filename = sanitize_file_name( $filename );
        // جلوگیری از Path Traversal + بررسی پیشوند
        if ( strpos( $filename, $this->prefix ) !== 0 || strpos( $filename, '..' ) !== false ) {
            return false;
        }
        $filepath = $this->backup_dir . '/' . $filename;
        if ( file_exists( $filepath ) ) {
            return unlink( $filepath );
        }
        return false;
    }

    /**
     * دانلود فایل بکاپ (امنیت در کنترلر بررسی می‌شود)
     */
    public function download_backup( $filename ) {
        $filename = sanitize_file_name( $filename );
        if ( strpos( $filename, $this->prefix ) !== 0 || strpos( $filename, '..' ) !== false ) {
            wp_die('دسترسی غیرمجاز.');
        }

        $filepath = $this->backup_dir . '/' . $filename;
        // اطمینان از اینکه فایل واقعاً داخل پوشه بکاپ است (Real Path Check)
        if ( strpos( realpath( $filepath ), realpath( $this->backup_dir ) ) !== 0 ) {
            wp_die('دسترسی غیرمجاز.');
        }

        if ( file_exists( $filepath ) ) {
            nocache_headers();
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="'.basename($filepath).'"');
            header('Content-Length: ' . filesize($filepath));
            readfile($filepath);
            exit;
        }
        wp_die('فایل یافت نشد.');
    }
}