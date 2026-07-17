<?php
namespace Mediasanat\PA\Modules;

if ( ! defined( 'ABSPATH' ) ) exit;

class SolutionsEngine {

    public function generate_report( $server_data, $homepage_stats ) {
        $solutions = [];

        // ===== بررسی موفقیت تست سرعت =====
        $speed_test_ok = isset( $homepage_stats['status'] ) && $homepage_stats['status'] === 'success';

        if ( 'not_scanned' === ( $homepage_stats['status'] ?? '' ) ) {
            $solutions[] = [
                'type'  => 'warning',
                'title' => 'اسکن صفحه اصلی هنوز اجرا نشده است',
                'cause' => 'برای جلوگیری از سربار، اسکن سنگین هنگام بازشدن داشبورد خودکار اجرا نمی‌شود.',
                'fix'   => 'در نمای کلی روی «اسکن دوباره» کلیک کنید.'
            ];
        } elseif ( ! $speed_test_ok ) {
            $solutions[] = [
                'type'  => 'danger',
                'title' => '⚠️ تست سرعت سایت انجام نشد!',
                'cause' => $homepage_stats['message'] ?? 'سیستم نتوانست به صفحه اصلی سایت شما متصل شود.',
                'fix'   => $homepage_stats['reason'] ?? 'اگر سایت روی لوکال‌هاست است، این ابزار را روی سرور اصلی (هاست آنلاین) اجرا کنید.'
            ];
        } else {
            $external_count = (int) ( $homepage_stats['risky_external_count'] ?? ( $homepage_stats['external_count'] ?? 0 ) );
            if ( $external_count > 0 ) {
                $solutions[] = [
                    'type'  => 'warning',
                    'title' => "{$external_count} دامنه خارجی در صفحه یا تماس‌های سروری پیدا شد",
                    'cause' => 'در زمان قطع اینترنت بین‌الملل، مرورگر یا وردپرس برای این دامنه‌ها منتظر می‌ماند و ظاهر یا عملکرد سایت ممکن است ناقص شود.',
                    'fix'   => 'در تب «تحلیل دامنه‌ها» نوع و اثر دامنه را بررسی و سپس در بخش سیاست، Allowlist یا Blocklist مناسب را انتخاب کنید.'
                ];
            }
            // زمان اندازه‌گیری‌شده تا دریافت پاسخ/HTML است و ادعای Core Web Vitals یا TTFB ندارد.
            $response_time = $homepage_stats['server_response_time'] ?? null;
            if ( null !== $response_time && $response_time > 2 ) {
                $solutions[] = [
                    'type'  => 'danger',
                    'title' => 'دریافت پاسخ صفحه کند است (' . $response_time . ' ثانیه)',
                    'cause' => 'این مقدار زمان اندازه‌گیری‌شده توسط اسکن DepGuard است و TTFB واقعی مرورگر محسوب نمی‌شود.',
                    'fix'   => 'زمان پاسخ را با ابزار تخصصی مرورگر نیز بررسی کنید و سپس افزونه‌ها، قالب و منابع هاست را روی محیط آزمایشی ارزیابی کنید.'
                ];
            } elseif ( null !== $response_time && $response_time > 1 ) {
                $solutions[] = [
                    'type'  => 'warning',
                    'title' => 'زمان دریافت پاسخ کمی بالاست (' . $response_time . ' ثانیه)',
                    'cause' => 'این اندازه‌گیری داخلی برای مقایسه اسکن‌های DepGuard است، نه PageSpeed.',
                    'fix'   => 'نتیجه را در چند نوبت و همراه ابزار مرورگر مقایسه کنید.'
                ];
            }

            $load_time = $homepage_stats['load_time'] ?? null;
            if ( null !== $load_time && $load_time > 5 ) {
                $solutions[] = [
                    'type'  => 'warning',
                    'title' => 'دریافت کامل HTML طولانی شده است (' . $load_time . ' ثانیه)',
                    'cause' => 'این مقدار زمان دریافت پاسخ اسکن است و زمان رندر کامل صفحه یا Core Web Vitals نیست.',
                    'fix'   => 'پاسخ سرور، حجم HTML و وابستگی‌های خارجی را جداگانه بررسی کنید.'
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
            if ( $assets_count > 120 ) {
                $solutions[] = [
                    'type'  => 'warning',
                    'title' => 'تعداد ارجاع‌های HTML و CSS زیاد است (' . $assets_count . ' مورد)',
                    'cause' => 'این عدد تعداد درخواست واقعی مرورگر نیست و ممکن است شامل preload، srcset یا ارجاع‌های تکرارنشده باشد.',
                    'fix'   => 'در تحلیل دامنه‌ها، نوع ارجاع‌ها را بررسی کنید و برای شمارش درخواست واقعی از ابزار Network مرورگر استفاده کنید.'
                ];
            }
        }

        // ===== نسخه PHP =====
        $php_version = $server_data['php_version'] ?? PHP_VERSION;
        if ( version_compare( $php_version, '8.0', '<' ) ) {
            $solutions[] = [
                'type'  => 'warning',
                'title' => 'نسخه PHP سرور شما قدیمی است (نسخه ' . $php_version . ')',
                'cause' => 'نسخه‌های قدیمی PHP از نظر کارایی و نگهداری امنیتی انتخاب مناسبی برای محصول تجاری نیستند.',
                'fix'   => 'پس از بررسی سازگاری قالب و افزونه‌ها در محیط آزمایشی، PHP را به یک نسخه پشتیبانی‌شده ارتقا دهید.'
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

        $plugin_count = (int) ( $server_data['active_plugins'] ?? 0 );
        if ( $plugin_count > 30 ) {
            $solutions[] = [
                'type'  => 'warning',
                'title' => 'تعداد افزونه‌های فعال زیاد است (' . $plugin_count . ' افزونه)',
                'cause' => 'تعداد به‌تنهایی معیار قطعی کندی نیست، اما سطح تداخل و تعداد کدهای اجراشونده را بالا می‌برد.',
                'fix'   => 'در محیط آزمایشی، افزونه‌های هم‌پوشان یا بلااستفاده را شناسایی و حذف کنید؛ فقط غیرفعال‌کردن برای پاکسازی کافی نیست.'
            ];
        }

        $cron_count = (int) ( $server_data['cron_events'] ?? 0 );
        if ( $cron_count > 100 ) {
            $solutions[] = [
                'type'  => 'warning',
                'title' => 'صف زمان‌بندی وردپرس شلوغ است (' . $cron_count . ' رویداد)',
                'cause' => 'رویدادهای پرتعداد یا معیوب می‌توانند در بازدید کاربران اجرا شوند و پاسخ سرور را کند کنند.',
                'fix'   => 'رویدادهای افزونه‌های حذف‌شده و کارهای پرتکرار را بررسی کنید و اجرای WP-Cron را با cron واقعی هاست هماهنگ کنید.'
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
                'fix'   => 'پس از هر تغییر قالب یا افزونه، اسکن را دوباره اجرا کنید تا وابستگی خارجی تازه‌ای وارد سایت نشده باشد.'
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
