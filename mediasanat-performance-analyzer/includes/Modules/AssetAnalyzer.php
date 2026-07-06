<?php
namespace Mediasanat\PA\Modules;

if ( ! defined( 'ABSPATH' ) ) exit;

class AssetAnalyzer {
    
    /**
     * تست سرعت و حجم صفحه اصلی (شبیه‌سازی ریکوئست کاربر)
     */
    public function analyze_homepage() {
        $start_time = microtime( true );
        $response = wp_remote_get( home_url(), [ 'timeout' => 10 ] );
        $end_time = microtime( true );

        if ( is_wp_error( $response ) ) {
            return [ 'status' => 'error', 'message' => 'خطا در برقراری ارتباط با سایت.' ];
        }

        $body = wp_remote_retrieve_body( $response );
        $size_in_mb = round( strlen( $body ) / ( 1024 * 1024 ), 2 );
        $load_time = round( $end_time - $start_time, 2 );

        return [
            'status'  => 'success',
            'time'    => $load_time,
            'size'    => $size_in_mb,
            'code'    => wp_remote_retrieve_response_code( $response )
        ];
    }

    /**
     * استخراج ۵ تصویر سنگین از رسانه (گلوگاه‌های لود)
     */
    public function get_heavy_images( $limit = 5 ) {
        $args = [
            'post_type'      => 'attachment',
            'post_mime_type' => 'image',
            'posts_per_page' => 100, // بررسی ۱۰۰ تصویر آخر برای جلوگیری از مصرف مموری
            'post_status'    => 'inherit',
        ];
        
        $images = get_posts( $args );
        $heavy_images = [];

        foreach ( $images as $img ) {
            $file_path = get_attached_file( $img->ID );
            if ( $file_path && file_exists( $file_path ) ) {
                $size = filesize( $file_path );
                if ( $size > 300 * 1024 ) { // شناسایی تصاویر بالای ۳۰۰ کیلوبایت
                    $heavy_images[] = [
                        'title' => $img->post_title,
                        'url'   => wp_get_attachment_url( $img->ID ),
                        'size'  => round( $size / (1024 * 1024), 2 ) . ' MB',
                        'bytes' => $size
                    ];
                }
            }
        }

        // مرتب‌سازی نزولی بر اساس حجم
        usort( $heavy_images, function($a, $b) {
            return $b['bytes'] <=> $a['bytes'];
        });

        return array_slice( $heavy_images, 0, $limit );
    }
}