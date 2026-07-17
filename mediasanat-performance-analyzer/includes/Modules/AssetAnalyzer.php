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
        $scan_token = strtolower( wp_generate_password( 24, false, false ) );
        set_transient( 'ms_pa_scan_' . $scan_token, 1, MINUTE_IN_SECONDS );
        $scan_url = add_query_arg( [ 'ms_pa_scan' => time(), 'ms_pa_token' => $scan_token ], home_url( '/' ) );
        $response = wp_remote_get( $scan_url, [
            'timeout'     => 15,
            'redirection' => 5,
            'sslverify'   => true,
            'user-agent'  => 'Mostech-Resilience-Monitor/' . MEDIASANAT_PA_VERSION,
            'headers'     => [ 'Cache-Control' => 'no-cache' ],
        ] );
        $ttfb = max( 0.01, round( microtime( true ) - $start_time, 3 ) );
        delete_transient( 'ms_pa_scan_' . $scan_token );

        // === مدیریت خطا: اگر ارتباط برقرار نشد ===
        if ( is_wp_error( $response ) ) {
            return [
                'status'  => 'error',
                'message' => 'سیستم نتوانست به سایت شما متصل شود. کد خطا: request_failed',
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

        return $this->analyze_html( $body, $ttfb, $code, 'server' );
    }

    /**
     * تحلیل HTML دریافت‌شده از loopback سرور یا fallback مرورگر مدیر.
     */
    public function analyze_html( $body, $response_time, $code = 200, $source = 'server' ) {
        $html_size = strlen( $body );

        // مرحله ۲: استخراج فایل‌های استاتیک
        $assets = $this->expand_local_css_assets( $this->extract_assets( $body ) );
        $external_assets = array_values( array_filter( $assets, [ $this, 'is_external_url' ] ) );
        $internal_assets = array_values( array_diff( $assets, $external_assets ) );

        // مرحله ۳: محاسبه حجم فایل‌های داخلی مستقیماً از دیسک؛ بدون هیچ تماس خارجی.
        $total_assets_size = 0;
        $checked = 0;
        $max_check = 100;

        // اصل مهم محصول: اسکن هرگز برای اندازه‌گیری به سرور خارجی متصل نمی‌شود.
        foreach ( $internal_assets as $asset_url ) {
            if ( $checked >= $max_check ) break;
            $local_path = $this->url_to_local_path( $asset_url );
            if ( $local_path && is_file( $local_path ) ) $total_assets_size += (int) filesize( $local_path );
            $checked++;
        }

        $total_load_time = max( 0.01, round( (float) $response_time, 3 ) );
        $ttfb = $total_load_time;
        $total_size_mb   = round( ( $html_size + $total_assets_size ) / ( 1024 * 1024 ), 2 );
        $score = $this->calculate_speed_score( $ttfb, $total_load_time, $total_size_mb, count( $assets ), count( $external_assets ) );

        return [
            'status'        => 'success',
            'ttfb'          => $ttfb,
            'time'          => $total_load_time,
            'size'          => $total_size_mb,
            'html_size'     => round( $html_size / 1024, 1 ),
            'assets_count'  => count( $assets ),
            'internal_count'=> count( $internal_assets ),
            'external_count'=> count( $external_assets ),
            'external_assets' => $this->describe_external_assets( $external_assets, $this->extract_url_references( $body ) ),
            'code'          => $code,
            'score'         => $score,
            'scan_source'   => $source,
            'scanned_at'    => time(),
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
        $links = $this->extract_relevant_links( $html );
        preg_match_all( '/<script[^>]+src=["\']([^"\']+)["\']/i', $html, $js );
        preg_match_all( '/<(?:img|source)[^>]+(?:src|srcset|data-src)=["\']([^"\']+)["\']/i', $html, $img );
        preg_match_all( '/<(?:iframe|source|video|audio|embed|object)[^>]+(?:src|data)=["\']([^"\']+)["\']/i', $html, $media );
        preg_match_all( '/<form[^>]+action=["\']([^"\']+)["\']/i', $html, $forms );
        preg_match_all( '/url\(["\']?([^\)"\']+)["\']?\)/i', $html, $inline_urls );
        $all = array_merge( $links, $js[1] ?? [], $img[1] ?? [], $media[1] ?? [], $forms[1] ?? [], $inline_urls[1] ?? [] );

        foreach ( $all as $value ) {
            // تمام کاندیداهای srcset بررسی می‌شوند، نه فقط اولین تصویر.
            $candidates = strpos( $value, ',' ) !== false ? explode( ',', $value ) : [ $value ];
            foreach ( $candidates as $url ) {
                $url = trim( preg_replace( '/\s+\d+(?:\.\d+)?[wx]$/i', '', trim( $url ) ) );
                if ( ! $url || strpos( $url, 'data:' ) === 0 || strpos( $url, '#' ) === 0 ) continue;
                if ( strpos( $url, '//' ) === 0 ) {
                    $url = 'https:' . $url;
                } elseif ( strpos( $url, 'http' ) !== 0 ) {
                    $url = rtrim( $home, '/' ) . '/' . ltrim( $url, '/' );
                }
                $assets[ $url ] = $url;
            }
        }
        return array_values( $assets );
    }

    private function extract_relevant_links( $html ) {
        $urls = [];
        $allowed_rels = [ 'stylesheet', 'preload', 'modulepreload', 'prefetch', 'preconnect', 'dns-prefetch', 'icon', 'manifest' ];
        preg_match_all( '/<link\b[^>]*>/i', $html, $tags );
        foreach ( $tags[0] ?? [] as $tag ) {
            if ( ! preg_match( '/href=["\']([^"\']+)["\']/i', $tag, $href ) || ! preg_match( '/rel=["\']([^"\']+)["\']/i', $tag, $rel ) ) continue;
            $rels = preg_split( '/\s+/', strtolower( trim( $rel[1] ) ) );
            if ( array_intersect( $allowed_rels, $rels ) ) $urls[] = $href[1];
        }
        return $urls;
    }

    private function expand_local_css_assets( $assets ) {
        $expanded = array_fill_keys( $assets, true );
        $checked  = 0;
        foreach ( $assets as $css_url ) {
            $path = strtolower( (string) wp_parse_url( $css_url, PHP_URL_PATH ) );
            if ( ! preg_match( '/\.css$/', $path ) || $this->is_external_url( $css_url ) || $checked >= 50 ) continue;
            $local_path = $this->url_to_local_path( $css_url );
            if ( ! $local_path || ! is_readable( $local_path ) || filesize( $local_path ) > 1024 * 1024 ) continue;
            $contents = file_get_contents( $local_path );
            if ( false === $contents ) continue;
            preg_match_all( '/(?:url\(|@import\s+)["\']?([^\)"\';\s]+)["\']?/i', $contents, $matches );
            foreach ( $matches[1] ?? [] as $reference ) {
                if ( 0 === strpos( $reference, 'data:' ) || 0 === strpos( $reference, '#' ) ) continue;
                $resolved = $this->resolve_css_url( $css_url, $reference );
                if ( $resolved ) $expanded[ $resolved ] = true;
            }
            $checked++;
        }
        return array_keys( $expanded );
    }

    private function resolve_css_url( $css_url, $reference ) {
        if ( 0 === strpos( $reference, '//' ) ) return 'https:' . $reference;
        if ( preg_match( '#^https?://#i', $reference ) ) return $reference;
        $parts = wp_parse_url( $css_url );
        if ( empty( $parts['host'] ) ) return false;
        $path = 0 === strpos( $reference, '/' ) ? $reference : dirname( $parts['path'] ) . '/' . $reference;
        $segments = [];
        foreach ( explode( '/', $path ) as $segment ) {
            if ( '' === $segment || '.' === $segment ) continue;
            if ( '..' === $segment ) array_pop( $segments ); else $segments[] = $segment;
        }
        $port = isset( $parts['port'] ) ? ':' . $parts['port'] : '';
        return ( $parts['scheme'] ?? 'https' ) . '://' . $parts['host'] . $port . '/' . implode( '/', $segments );
    }

    private function url_to_local_path( $url ) {
        $home_parts = wp_parse_url( home_url( '/' ) );
        $url_parts  = wp_parse_url( $url );
        if ( empty( $url_parts['host'] ) || empty( $home_parts['host'] ) || strtolower( $url_parts['host'] ) !== strtolower( $home_parts['host'] ) ) return false;
        $home_path = isset( $home_parts['path'] ) ? rtrim( $home_parts['path'], '/' ) : '';
        $url_path  = isset( $url_parts['path'] ) ? rawurldecode( $url_parts['path'] ) : '';
        if ( $home_path && strpos( $url_path, $home_path ) === 0 ) $url_path = substr( $url_path, strlen( $home_path ) );
        $candidate = wp_normalize_path( ABSPATH . ltrim( $url_path, '/' ) );
        $root      = trailingslashit( wp_normalize_path( ABSPATH ) );
        return strpos( $candidate, $root ) === 0 ? $candidate : false;
    }

    public function is_external_url( $url ) {
        $host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
        $home = strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
        return $host && $home && $host !== $home;
    }

    private function extract_url_references( $html ) {
        $html = str_replace( '\\/', '/', html_entity_decode( $html, ENT_QUOTES, 'UTF-8' ) );
        preg_match_all( '#(?:https?:)?//[a-z0-9][a-z0-9.\-]*(?::\d+)?(?:/[^\s<>"\'\\)]*)?#iu', $html, $matches );
        $references = [];
        foreach ( $matches[0] ?? [] as $url ) {
            $url = rtrim( $url, '.,;:]}' );
            if ( 0 === strpos( $url, '//' ) ) $url = 'https:' . $url;
            if ( $this->is_external_url( $url ) ) $references[ $url ] = $url;
        }
        return array_values( $references );
    }

    private function describe_external_assets( $urls, $references = [] ) {
        $items = [];
        $runtime_urls = array_fill_keys( $urls, true );
        foreach ( $urls as $url ) {
            $host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
            if ( ! $host ) continue;
            $path = strtolower( (string) wp_parse_url( $url, PHP_URL_PATH ) );
            $type = 'سایر';
            if ( preg_match( '/\.css$/', $path ) ) $type = 'استایل';
            elseif ( preg_match( '/\.js$/', $path ) ) $type = 'اسکریپت';
            elseif ( preg_match( '/\.(woff2?|ttf|otf)$/', $path ) ) $type = 'فونت';
            elseif ( preg_match( '/\.(jpe?g|png|gif|webp|svg|avif)$/', $path ) ) $type = 'تصویر';
            elseif ( preg_match( '/\.(mp4|webm|mp3|ogg)$/', $path ) ) $type = 'رسانه';

            if ( ! isset( $items[ $host ] ) ) {
                $items[ $host ] = [ 'domain' => $host, 'count' => 0, 'types' => [], 'samples' => [] ];
            }
            $items[ $host ]['count']++;
            $items[ $host ]['types'][ $type ] = $type;
            $sample = $this->safe_origin( $url );
            if ( $sample && count( $items[ $host ]['samples'] ) < 2 && ! in_array( $sample, $items[ $host ]['samples'], true ) ) $items[ $host ]['samples'][] = $sample;
        }
        foreach ( $references as $url ) {
            if ( isset( $runtime_urls[ $url ] ) ) continue;
            $host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
            if ( ! $host ) continue;
            if ( ! isset( $items[ $host ] ) ) {
                $items[ $host ] = [ 'domain' => $host, 'count' => 0, 'types' => [], 'samples' => [] ];
            }
            $items[ $host ]['types']['code_reference'] = 'اشاره URL در کد';
            if ( ! in_array( $url, $items[ $host ]['samples'], true ) ) {
                $items[ $host ]['count']++;
                $sample = $this->safe_origin( $url );
                if ( $sample && count( $items[ $host ]['samples'] ) < 2 && ! in_array( $sample, $items[ $host ]['samples'], true ) ) $items[ $host ]['samples'][] = $sample;
            }
        }
        foreach ( $items as &$item ) $item['types'] = array_values( $item['types'] );
        unset( $item );
        uasort( $items, function( $a, $b ) { return $b['count'] <=> $a['count']; } );
        return array_values( $items );
    }

    private function safe_origin( $url ) {
        $parts = wp_parse_url( $url );
        if ( empty( $parts['host'] ) ) return '';
        $scheme = isset( $parts['scheme'] ) && in_array( strtolower( $parts['scheme'] ), [ 'http', 'https' ], true ) ? strtolower( $parts['scheme'] ) : 'https';
        $port = isset( $parts['port'] ) ? ':' . (int) $parts['port'] : '';
        return $scheme . '://' . strtolower( $parts['host'] ) . $port;
    }

    private function calculate_speed_score( $ttfb, $total_time, $size_mb, $asset_count, $external_count ) {
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
        if ( $external_count > 0 ) $score -= min( 25, 5 + ( $external_count * 2 ) );
        return max( 0, min( 100, $score ) );
    }

    public function get_heavy_images( $limit = 100 ) {
        $uploads = wp_upload_dir();
        $locations = [
            [ 'path' => get_stylesheet_directory(), 'url' => get_stylesheet_directory_uri(), 'source' => 'قالب فعال' ],
        ];
        if ( get_template_directory() !== get_stylesheet_directory() ) {
            $locations[] = [ 'path' => get_template_directory(), 'url' => get_template_directory_uri(), 'source' => 'قالب والد' ];
        }
        $locations[] = [ 'path' => $uploads['basedir'], 'url' => $uploads['baseurl'], 'source' => 'uploads' ];
        $heavy_images = [];
        $seen = [];
        foreach ( $locations as $location ) {
            if ( ! is_dir( $location['path'] ) ) continue;
            $scanned = 0;
            try {
                $iterator = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $location['path'], \FilesystemIterator::SKIP_DOTS ) );
                foreach ( $iterator as $file ) {
                    if ( $scanned++ >= 10000 ) break;
                    if ( ! $file->isFile() || ! preg_match( '/\.(?:jpe?g|png|gif|webp|avif|svg)$/i', $file->getFilename() ) ) continue;
                    $path = wp_normalize_path( $file->getPathname() );
                    if ( isset( $seen[ $path ] ) ) continue;
                    $seen[ $path ] = true;
                    $size = $file->getSize();
                    if ( $size <= 200 * 1024 ) continue;
                    $relative = ltrim( substr( $path, strlen( wp_normalize_path( $location['path'] ) ) ), '/' );
                    $heavy_images[] = [
                        'title'  => $file->getFilename(),
                        'url'    => trailingslashit( $location['url'] ) . str_replace( '%2F', '/', rawurlencode( $relative ) ),
                        'size'   => size_format( $size, 2 ),
                        'bytes'  => $size,
                        'source' => $location['source'],
                    ];
                }
            } catch ( \UnexpectedValueException $exception ) {
                continue;
            }
        }
        usort( $heavy_images, function($a, $b) { return $b['bytes'] <=> $a['bytes']; } );
        return array_slice( $heavy_images, 0, $limit );
    }
}
