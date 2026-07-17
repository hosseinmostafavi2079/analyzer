<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$scan_ok = isset( $homepage_stats['status'] ) && 'success' === $homepage_stats['status'];
$score = $scan_ok ? (int) ( $homepage_stats['score'] ?? 0 ) : 0;
$external_count = (int) ( $homepage_stats['external_domain_count'] ?? 0 );
$risky_external_count = (int) ( $homepage_stats['risky_external_count'] ?? 0 );
$issue_count = count( array_filter( $reports, function( $item ) { return 'success' !== $item['type']; } ) );
$status_class = ! $scan_ok ? 'warning' : ( $risky_external_count ? 'danger' : 'success' );
$status_text = ! $scan_ok ? 'در انتظار اسکن دستی' : ( $risky_external_count ? 'وابستگی اجرایی پیدا شد' : 'وابستگی اجرایی پیدا نشد' );
$decision_labels = [ 'observe' => 'ثبت شد', 'allow' => 'مجاز', 'would_block' => 'در اجرا مسدود می‌شود', 'block' => 'مسدود شد' ];
?>
<div class="wrap ms-app-wrap" dir="rtl">
    <div class="ms-dashboard">
        <header class="ms-topbar">
            <div class="ms-brand"><span class="ms-logo" aria-hidden="true">م</span><div><h1>پایشگر تاب‌آوری موستک</h1><p>شناسایی و کنترل وابستگی‌های خارجی وردپرس</p></div></div>
            <div class="ms-system-state <?php echo esc_attr( $status_class ); ?>"><span></span><?php echo esc_html( $status_text ); ?></div>
        </header>

        <div class="ms-layout">
            <aside class="ms-sidebar">
                <nav class="ms-nav" aria-label="بخش‌های تحلیل">
                    <button class="active" data-tab="dashboard"><span>01</span>نمای کلی</button>
                    <button data-tab="external"><span>02</span>تحلیل دامنه‌ها <?php if ( $external_count ) : ?><b><?php echo esc_html( $external_count ); ?></b><?php endif; ?></button>
                    <button data-tab="network"><span>03</span>سیاست و تاب‌آوری</button>
                    <button data-tab="assets"><span>04</span>رسانه‌های سنگین</button>
                    <button data-tab="server"><span>05</span>سرور</button>
                    <button data-tab="academy"><span>06</span>آموزش</button>
                </nav>
                <div class="ms-sidebar-note"><strong>بدون Telemetry</strong><p>لاگ‌ها فقط روی وردپرس شما، بدون Query String و حداکثر ۱۲ ساعت نگه‌داری می‌شوند.</p></div>
            </aside>

            <main class="ms-content">
                <section id="tab-dashboard" class="tab-pane active">
                    <div class="ms-section-head"><div><span class="eyebrow">وضعیت سایت</span><h2>سلامت وابستگی‌ها</h2><p>اسکن فقط با اقدام مدیر اجرا و نتیجه آن ۳۰ دقیقه Cache می‌شود.</p></div><button class="ms-button ms-retest-btn">اسکن دوباره</button></div>
                    <?php if ( ! $scan_ok ) : ?>
                        <div class="ms-alert warning"><div class="ms-alert-icon">!</div><div><h3><?php echo 'not_scanned' === ( $homepage_stats['status'] ?? '' ) ? 'اسکن هنوز اجرا نشده است' : 'اسکن صفحه اصلی کامل نشد'; ?></h3><p><?php echo esc_html( $homepage_stats['message'] ?? 'پاسخی دریافت نشد.' ); ?></p><small><?php echo esc_html( $homepage_stats['reason'] ?? '' ); ?></small></div></div>
                    <?php else : ?>
                        <div class="ms-hero-grid">
                            <article class="ms-score-card"><div class="ms-score" style="--score:<?php echo esc_attr( $score ); ?>"><div><strong><?php echo esc_html( $score ); ?></strong><span>از ۱۰۰</span></div></div><div><span class="eyebrow">امتیاز عملکرد</span><h3><?php echo $score >= 80 ? 'عملکرد خوب و پایدار' : ( $score >= 50 ? 'قابل قبول، نیازمند بررسی' : 'نیازمند اقدام' ); ?></h3><p>امتیاز بر اساس پاسخ سرور، حجم صفحه، تعداد فایل‌ها و منابع خارجی محاسبه شده است.</p></div></article>
                            <article class="ms-resilience-card <?php echo $risky_external_count ? 'danger' : 'success'; ?>"><span class="eyebrow">ریسک وابستگی خارجی</span><strong><?php echo $risky_external_count ? 'بالا' : 'پایین'; ?></strong><p><?php echo $risky_external_count ? esc_html( $risky_external_count ) . ' دامنه دارای وابستگی اجرایی یا تماس سروری است.' : 'وابستگی اجرایی تأییدشده‌ای پیدا نشد.'; ?></p><?php if ( $external_count ) : ?><button class="ms-link-button" data-open-tab="external">مشاهده دامنه‌ها ←</button><?php endif; ?></article>
                        </div>
                        <div class="ms-metrics"><article><span>زمان دریافت پاسخ</span><strong><?php echo esc_html( $homepage_stats['ttfb'] ?? 0 ); ?><small> ثانیه</small></strong></article><article><span>زمان اسکن</span><strong><?php echo esc_html( $homepage_stats['time'] ?? 0 ); ?><small> ثانیه</small></strong></article><article><span>حجم قابل محاسبه</span><strong><?php echo esc_html( $homepage_stats['size'] ?? 0 ); ?><small> MB</small></strong></article><article><span>تعداد منابع</span><strong><?php echo esc_html( $homepage_stats['assets_count'] ?? 0 ); ?><small> فایل</small></strong></article></div>
                    <?php endif; ?>
                    <div class="ms-panel"><div class="ms-panel-head"><div><span class="eyebrow">برنامه اقدام</span><h3>راهکارها به ترتیب اولویت</h3></div><span class="ms-count"><?php echo esc_html( $issue_count ); ?> مورد</span></div><div class="ms-reports"><?php foreach ( $reports as $report ) : ?><article class="ms-report <?php echo esc_attr( $report['type'] ); ?>"><span class="ms-priority"><?php echo 'danger' === $report['type'] ? 'فوری' : ( 'warning' === $report['type'] ? 'بررسی' : 'سالم' ); ?></span><div><h4><?php echo esc_html( $report['title'] ); ?></h4><p><b>دلیل:</b> <?php echo esc_html( $report['cause'] ); ?></p><p class="fix"><b>راه‌حل:</b> <?php echo esc_html( $report['fix'] ); ?></p></div></article><?php endforeach; ?></div></div>
                </section>

                <section id="tab-external" class="tab-pane">
                    <div class="ms-section-head"><div><span class="eyebrow">تشخیص بدون تغییر سایت</span><h2>تحلیل دامنه‌های خارجی</h2><p>وجود URL در کد الزاماً به معنی درخواست شبکه نیست؛ نوع و اثر هر دامنه جدا نمایش داده می‌شود.</p></div></div>
                    <div class="ms-compare"><article><strong>تحلیل دامنه‌ها</strong><p>فقط تشخیص می‌دهد و هیچ چیزی را مسدود نمی‌کند.</p></article><article><strong>سیاست و تاب‌آوری</strong><p>Allowlist و Blocklist را مدیریت می‌کند؛ مسدودسازی فقط در حالت اجرایی آزمایشی انجام می‌شود.</p></article></div>
                    <?php if ( empty( $external_assets ) ) : ?><div class="ms-empty"><h3>دامنه‌ای برای نمایش وجود ندارد</h3><p>یک اسکن دستی اجرا کنید یا اجازه دهید پایش شبکه داده جمع‌آوری کند.</p></div><?php else : ?>
                        <div class="ms-domain-grid"><?php foreach ( $external_assets as $item ) : ?>
                            <article class="ms-domain-card" data-category="<?php echo esc_attr( $item['category'] ); ?>">
                                <div><span class="ms-domain-icon">↗</span><div><h3 dir="ltr"><?php echo esc_html( $item['domain'] ); ?></h3><p><?php echo esc_html( implode( '، ', $item['types'] ) ); ?></p></div></div>
                                <div class="ms-domain-meta"><span class="ms-category-badge"><?php echo esc_html( $item['category_label'] ); ?></span><?php if ( $item['rule_list'] ) : ?><span class="ms-rule-badge <?php echo esc_attr( $item['rule_list'] ); ?>"><?php echo 'allow' === $item['rule_list'] ? 'Allowlist' : 'Blocklist'; ?></span><?php endif; ?></div>
                                <p class="ms-impact"><?php echo esc_html( $item['impact'] ); ?></p>
                                <ul><?php foreach ( $item['samples'] as $sample ) : ?><li dir="ltr" title="<?php echo esc_attr( $sample ); ?>"><?php echo esc_html( $sample ); ?></li><?php endforeach; ?></ul>
                                <div class="ms-card-actions"><button class="ms-mini-button ms-quick-rule" data-domain="<?php echo esc_attr( $item['domain'] ); ?>" data-list="allow" data-category="<?php echo esc_attr( $item['category'] ); ?>">افزودن به Allowlist</button><button class="ms-mini-button danger ms-quick-rule" data-domain="<?php echo esc_attr( $item['domain'] ); ?>" data-list="block" data-category="<?php echo esc_attr( $item['category'] ); ?>">افزودن به Blocklist</button></div>
                            </article>
                        <?php endforeach; ?></div>
                    <?php endif; ?>
                </section>

                <section id="tab-network" class="tab-pane">
                    <div class="ms-section-head"><div><span class="eyebrow">سیاست اجرا</span><h2>حالت‌های پایش و تاب‌آوری</h2><p>هیچ دامنه‌ای بدون قرارگرفتن صریح در Blocklist و تأیید حالت اجرایی مسدود نمی‌شود.</p></div></div>
                    <?php if ( $policy_state['emergency_off'] ) : ?><div class="ms-alert danger"><div class="ms-alert-icon">!</div><div><h3>خاموش‌کن اضطراری فعال است</h3><p>ثابت <code>MOSTECH_RESILIENCE_EMERGENCY_OFF</code> در wp-config.php فعال است؛ افزونه فقط پایش می‌کند.</p></div></div><?php endif; ?>
                    <div class="ms-mode-grid">
                        <article class="ms-mode-card <?php echo 'monitor' === $policy_state['mode'] ? 'active' : ''; ?>"><span>01</span><h3>پایش</h3><p>درخواست‌ها فقط ثبت و تحلیل می‌شوند.</p><button class="ms-button secondary ms-mode-button" data-mode="monitor">فعال‌کردن پایش</button></article>
                        <article class="ms-mode-card <?php echo 'simulate' === $policy_state['mode'] ? 'active' : ''; ?>"><span>02</span><h3>شبیه‌سازی</h3><p>دامنه‌های Blocklist با برچسب «مسدود می‌شد» ثبت می‌شوند، اما درخواست ادامه پیدا می‌کند.</p><button class="ms-button secondary ms-mode-button" data-mode="simulate">فعال‌کردن شبیه‌سازی</button></article>
                        <article class="ms-mode-card danger <?php echo 'enforce' === $policy_state['mode'] ? 'active' : ''; ?>"><span>03</span><h3>اجرایی آزمایشی</h3><p>فقط دامنه‌های Blocklist واقعاً و برای مدت محدود مسدود می‌شوند.</p><label class="ms-field"><span>مدت آزمایش</span><select id="ms-enforce-duration"><option value="5">۵ دقیقه</option><option value="15" selected>۱۵ دقیقه</option><option value="30">۳۰ دقیقه</option><option value="60">۶۰ دقیقه</option></select></label><label class="ms-confirm"><input type="checkbox" id="ms-enforce-confirm" <?php disabled( $policy_state['emergency_off'] ); ?>> خطر قطع پرداخت، پیامک و سرویس‌های ضروری را می‌پذیرم.</label><button class="ms-button danger ms-mode-button" data-mode="enforce" id="ms-enforce-button" data-emergency="<?php echo $policy_state['emergency_off'] ? '1' : '0'; ?>" disabled>شروع آزمایش اجرایی</button></article>
                    </div>
                    <?php if ( 'enforce' === $policy_state['mode'] ) : ?><div class="ms-trial-banner" data-remaining="<?php echo esc_attr( $policy_state['remaining'] ); ?>"><strong>حالت اجرایی فعال است</strong><span id="ms-trial-countdown">در حال محاسبه…</span><button class="ms-button ghost ms-mode-button" data-mode="monitor">خاموش‌کردن فوری</button></div><?php endif; ?>

                    <div class="ms-panel ms-rule-manager">
                        <div class="ms-panel-head"><div><span class="eyebrow">قوانین مدیر</span><h3>Allowlist و Blocklist</h3></div><span class="ms-count"><?php echo esc_html( count( $domain_rules ) ); ?> قانون</span></div>
                        <form id="ms-domain-rule-form" class="ms-rule-form"><label class="ms-field"><span>دامنه</span><input type="text" name="domain" dir="ltr" placeholder="api.example.com" required></label><label class="ms-field"><span>فهرست</span><select name="list"><option value="allow">Allowlist</option><option value="block">Blocklist</option></select></label><label class="ms-field"><span>دسته‌بندی</span><select name="category"><?php foreach ( $categories as $key => $data ) : ?><option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $data['label'] ); ?></option><?php endforeach; ?></select></label><button class="ms-button" type="submit">ذخیره قانون</button></form>
                        <div class="ms-rule-filters"><input type="search" id="ms-rule-search" placeholder="جست‌وجوی دامنه…"><select id="ms-rule-list-filter"><option value="">همه فهرست‌ها</option><option value="allow">Allowlist</option><option value="block">Blocklist</option></select><select id="ms-rule-category-filter"><option value="">همه دسته‌ها</option><?php foreach ( $categories as $key => $data ) : ?><option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $data['label'] ); ?></option><?php endforeach; ?></select></div>
                        <div class="ms-table-wrap"><table class="ms-table"><thead><tr><th>دامنه</th><th>فهرست</th><th>دسته</th><th>تأثیر احتمالی قطع</th><th>عملیات</th></tr></thead><tbody id="ms-rules-body"><?php if ( empty( $domain_rules ) ) : ?><tr class="ms-no-rules"><td colspan="5">هنوز قانونی ثبت نشده است.</td></tr><?php else : ?><?php foreach ( $domain_rules as $domain => $rule ) : $category_data = $categories[ $rule['category'] ] ?? $categories['unknown']; ?><tr data-domain-row data-domain="<?php echo esc_attr( $domain ); ?>" data-list="<?php echo esc_attr( $rule['list'] ); ?>" data-category="<?php echo esc_attr( $rule['category'] ); ?>"><td dir="ltr"><?php echo esc_html( $domain ); ?></td><td><span class="ms-rule-badge <?php echo esc_attr( $rule['list'] ); ?>"><?php echo 'allow' === $rule['list'] ? 'Allowlist' : 'Blocklist'; ?></span></td><td><?php echo esc_html( $category_data['label'] ); ?></td><td class="ms-wrap-cell"><?php echo esc_html( $category_data['impact'] ); ?></td><td><button class="ms-mini-button danger ms-delete-rule" data-domain="<?php echo esc_attr( $domain ); ?>">حذف</button></td></tr><?php endforeach; ?><?php endif; ?></tbody></table></div>
                    </div>

                    <div class="ms-panel"><div class="ms-panel-head"><div><span class="eyebrow">لاگ تجمیعی ۱۲ ساعت اخیر</span><h3>درخواست‌های خارجی مشاهده‌شده</h3></div><?php if ( $network_logs ) : ?><button class="ms-button ghost ms-clear-logs-btn">پاک‌کردن لاگ‌ها</button><?php endif; ?></div>
                        <div class="ms-privacy-note">فقط دامنه، کد وضعیت، زمان و تصمیم ذخیره می‌شود؛ مسیر URL، Query String، Cookie، Token، ایمیل، موبایل و اطلاعات سفارش ذخیره نمی‌شوند.</div>
                        <?php if ( empty( $network_logs ) ) : ?><div class="ms-empty compact"><p>هنوز درخواست خارجی ثبت نشده است.</p></div><?php else : ?><div class="ms-table-wrap"><table class="ms-table"><thead><tr><th>دامنه</th><th>دسته</th><th>کانال</th><th>تصمیم</th><th>وضعیت</th><th>زمان</th><th>تعداد</th></tr></thead><tbody><?php foreach ( $network_logs as $domain => $log ) : $log_category = $categories[ $log['category'] ?? 'unknown' ] ?? $categories['unknown']; ?><tr><td dir="ltr"><?php echo esc_html( $domain ); ?></td><td><?php echo esc_html( $log_category['label'] ); ?></td><td><?php echo 'frontend' === ( $log['channel'] ?? '' ) ? 'فایل Frontend' : 'درخواست سرور'; ?></td><td><?php echo esc_html( $decision_labels[ $log['decision'] ?? 'observe' ] ?? 'ثبت شد' ); ?></td><td dir="ltr"><?php echo esc_html( $log['status'] ?? '—' ); ?></td><td><?php echo null === ( $log['duration'] ?? null ) ? '—' : esc_html( $log['duration'] ) . ' ثانیه'; ?></td><td><?php echo esc_html( $log['count'] ?? 1 ); ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
                    </div>
                    <div class="ms-emergency-help"><strong>خاموش‌کردن اضطراری از wp-config.php</strong><code>define( 'MOSTECH_RESILIENCE_EMERGENCY_OFF', true );</code><p>این Constant بر همه تنظیمات مقدم است و در اولین درخواست، مسدودسازی واقعی را متوقف می‌کند.</p></div>
                </section>

                <section id="tab-assets" class="tab-pane"><div class="ms-section-head"><div><span class="eyebrow">اسکن درخواستی</span><h2>تصاویر سنگین سایت</h2><p>این پردازش فقط با کلیک مدیر اجرا و نتیجه آن یک ساعت Cache می‌شود.</p></div><button class="ms-button ms-scan-images-btn">اسکن تصاویر</button></div><?php if ( ! $heavy_scan_ready ) : ?><div class="ms-empty"><h3>اسکن تصاویر اجرا نشده است</h3><p>برای بررسی uploads و قالب فعال روی «اسکن تصاویر» کلیک کنید.</p></div><?php elseif ( empty( $heavy_images ) ) : ?><div class="ms-empty success"><span>✓</span><h3>تصویر بالای ۲۰۰ کیلوبایت پیدا نشد</h3></div><?php else : ?><div class="ms-panel"><div class="ms-table-wrap"><table class="ms-table"><thead><tr><th>تصویر</th><th>محل</th><th>حجم</th><th>راهکار</th></tr></thead><tbody><?php foreach ( $heavy_images as $image ) : ?><tr><td><a href="<?php echo esc_url( $image['url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $image['title'] ?: 'بدون عنوان' ); ?></a></td><td><?php echo esc_html( $image['source'] ?? 'سایت' ); ?></td><td><?php echo esc_html( $image['size'] ); ?></td><td>تبدیل به WebP/AVIF و استفاده از ابعاد صحیح</td></tr><?php endforeach; ?></tbody></table></div></div><?php endif; ?></section>

                <section id="tab-server" class="tab-pane"><div class="ms-section-head"><div><span class="eyebrow">زیرساخت</span><h2>وضعیت سرور وردپرس</h2></div></div><div class="ms-server-grid"><?php $server_rows = [ 'نسخه PHP' => $server_health['php_version'], 'نسخه وردپرس' => $server_health['wp_version'], 'محدودیت حافظه' => $server_health['memory_limit'], 'OPcache' => $server_health['opcache_active'] ? 'فعال' : 'غیرفعال', 'Object Cache' => $server_health['object_cache'] ? 'فعال' : 'غیرفعال', 'کش صفحه' => $server_health['page_cache'] ? 'اعلام‌شده فعال' : 'اعلام‌نشده', 'افزونه‌های فعال' => $server_health['active_plugins'], 'رویدادهای زمان‌بندی' => $server_health['cron_events'], 'پردازش تصویر' => ( $server_health['gd_active'] || $server_health['imagick_active'] ) ? 'فعال' : 'غیرفعال', 'HTTPS' => $server_health['is_https'] ? 'فعال' : 'غیرفعال', 'فضای خالی' => $server_health['disk_free'] ]; foreach ( $server_rows as $label => $value ) : ?><article><span><?php echo esc_html( $label ); ?></span><strong dir="auto"><?php echo esc_html( $value ); ?></strong></article><?php endforeach; ?></div></section>

                <section id="tab-academy" class="tab-pane"><div class="ms-section-head"><div><span class="eyebrow">راهنمای کوتاه</span><h2>ترتیب امن استفاده</h2></div></div><div class="ms-learning-path"><article><span>۱</span><div><h3>با پایش شروع کنید</h3><p>در این حالت هیچ درخواستی قطع نمی‌شود و دامنه‌های واقعی جمع‌آوری می‌شوند.</p></div></article><article><span>۲</span><div><h3>قوانین را دسته‌بندی کنید</h3><p>سرویس‌های ضروری را Allowlist و فقط دامنه‌های تأییدشده را Blocklist کنید.</p></div></article><article><span>۳</span><div><h3>شبیه‌سازی را بررسی کنید</h3><p>لاگ نشان می‌دهد کدام درخواست‌ها در حالت اجرایی مسدود خواهند شد.</p></div></article><article><span>۴</span><div><h3>آزمایش اجرایی کوتاه</h3><p>ابتدا ۵ دقیقه فعال کنید و پرداخت، پیامک، ورود، نقشه و بروزرسانی را آزمایش کنید.</p></div></article></div></section>
            </main>
        </div>
        <div id="ms-toast" class="ms-toast" role="status" aria-live="polite"></div>
    </div>
</div>
