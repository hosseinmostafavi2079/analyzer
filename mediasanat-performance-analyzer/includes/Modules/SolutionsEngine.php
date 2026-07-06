<?php
namespace Mediasanat\PA\Modules;

if ( ! defined( 'ABSPATH' ) ) exit;

class SolutionsEngine {
    
    public function generate_report( $autoload_mb, $expired_transients, $server_data, $homepage_stats ) {
        $solutions = [];

        // تحلیل لود صفحه
        if ( isset($homepage_stats['time']) && $homepage_stats['time'] > 2 ) {
            $solutions[] = [
                'type'  => 'danger',
                'title' => 'سرعت بارگذاری سایت کُند است (' . $homepage_stats['time'] . ' ثانیه)',
                'cause' => 'سرور شما برای آماده کردن صفحه اول سایت زمان زیادی صرف می‌کند.',
                'fix'   => 'اگر از افزونه‌های کش (مثل راکت) استفاده نمی‌کنید، حتماً نصب کنید. اگر نصب دارید، افزونه‌های غیرضروری را غیرفعال کنید تا بار پردازش کمتر شود.'
            ];
        }

        // تحلیل دیتابیس (Autoload)
        if ( $autoload_mb > 1.0 ) {
            $solutions[] = [
                'type'  => 'warning',
                'title' => 'انباشت زباله در حافظه دیتابیس (' . $autoload_mb . ' مگابایت)',
                'cause' => 'افزونه‌هایی که در گذشته نصب و سپس پاک کرده‌اید، اطلاعات خود را در دیتابیس جا گذاشته‌اند و سایت شما مجبور است در هر بار باز شدن، این اطلاعات بی‌فایده را لود کند.',
                'fix'   => 'از یک افزونه بهینه‌ساز دیتابیس (بخش پاکسازی wp_options) استفاده کنید تا این زباله‌ها پاک شوند. حجم استاندارد باید زیر ۱ مگابایت باشد.'
            ];
        }

        // تحلیل سرور
        if ( $server_data['max_exec_time'] < 300 ) {
            $solutions[] = [
                'type'  => 'warning',
                'title' => 'محدودیت زمان پردازش سرور',
                'cause' => 'هاست شما اجازه نمی‌دهد یک پردازش بیشتر از ' . $server_data['max_exec_time'] . ' ثانیه طول بکشد. این موضوع باعث خطای 504 یا نصفه کاره ماندن کارهای سنگین (مثل آپدیت المنتور) می‌شود.',
                'fix'   => 'به پشتیبانی هاست خود تیکت بزنید و بخواهید مقدار Max Execution Time را به 300 افزایش دهند.'
            ];
        }

        if ( empty( $solutions ) ) {
            $solutions[] = [
                'type'  => 'success',
                'title' => 'تبریک! سایت شما در بهترین وضعیت ممکن است.',
                'cause' => 'سرور و دیتابیس با بالاترین راندمان در حال کار هستند.',
                'fix'   => 'به همین روند ادامه دهید و از نصب افزونه‌های متفرقه خودداری کنید.'
            ];
        }

        return $solutions;
    }
}