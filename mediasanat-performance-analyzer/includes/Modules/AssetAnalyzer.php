<?php
namespace Mediasanat\PA\Modules;

if ( ! defined( 'ABSPATH' ) ) exit;

class AssetAnalyzer {

    /**
     * تحلیل واقعی سرعت صفحه اصلی با مدیریت کامل خطا
     */
    public function analyze_homepage() {
        // مرحله ۱: اندازه‌گیری TTFB و دریافت HTML
        $start_time = microtime( true );
        $response = wp_remote_get( home_url( '/?ms_nocache=' . time() ), [
            'timeout'     => 25,
            'redirection' => 5,
            'sslverify'   => false,
            'user-agent'  => 'Mozilla/5.0 (compatible; MediasanatBot/1.0)',
            'headers'     => [ 'Cache-Control' => 'no-cache' ],
        ] );
        $ttfb = round( microtime( true ) - $start_time, 2 );

        // === مدیریت خطا: اگر ارتباط برقرار نشد ===
        if ( is_wp_error( $response ) ) {
            return [
                'status'  => 'error',
                'message' => 'سیستم نتوانست به سایت شما متصل شود. علت: ' . $response->get_error_message(),
                'reason'  => $this->guess_error_reason( $response->get_error_message() ),
            ];
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );

        // === مدیریت خطا: اگر کد پاسخ غیرعادی بود ===
        if ( $code >= 400 || empty( $body ) ) {
            return [
                'status'  => 'error',
                'message' => "سرور کد {$code} برگرداند یا صفحه خالی بود.",
                'reason'  => $this->guess_http_error( $code ),
                'code'    => $code,
            ];
        }

        $html_size = strlen( $body );

        // مرحله ۲: استخراج فایل‌های استاتیک
        $assets = $this->extract_assets( $body );

        // مرحله ۳: اندازه‌گیری حجم و زمان منابع
        $assets_start = microtime( true );
        $total_assets_size = 0;
        $checked = 0;
        $max_check = 15;

        foreach ( $assets as $asset_url ) {
            if ( $checked >= $max_check ) break;
            $asset_res = wp_remote_head( $asset_url, [ 'timeout' => 8, 'sslverify' => false ] );
            if ( ! is_wp_error( $asset_res ) ) {
                $len = wp_remote_retrieve_header( $asset_res, 'content-length' );
                if ( $len ) $total_assets_size += (int) $len;
            }
            $checked++;
        }
        $assets_time = round( microtime( true ) - $assets_start, 2 );

        $total_load_time = round( $ttfb + $assets_time, 2 );
        $total_size_mb   = round( ( $html_size + $total_assets_size ) / ( 1024 * 1024 ), 2 );
        $score = $this->calculate_speed_score( $ttfb, $total_load_time, $total_size_mb, count( $assets ) );

        return [
            'status'        => 'success',
            'ttfb'          => $ttfb,
            'time'          => $total_load_time,
            'size'          => $total_size_mb,
            'html_size'     => round( $html_size / 1024, 1 ),
            'assets_count'  => count( $assets ),
            'code'          => $code,
            'score'         => $score,
        ];
    }

    /**
     * حدس دلیل خطای ارتباط برای راهنمایی کاربر مبتدی
     */
    private function guess_error_reason( $error_msg ) {
        $msg = strtolower( $error_msg );
        if ( strpos( $msg, 'timeout' ) !== false || strpos( $msg, 'timed out' ) !== false ) {
            return 'سرور شما بیش از حد کند است یا فایروال جلوی ارتباط را گرفته است. با پشتیبانی هاست تماس بگیرید.';
        }
        if ( strpos( $msg, 'ssl' ) !== false || strpos( $msg, 'certificate' ) !== false ) {
            return 'گواهی SSL سایت شما مشکل دارد. گواهی امنیتی را از پنل هاست بررسی یا تمدید کنید.';
        }
        if ( strpos( $msg, 'resolve' ) !== false || strpos( $msg, 'dns' ) !== false ) {
            return 'مشکل در DNS. اگر سایت روی لوکال‌هاست (XAMPP) اجرا می‌شود، این ابزار روی سرور واقعی کار می‌کند.';
        }
        if ( strpos( $msg, 'refused' ) !== false ) {
            return 'سرور ارتباط را رد کرد. ممکن است سایت با رمز عبور محافظت شده یا فایروال فعال باشد.';
        }
        return 'اگر سایت شما روی لوکال‌هاست است یا با رمز محافظت می‌شود، تست سرعت کار نمی‌کند. روی سرور اصلی تست کنید.';
    }

    private function guess_http_error( $code ) {
        $errors = [
            401 => 'سایت شما با رمز عبور محافظت می‌شود (احراز هویت). این تست فقط روی سایت‌های عمومی کار می‌کند.',
            403 => 'دسترسی ممنوع شد. فایروال یا افزونه امنیتی جلوی ربات‌ها را گرفته است.',
            404 => 'صفحه اصلی یافت نشد.',
            500 => 'سایت شما دچار خطای داخلی (Error 500) است! این یک مشکل جدی است که باید فوری بررسی شود.',
            503 => 'سایت شما موقتاً در دسترس نیست (احتمالاً حالت تعمیر یا فشار زیاد روی سرور).',
        ];
        return $errors[ $code ] ?? "سرور کد غیرعادی {$code} برگرداند. با پشتیبانی هاست تماس بگیرید.";
    }

    private function extract_assets( $html ) {
        $assets = [];
        $home = home_url();
        preg_match_all( '/<link[^>]+href=["\']([^"\']+\.css[^"\']*)["\']/i', $html, $css );
        preg_match_all( '/<script[^>]+src=["\']([^"\']+\.js[^"\']*)["\']/i', $html, $js );
        preg_match_all( '/<img[^>]+src=["\']([^"\']+)["\']/i', $html, $img );
        $all = array_merge( $css[1] ?? [], $js[1] ?? [], $img[1] ?? [] );

        foreach ( $all as $url ) {
            if ( strpos( $url, 'data:' ) === 0 ) continue; // نادیده گرفتن تصاویر base64
            if ( strpos( $url, '//' ) === 0 ) {
                $url = 'https:' . $url;
            } elseif ( strpos( $url, 'http' ) !== 0 ) {
                $url = rtrim( $home, '/' ) . '/' . ltrim( $url, '/' );
            }
            $assets[ $url ] = $url;
        }
        return array_values( $assets );
    }

    private function calculate_speed_score( $ttfb, $total_time, $size_mb, $asset_count ) {
        $score = 100;
        if ( $ttfb > 0.8 ) $score -= 15;
        if ( $ttfb > 1.5 ) $score -= 15;
        if ( $ttfb > 3 )   $score -= 20;
        if ( $total_time > 2 )  $score -= 15;
        if ( $total_time > 4 )  $score -= 15;
        if ( $size_mb > 2 ) $score -= 10;
        if ( $size_mb > 4 ) $score -= 10;
        if ( $asset_count > 30 ) $score -= 5;
        if ( $asset_count > 60 ) $score -= 10;
        return max( 0, min( 100, $score ) );
    }

    public function get_heavy_images( $limit = 10 ) {
        $args = [
            'post_type'      => 'attachment',
            'post_mime_type' => 'image',
            'posts_per_page' => 150,
            'post_status'    => 'inherit',
            'fields'         => 'ids',
        ];
        $image_ids = get_posts( $args );
        $heavy_images = [];
        foreach ( $image_ids as $img_id ) {
            $file_path = get_attached_file( $img_id );
            if ( $file_path && file_exists( $file_path ) ) {
                $size = filesize( $file_path );
                if ( $size > 200 * 1024 ) {
                    $heavy_images[] = [
                        'title' => get_the_title( $img_id ),
                        'url'   => wp_get_attachment_url( $img_id ),
                        'size'  => round( $size / (1024 * 1024), 2 ) . ' MB',
                        'bytes' => $size
                    ];
                }
            }
        }
        usort( $heavy_images, function($a, $b) { return $b['bytes'] <=> $a['bytes']; } );
        return array_slice( $heavy_images, 0, $limit );
    }
}