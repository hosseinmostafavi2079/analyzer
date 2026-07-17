<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$scan_ok        = isset( $homepage_stats['status'] ) && 'success' === $homepage_stats['status'];
$score          = $scan_ok ? (int) ( $homepage_stats['score'] ?? 0 ) : 0;
$external_count = (int) ( $homepage_stats['external_domain_count'] ?? ( $homepage_stats['external_count'] ?? 0 ) );
$risky_external_count = (int) ( $homepage_stats['risky_external_count'] ?? ( $homepage_stats['external_count'] ?? 0 ) );
$issue_count    = count( array_filter( $reports, function( $item ) { return 'success' !== $item['type']; } ) );
$status_class   = ! $scan_ok || $risky_external_count > 0 ? 'danger' : ( $score < 80 ? 'warning' : 'success' );
$status_text    = ! $scan_ok ? 'اسکن نیاز به بررسی دارد' : ( $risky_external_count > 0 ? 'وابستگی اجرایی پیدا شد' : 'وابستگی اجرایی پیدا نشد' );
?>
<div class="wrap ms-app-wrap" dir="rtl">
    <div class="ms-dashboard">
        <header class="ms-topbar">
            <div class="ms-brand">
                <span class="ms-logo" aria-hidden="true">م</span>
                <div><h1>پایشگر تاب‌آوری موستک</h1><p>شناسایی و کنترل وابستگی‌های خارجی وردپرس</p></div>
            </div>
            <div class="ms-system-state <?php echo esc_attr( $status_class ); ?>"><span></span><?php echo esc_html( $status_text ); ?></div>
        </header>

        <div class="ms-layout">
            <aside class="ms-sidebar">
                <nav class="ms-nav" aria-label="بخش‌های تحلیل">
                    <button class="active" data-tab="dashboard"><span>01</span>نمای کلی</button>
                    <button data-tab="external"><span>02</span>تحلیل دامنه‌ها <?php if ( $external_count ) : ?><b><?php echo esc_html( $external_count ); ?></b><?php endif; ?></button>
                    <button data-tab="network"><span>03</span>کنترل تاب‌آوری</button>
                    <button data-tab="assets"><span>04</span>رسانه‌های سنگین</button>
                    <button data-tab="server"><span>05</span>سرور</button>
                    <button data-tab="academy"><span>06</span>آموزش کار با ابزار</button>
                </nav>
                <div class="ms-sidebar-note"><strong>حریم خصوصی کامل</strong><p>نتیجه اسکن روی وردپرس شما می‌ماند و به هیچ سروری ارسال نمی‌شود.</p></div>
            </aside>

            <main class="ms-content">
                <section id="tab-dashboard" class="tab-pane active">
                    <div class="ms-section-head"><div><span class="eyebrow">وضعیت امروز</span><h2>سلامت عملکرد سایت</h2></div><button class="ms-button ms-retest-btn">اسکن دوباره</button></div>

                    <?php if ( ! $scan_ok ) : ?>
                        <div class="ms-alert danger"><div class="ms-alert-icon">!</div><div><h3>اسکن صفحه اصلی کامل نشد</h3><p><?php echo esc_html( $homepage_stats['message'] ?? 'پاسخی از صفحه اصلی دریافت نشد.' ); ?></p><small><?php echo esc_html( $homepage_stats['reason'] ?? 'تنظیمات فایروال و دسترسی loopback وردپرس را بررسی کنید.' ); ?></small></div></div>
                    <?php else : ?>
                        <div class="ms-hero-grid">
                            <article class="ms-score-card">
                                <div class="ms-score" style="--score:<?php echo esc_attr( $score ); ?>"><div><strong><?php echo esc_html( $score ); ?></strong><span>از ۱۰۰</span></div></div>
                                <div><span class="eyebrow">امتیاز عملکرد</span><h3><?php echo $score >= 80 ? 'عملکرد خوب و پایدار' : ( $score >= 50 ? 'قابل قبول، نیازمند بهبود' : 'نیازمند اقدام فوری' ); ?></h3><p>این امتیاز از پاسخ سرور، حجم صفحه، تعداد فایل‌ها و وابستگی خارجی ساخته شده است.</p></div>
                            </article>
                            <article class="ms-resilience-card <?php echo $risky_external_count ? 'danger' : 'success'; ?>">
                                <span class="eyebrow">ریسک قطع اینترنت بین‌الملل</span>
                                <strong><?php echo $risky_external_count ? 'بالا' : 'پایین'; ?></strong>
                                <p><?php echo $risky_external_count ? esc_html( $risky_external_count ) . ' دامنه دارای وابستگی اجرایی یا تماس سروری است.' : esc_html( $external_count ) . ' دامنه صرفاً در کد دیده شد و وابستگی اجرایی تأیید نشد.'; ?></p>
                                <?php if ( $external_count ) : ?><button class="ms-link-button" data-open-tab="external">مشاهده جزئیات و راه‌حل ←</button><?php endif; ?>
                            </article>
                        </div>

                        <div class="ms-metrics">
                            <article><span>زمان دریافت پاسخ</span><strong><?php echo esc_html( $homepage_stats['ttfb'] ?? 0 ); ?><small> ثانیه</small></strong><em class="<?php echo ( $homepage_stats['ttfb'] ?? 0 ) > .8 ? 'bad' : 'good'; ?>"><?php echo ( $homepage_stats['ttfb'] ?? 0 ) > .8 ? 'نیازمند بهبود' : 'مناسب'; ?></em></article>
                            <article><span>زمان اسکن داخلی</span><strong><?php echo esc_html( $homepage_stats['time'] ?? 0 ); ?><small> ثانیه</small></strong><em>بدون تماس خارجی</em></article>
                            <article><span>حجم قابل محاسبه</span><strong><?php echo esc_html( $homepage_stats['size'] ?? 0 ); ?><small> MB</small></strong><em>HTML و فایل‌های محلی</em></article>
                            <article><span>تعداد منابع</span><strong><?php echo esc_html( $homepage_stats['assets_count'] ?? 0 ); ?><small> فایل</small></strong><em><?php echo esc_html( $homepage_stats['internal_count'] ?? 0 ); ?> داخلی</em></article>
                        </div>
                    <?php endif; ?>

                    <div class="ms-panel">
                        <div class="ms-panel-head"><div><span class="eyebrow">برنامه اقدام</span><h3>راهکارها به ترتیب اولویت</h3></div><span class="ms-count"><?php echo esc_html( $issue_count ); ?> مورد</span></div>
                        <div class="ms-reports">
                            <?php foreach ( $reports as $report ) : ?>
                                <article class="ms-report <?php echo esc_attr( $report['type'] ); ?>">
                                    <span class="ms-priority"><?php echo 'danger' === $report['type'] ? 'فوری' : ( 'warning' === $report['type'] ? 'بهبود' : 'سالم' ); ?></span>
                                    <div><h4><?php echo esc_html( $report['title'] ); ?></h4><p><b>دلیل:</b> <?php echo esc_html( $report['cause'] ); ?></p><p class="fix"><b>راه‌حل:</b> <?php echo esc_html( $report['fix'] ); ?></p></div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>

                <section id="tab-external" class="tab-pane">
                    <div class="ms-section-head"><div><span class="eyebrow">بخش تشخیص</span><h2>تحلیل جامع دامنه‌های خارجی</h2><p>منابع واقعی، URLهای موجود در کد و تماس‌های سروری را بدون باز کردن دامنه خارجی فهرست می‌کند.</p></div></div>
                    <div class="ms-compare"><article><strong>تحلیل دامنه‌ها چیست؟</strong><p>فقط تشخیص و گزارش می‌دهد. «اشاره URL در کد» لزوماً بارگذاری نمی‌شود، اما اسکریپت، استایل، فونت، تصویر و تماس سروری وابستگی واقعی‌اند.</p></article><article><strong>کنترل تاب‌آوری چیست؟</strong><p>یک اقدام عملی و اضطراری است که درخواست‌های خارجی را متوقف می‌کند؛ ممکن است پرداخت، پیامک، لایسنس یا نقشه را هم قطع کند.</p></article></div>
                    <?php if ( ! $scan_ok && empty( $external_assets ) ) : ?><div class="ms-empty"><h3>ابتدا اسکن را با موفقیت اجرا کنید</h3><p>دکمه اسکن دوباره، در صورت خطای سرور از روش مرورگر استفاده می‌کند.</p></div>
                    <?php elseif ( empty( $external_assets ) ) : ?><div class="ms-empty success"><span>✓</span><h3>وابستگی خارجی پیدا نشد</h3><p>فونت، استایل، اسکریپت، تصویر، رسانه و iframe قابل تشخیص صفحه اصلی محلی هستند.</p></div>
                    <?php else : ?>
                        <div class="ms-alert warning"><div class="ms-alert-icon">!</div><div><h3>نوع هر دامنه را قبل از اقدام بررسی کنید</h3><p>«اشاره URL در کد» ممکن است فقط لینک یا metadata باشد و سرعت را کم نکند. منابع اجرایی و تماس‌های سروری در قطعی اینترنت ریسک واقعی دارند.</p></div></div>
                        <div class="ms-domain-grid">
                            <?php foreach ( $external_assets as $item ) : ?>
                                <article class="ms-domain-card"><div><span class="ms-domain-icon">↗</span><div><h3 dir="ltr"><?php echo esc_html( $item['domain'] ); ?></h3><p><?php echo esc_html( implode( '، ', $item['types'] ) ); ?></p></div></div><strong><?php echo esc_html( $item['count'] ); ?> مورد ردیابی‌شده</strong><ul><?php foreach ( $item['samples'] as $sample ) : ?><li dir="ltr" title="<?php echo esc_attr( $sample ); ?>"><?php echo esc_html( $sample ); ?></li><?php endforeach; ?></ul><button class="ms-button secondary ms-network-action" data-domain="<?php echo esc_attr( $item['domain'] ); ?>" data-type="block">مسدودسازی تماس‌های سروری این دامنه</button></article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>

                <section id="tab-network" class="tab-pane">
                    <div class="ms-section-head"><div><span class="eyebrow">بخش کنترل</span><h2>کنترل تاب‌آوری و تماس‌های شبکه</h2><p>این بخش برخلاف تحلیل دامنه‌ها، رفتار سایت را واقعاً تغییر می‌دهد.</p></div></div>
                    <div class="ms-toggle-card <?php echo $resilience_mode ? 'active' : ''; ?>">
                        <div><span class="eyebrow">حالت قطعی اینترنت</span><h3><?php echo $resilience_mode ? 'تاب‌آوری فعال است' : 'تاب‌آوری غیرفعال است'; ?></h3><p>در حالت فعال، درخواست‌های HTTP سروری و فایل‌های ثبت‌شده وردپرس به دامنه‌های خارجی متوقف می‌شوند. قبل از فعال‌سازی، در محیط آزمایشی بررسی کنید.</p></div>
                        <label class="ms-switch"><input type="checkbox" id="ms-resilience-toggle" <?php checked( $resilience_mode ); ?>><span></span></label>
                    </div>
                    <div class="ms-panel"><div class="ms-panel-head"><div><span class="eyebrow">۲۴ ساعت اخیر</span><h3>دامنه‌های تماس‌گرفته‌شده توسط وردپرس</h3></div><?php if ( $network_logs ) : ?><button class="ms-button ghost ms-clear-logs-btn">پاک‌کردن تاریخچه</button><?php endif; ?></div>
                        <?php if ( empty( $network_logs ) ) : ?><div class="ms-empty compact"><p>هنوز تماس خارجی سروری ثبت نشده است.</p></div><?php else : ?><div class="ms-table-wrap"><table class="ms-table"><thead><tr><th>دامنه</th><th>وضعیت</th><th>زمان</th><th>تعداد</th><th>کنترل</th></tr></thead><tbody><?php foreach ( $network_logs as $domain => $log ) : ?><tr><td dir="ltr"><?php echo esc_html( $domain ); ?></td><td><?php echo esc_html( $log['status'] ); ?></td><td><?php echo null === $log['duration'] ? '—' : esc_html( $log['duration'] ) . ' ثانیه'; ?></td><td><?php echo esc_html( $log['count'] ); ?></td><td><button class="ms-mini-button ms-network-action" data-domain="<?php echo esc_attr( $domain ); ?>" data-type="<?php echo in_array( $domain, $blocked_domains, true ) ? 'unblock' : 'block'; ?>"><?php echo in_array( $domain, $blocked_domains, true ) ? 'آزادسازی' : 'مسدودسازی'; ?></button></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
                    </div>
                </section>

                <section id="tab-assets" class="tab-pane"><div class="ms-section-head"><div><span class="eyebrow">رسانه</span><h2>تصاویر سنگین سایت</h2><p>فایل‌های بالای ۲۰۰ کیلوبایت در uploads، قالب فعال و قالب والد؛ شامل تصاویر سیستمی مانند صفحه ۴۰۴.</p></div></div><?php if ( empty( $heavy_images ) ) : ?><div class="ms-empty success"><span>✓</span><h3>تصویر سنگینی پیدا نشد</h3></div><?php else : ?><div class="ms-panel"><div class="ms-table-wrap"><table class="ms-table"><thead><tr><th>تصویر</th><th>محل</th><th>حجم</th><th>راهکار</th></tr></thead><tbody><?php foreach ( $heavy_images as $image ) : ?><tr><td><a href="<?php echo esc_url( $image['url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $image['title'] ?: 'بدون عنوان' ); ?></a></td><td><?php echo esc_html( $image['source'] ?? 'سایت' ); ?></td><td><?php echo esc_html( $image['size'] ); ?></td><td>تبدیل به WebP/AVIF و استفاده از ابعاد صحیح</td></tr><?php endforeach; ?></tbody></table></div></div><?php endif; ?></section>

                <section id="tab-server" class="tab-pane"><div class="ms-section-head"><div><span class="eyebrow">زیرساخت</span><h2>وضعیت سرور وردپرس</h2></div></div><div class="ms-server-grid"><?php $server_rows = [ 'نسخه PHP' => $server_health['php_version'], 'نسخه وردپرس' => $server_health['wp_version'], 'محدودیت حافظه' => $server_health['memory_limit'], 'OPcache' => $server_health['opcache_active'] ? 'فعال' : 'غیرفعال', 'Object Cache' => $server_health['object_cache'] ? 'فعال' : 'غیرفعال', 'کش صفحه' => $server_health['page_cache'] ? 'اعلام‌شده فعال' : 'اعلام‌نشده', 'افزونه‌های فعال' => $server_health['active_plugins'], 'رویدادهای زمان‌بندی' => $server_health['cron_events'], 'پردازش تصویر' => ( $server_health['gd_active'] || $server_health['imagick_active'] ) ? 'فعال' : 'غیرفعال', 'HTTPS' => $server_health['is_https'] ? 'فعال' : 'غیرفعال', 'فضای خالی' => $server_health['disk_free'] ]; foreach ( $server_rows as $label => $value ) : ?><article><span><?php echo esc_html( $label ); ?></span><strong dir="auto"><?php echo esc_html( $value ); ?></strong></article><?php endforeach; ?></div></section>

                <section id="tab-academy" class="tab-pane"><div class="ms-section-head"><div><span class="eyebrow">راهنمای ساده</span><h2>از کجا شروع کنم؟</h2><p>این مسیر برای مدیرانی است که دانش فنی ندارند.</p></div></div><div class="ms-learning-path"><article><span>۱</span><div><h3>یک اسکن تازه اجرا کنید</h3><p>در «نمای کلی» روی اسکن دوباره بزنید. ابزار صفحه اصلی، کدهای آن و فایل‌های محلی را بررسی می‌کند.</p></div></article><article><span>۲</span><div><h3>نوع دامنه را تشخیص دهید</h3><p>«اشاره URL در کد» الزاماً مشکل سرعت نیست. ابتدا فونت، اسکریپت، استایل، تصویر و تماس سروری را رفع کنید.</p></div></article><article><span>۳</span><div><h3>منابع واقعی را محلی کنید</h3><p>با رعایت مجوز، فایل خارجی را روی هاست ایران قرار دهید و سپس سایت را دوباره اسکن کنید.</p></div></article><article><span>۴</span><div><h3>حالت تاب‌آوری را آزمایشی فعال کنید</h3><p>ابتدا در زمان کم‌ترافیک فعال کنید و پرداخت، پیامک، نقشه و ورود اجتماعی را حتماً تست کنید.</p></div></article></div><div class="ms-panel ms-glossary"><div class="ms-panel-head"><h3>واژه‌نامه کوتاه</h3></div><dl><div><dt>منبع اجرایی</dt><dd>فایلی مانند فونت، CSS یا JavaScript که مرورگر واقعاً برای نمایش صفحه نیاز دارد.</dd></div><div><dt>اشاره URL</dt><dd>آدرسی موجود در HTML یا تنظیمات اسکریپت؛ ممکن است فقط لینک باشد و هیچ درخواستی ایجاد نکند.</dd></div><div><dt>تماس سروری</dt><dd>درخواستی که خود وردپرس از طریق HTTP API به یک دامنه دیگر می‌فرستد.</dd></div><div><dt>حالت تاب‌آوری</dt><dd>توقف موقت ارتباطات خارجی برای جلوگیری از انتظار طولانی هنگام اختلال شبکه.</dd></div></dl></div></section>
            </main>
        </div>
        <div id="ms-toast" class="ms-toast" role="status" aria-live="polite"></div>
    </div>
</div>
