<?php
namespace Mediasanat\PA\Modules;

if ( ! defined( 'ABSPATH' ) ) exit;

class SolutionsEngine {
    
    public function generate_report( $autoload_mb, $expired_transients, $server_data ) {
        $solutions = [];

        if ( $autoload_mb > 1.0 ) {
            $solutions[] = [
                'type' => 'danger',
                'title' => 'حجم بالای Autoload دیتابیس (' . $autoload_mb . ' MB)',
                'fix'  => 'جدول wp_options را بررسی کنید. ردیف‌های مربوط به افزونه‌های حذف شده را شناسایی و با احتیاط پاک کنید. استاندارد مطلوب زیر 800 کیلوبایت است.'
            ];
        }

        if ( $server_data['max_exec_time'] < 300 ) {
            $solutions[] = [
                'type' => 'warning',
                'title' => 'زمان اجرای پایین PHP (Max Execution Time)',
                'fix'  => 'مقدار فعلی ' . $server_data['max_exec_time'] . ' ثانیه است. برای سایت‌های فروشگاهی یا دارای المنتور سنگین، پیشنهاد می‌شود این مقدار در php.ini به 300 افزایش یابد.'
            ];
        }

        if ( empty( $solutions ) ) {
            $solutions[] = [
                'type' => 'success',
                'title' => 'وضعیت سرور و دیتابیس کاملاً پایدار است.',
                'fix'  => 'هیچ گلوگاه بحرانی شناسایی نشد.'
            ];
        }

        return $solutions;
    }
}