<?php
namespace Mediasanat\PA\Modules;

if ( ! defined( 'ABSPATH' ) ) exit;

class SolutionsEngine {

    public function generate_report( $autoload_mb, $expired_transients, $server_data, $homepage_stats ) {
        $solutions = [];

        // ===== بررسی موفقیت تست سرعت =====
        $speed_test_ok = isset( $homepage_stats['status'] ) && $homepage_stats['status'] === 'success';

        if ( ! $speed_test_ok ) {
            $solutions[] = [
                'type'  => 'danger',
                'title' => '⚠️ تست سرعت سایت انجام نشد!',
                'cause' => $homepage_stats['message'] ?? 'سیستم نتوانست به صفحه اصلی سایت شما متصل شود.',
                'fix'   => $homepage_stats['reason'] ?? 'اگر سایت روی لوکال‌هاست است، این ابزار را روی سرور اصلی (هاست آنلاین) اجرا کنید.'
            ];
        } else {
            // ===== تحلیل TTFB =====
            $ttfb = $homepage_stats['ttfb'] ?? 0;
            if ( $ttfb > 1.5 ) {
                $solutions[] = [
                    'type'  => 'danger',
                    'title' => 'سرور شما دیر جواب می‌دهد (زمان پاسخ اولیه: ' . $ttfb . ' ثانیه)',
                    'cause' => 'زمان پاسخ سرور (TTFB) باید زیر ۰.۸ ثانیه باشد. این کندی معمولاً به دلیل ضعف هاست، نبود کش، یا افزونه‌های سنگین است.',
                    'fix'   => 'یک افزونه کش مثل WP Rocket یا LiteSpeed Cache نصب کنید. اگر هاست اشتراکی دارید، به هاست قوی‌تر (NVMe) ارتقا دهید.'
                ];
            } elseif ( $ttfb > 0.8 ) {
                $solutions[] = [
                    'type'  => 'warning',
                    'title' => 'زمان پاسخ سرور کمی بالاست (' . $ttfb . ' ثانیه)',
                    'cause' => 'سرور در حد قابل قبول است اما جای بهتر شدن دارد.',
                    'fix'   => 'مطمئن شوید افزونه کش فعال است و افزونه‌های غیرضروری را غیرفعال کنید.'
                ];
            }

            // ===== زمان کل لود =====
            $total_time = $homepage_stats['time'] ?? 0;
            if ( $total_time > 3 ) {
                $solutions[] = [
                    'type'  => 'danger',
                    'title' => 'سرعت کلی بارگذاری سایت کُند است (' . $total_time . ' ثانیه)',
                    'cause' => 'کاربران معمولاً بعد از ۳ ثانیه سایت را ترک می‌کنند.',
                    'fix'   => 'تصاویر سنگین را فشرده کنید (تب «فایل‌های سنگین» را ببینید) و از فرمت WebP استفاده کنید.'
                ];
            }

            // ===== حجم صفحه =====
            $page_size = $homepage_stats['size'] ?? 0;
            if ( $page_size > 3 ) {
                $solutions[] = [
                    'type'  => 'warning',
                    'title' => 'حجم صفحه اصلی سنگین است (' . $page_size . ' مگابایت)',
                    'cause' => 'صفحه بیش از ۲-۳ مگابایت، روی موبایل به‌کندی باز می‌شود.',
                    'fix'   => 'تصاویر را بهینه کنید و از افزونه‌های Lazy Load استفاده کنید.'
                ];
            }

            // ===== تعداد فایل‌ها =====
            $assets_count = $homepage_stats['assets_count'] ?? 0;
            if ( $assets_count > 50 ) {
                $solutions[] = [
                    'type'  => 'warning',
                    'title' => 'تعداد فایل‌های بارگذاری‌شده زیاد است (' . $assets_count . ' فایل)',
                    'cause' => 'هر فایل یک درخواست جداگانه به سرور است. تعداد زیاد، سایت را کند می‌کند.',
                    'fix'   => 'از قابلیت ادغام و فشرده‌سازی فایل‌ها (Minify & Combine) در افزونه کش استفاده کنید.'
                ];
            }
        }

        // ===== تحلیل دیتابیس =====
        if ( $autoload_mb > 1.0 ) {
            $solutions[] = [
                'type'  => 'warning',
                'title' => 'انباشت زباله در حافظه دیتابیس (' . $autoload_mb . ' مگابایت)',
                'cause' => 'افزونه‌های قدیمی اطلاعات خود را جا گذاشته‌اند و سایت مجبور است در هر بازدید این اطلاعات بی‌فایده را لود کند.',
                'fix'   => 'از افزونه WP-Optimize برای پاکسازی جدول wp_options استفاده کنید. حجم استاندارد زیر ۱ مگابایت است.'
            ];
        }

        // ===== ترنزینت‌ها =====
        if ( $expired_transients > 50 ) {
            $solutions[] = [
                'type'  => 'warning',
                'title' => 'تعداد زیادی فایل موقت منقضی‌شده وجود دارد (' . $expired_transients . ' مورد)',
                'cause' => 'کش‌های موقتی که تاریخشان گذشته، فضای دیتابیس را اشغال کرده‌اند.',
                'fix'   => 'از دکمه «پاکسازی امن و خودکار» در همین صفحه استفاده کنید. هیچ آسیبی به سایت نمی‌زند.'
            ];
        }

        // ===== نسخه PHP =====
        $php_version = $server_data['php_version'] ?? PHP_VERSION;
        if ( version_compare( $php_version, '8.0', '<' ) ) {
            $solutions[] = [
                'type'  => 'warning',
                'title' => 'نسخه PHP سرور شما قدیمی است (نسخه ' . $php_version . ')',
                'cause' => 'PHP 8 تا ۳ برابر سریع‌تر از نسخه ۷ است.',
                'fix'   => 'از پنل هاست (بخش MultiPHP Manager) نسخه PHP را به ۸.۱ یا بالاتر تغییر دهید یا از پشتیبانی هاست بخواهید.'
            ];
        }

        // ===== حافظه =====
        $memory = $this->parse_size( $server_data['memory_limit'] ?? '0' );
        if ( $memory > 0 && $memory < 256 ) {
            $solutions[] = [
                'type'  => 'warning',
                'title' => 'محدودیت حافظه سرور پایین است (' . ($server_data['memory_limit'] ?? '?') . ')',
                'cause' => 'حافظه کم باعث خطای سفید صفحه هنگام کارهای سنگین می‌شود.',
                'fix'   => 'حافظه را به حداقل 256M افزایش دهید (در wp-config.php یا با کمک پشتیبانی هاست).'
            ];
        }

        // ===== SSL =====
        if ( isset( $server_data['is_https'] ) && $server_data['is_https'] === false ) {
            $solutions[] = [
                'type'  => 'danger',
                'title' => 'سایت شما گواهی امنیتی (SSL) ندارد',
                'cause' => 'سایت بدون HTTPS توسط مرورگرها «ناامن» علامت‌گذاری می‌شود و رتبه گوگل پایین می‌آید.',
                'fix'   => 'از پنل هاست، گواهی رایگان Let\'s Encrypt را فعال کنید.'
            ];
        }

        // ===== پیام موفقیت فقط اگر تست سرعت موفق بود و هیچ مشکلی نبود =====
        if ( empty( $solutions ) && $speed_test_ok ) {
            $solutions[] = [
                'type'  => 'success',
                'title' => 'تبریک! سایت شما در وضعیت بسیار خوبی است.',
                'cause' => 'سرور، دیتابیس و سرعت بارگذاری همگی در محدوده استاندارد هستند.',
                'fix'   => 'به همین روند ادامه دهید، مرتب بکاپ بگیرید و از نصب افزونه‌های غیرضروری خودداری کنید.'
            ];
        }

        return $solutions;
    }

    private function parse_size( $size ) {
        $size = trim( $size );
        $unit = strtolower( substr( $size, -1 ) );
        $value = (int) $size;
        switch ( $unit ) {
            case 'g': return $value * 1024;
            case 'm': return $value;
            case 'k': return $value / 1024;
            default:  return $value / (1024 * 1024);
        }
    }
}