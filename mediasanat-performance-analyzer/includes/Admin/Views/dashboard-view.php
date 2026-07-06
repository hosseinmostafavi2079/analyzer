<div class="wrap ms-app-wrap" dir="rtl">
    <div class="ms-glass-dashboard">
        <header class="app-header">
            <div class="brand">
                <h1>تحلیل‌گر عملکرد سایت</h1>
                <span class="version">دستیار هوشمند بررسی سرعت و سلامت</span>
            </div>
            <div class="global-status safe">✅ وضعیت سیستم: در حال نظارت</div>
        </header>

        <main class="app-grid">
            <aside class="app-sidebar glass-panel">
                <ul class="nav-tabs">
                    <li class="active" data-tab="dashboard">📊 وضعیت کلی سایت</li>
                    <li data-tab="backup">💾 نقطه امن (بکاپ)</li>
                    <li data-tab="network">🌐 مدیریت تحریم‌ها و شبکه</li>
                    <li data-tab="assets">📦 فایل‌های سنگین</li>
                    <li data-tab="database">🗄️ سلامت پایگاه داده</li>
                    <li data-tab="server">🖥️ اطلاعات سرور</li>
                </ul>
            </aside>

            <section class="app-content">

                <!-- TAB 1: Main Dashboard -->
                <div id="tab-dashboard" class="tab-pane active">

                    <?php $test_failed = ! isset($homepage_stats['status']) || $homepage_stats['status'] !== 'success'; ?>

                    <?php if ( $test_failed ): ?>
                        <!-- حالت خطا: تست سرعت انجام نشد -->
                        <div class="glass-card error-banner">
                            <div class="error-icon">🚫</div>
                            <div class="error-content">
                                <h2>تست سرعت سایت انجام نشد</h2>
                                <p class="error-msg"><?php echo esc_html( $homepage_stats['message'] ?? 'ارتباط با سایت برقرار نشد.' ); ?></p>
                                <div class="error-fix">
                                    <strong>💡 چه کار کنم؟</strong>
                                    <p><?php echo esc_html( $homepage_stats['reason'] ?? 'اگر سایت روی لوکال‌هاست است، این ابزار را روی سرور اصلی اجرا کنید.' ); ?></p>
                                </div>
                                <button class="btn-glass ms-retest-btn" style="width:auto; margin-top:15px;">🔄 تلاش مجدد برای تست سرعت</button>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- امتیاز کلی سرعت -->
                        <?php $score = $homepage_stats['score'] ?? 0;
                            $score_color = $score >= 80 ? 'var(--ms-success)' : ($score >= 50 ? 'var(--ms-warning)' : 'var(--ms-danger)');
                            $score_text  = $score >= 80 ? 'عالی 🎉' : ($score >= 50 ? 'متوسط ⚠️' : 'ضعیف 🐌');
                            $score_desc  = $score >= 80 ? 'سایت شما سریع است و کاربران تجربه خوبی دارند.' : ($score >= 50 ? 'سایت شما قابل قبول است اما جای بهبود دارد. راهکارهای پایین را بخوانید.' : 'سایت شما کُند است و نیاز به بهینه‌سازی فوری دارد. راهکارهای پایین را دنبال کنید.');
                        ?>
                        <div class="glass-card score-card">
                            <div class="score-circle" style="background: conic-gradient(<?php echo $score_color; ?> <?php echo $score * 3.6; ?>deg, #e2e8f0 0deg);">
                                <div class="score-inner">
                                    <span class="score-num" style="color:<?php echo $score_color; ?>;"><?php echo esc_html( $score ); ?></span>
                                    <span class="score-max">از ۱۰۰</span>
                                </div>
                            </div>
                            <div class="score-info">
                                <h2>امتیاز سرعت سایت شما: <span style="color:<?php echo $score_color; ?>;"><?php echo $score_text; ?></span></h2>
                                <p><?php echo esc_html( $score_desc ); ?></p>
                                <button class="btn-glass ms-retest-btn" style="width:auto; margin-top:12px; background:var(--ms-text-muted); font-size:13px; padding:8px 16px;">🔄 تست مجدد سرعت</button>
                            </div>
                        </div>

                        <div class="data-grid">
                            <div class="glass-card stat-card">
                                <h3>⚡ زمان پاسخ سرور (TTFB)</h3>
                                <?php $ttfb = $homepage_stats['ttfb'] ?? 0; ?>
                                <div class="stat-value <?php echo $ttfb > 1.5 ? 'danger' : ($ttfb > 0.8 ? 'warning' : 'success'); ?>">
                                    <?php echo esc_html( $ttfb ); ?><small> ثانیه</small>
                                </div>
                                <p class="stat-desc">مدت زمان اولین پاسخ سرور. باید زیر ۰.۸ ثانیه باشد.</p>
                            </div>

                            <div class="glass-card stat-card">
                                <h3>⏱️ زمان کل بارگذاری</h3>
                                <?php $speed = $homepage_stats['time'] ?? 0; ?>
                                <div class="stat-value <?php echo $speed > 3 ? 'danger' : ($speed > 2 ? 'warning' : 'success'); ?>">
                                    <?php echo esc_html( $speed ); ?><small> ثانیه</small>
                                </div>
                                <p class="stat-desc">زمان تخمینی لود کامل صفحه شامل تصاویر و اسکریپت‌ها.</p>
                            </div>

                            <div class="glass-card stat-card">
                                <h3>📄 حجم صفحه اصلی</h3>
                                <?php $psize = $homepage_stats['size'] ?? 0; ?>
                                <div class="stat-value <?php echo $psize > 3 ? 'danger' : ($psize > 2 ? 'warning' : 'success'); ?>">
                                    <?php echo esc_html( $psize ); ?><small> مگابایت</small>
                                </div>
                                <p class="stat-desc">حجم کل صفحه. بهتر است زیر ۲ مگابایت باشد.</p>
                            </div>

                            <div class="glass-card stat-card">
                                <h3>🔗 تعداد فایل‌ها</h3>
                                <?php $acount = $homepage_stats['assets_count'] ?? 0; ?>
                                <div class="stat-value <?php echo $acount > 50 ? 'warning' : 'success'; ?>">
                                    <?php echo esc_html( $acount ); ?><small> فایل</small>
                                </div>
                                <p class="stat-desc">تعداد فایل‌های CSS، JS و تصویر هنگام باز شدن صفحه.</p>
                            </div>

                            <div class="glass-card stat-card">
                                <h3>🗄️ بار اضافه دیتابیس</h3>
                                <div class="stat-value <?php echo $autoload_size > 1 ? 'danger' : 'success'; ?>">
                                    <?php echo esc_html( $autoload_size ); ?><small> مگابایت</small>
                                </div>
                                <p class="stat-desc">حجم اطلاعاتی که بی‌دلیل در هر بازدید بارگذاری می‌شود.</p>
                            </div>

                            <div class="glass-card stat-card action-card">
                                <h3>🗑️ فایل‌های موقت اضافی</h3>
                                <div class="stat-value <?php echo $transients > 0 ? 'warning' : 'success'; ?>"><?php echo esc_html( $transients ); ?></div>
                                <p class="stat-desc">کش‌های تاریخ‌گذشته که فقط فضا اشغال کرده‌اند.</p>
                                <?php if( $transients > 0 ): ?>
                                    <button class="btn-glass" data-action="ms_clear_transients" data-msg="آیا مایلید فایل‌های موقت و تاریخ‌گذشته پاک شوند؟ این کار هیچ آسیبی به اطلاعات شما نمی‌زند.">پاکسازی امن</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- دستیار هوشمند -->
                    <div class="glass-card report-card">
                        <div class="card-header"><h2>🔧 دستیار هوشمند رفع مشکلات</h2></div>
                        <div class="card-body">
                            <?php
                                // شمارش مشکلات بر اساس نوع
                                $danger_count = count( array_filter( $reports, function($r){ return $r['type']==='danger'; } ) );
                                $warning_count = count( array_filter( $reports, function($r){ return $r['type']==='warning'; } ) );
                            ?>
                            <?php if ( $danger_count > 0 || $warning_count > 0 ): ?>
                                <div class="report-summary">
                                    <?php if($danger_count): ?><span class="summary-badge danger"><?php echo $danger_count; ?> مشکل جدی</span><?php endif; ?>
                                    <?php if($warning_count): ?><span class="summary-badge warning"><?php echo $warning_count; ?> هشدار</span><?php endif; ?>
                                    <span style="color:var(--ms-text-muted); font-size:13px;">به ترتیب اولویت، موارد زیر را حل کنید:</span>
                                </div>
                            <?php endif; ?>
                            <ul class="solutions-list">
                                <?php
                                    // مرتب‌سازی: اول خطرناک، بعد هشدار، آخر موفق
                                    $order = ['danger'=>0, 'warning'=>1, 'success'=>2];
                                    usort( $reports, function($a,$b) use($order){ return $order[$a['type']] <=> $order[$b['type']]; } );
                                    foreach( $reports as $i => $report ):
                                ?>
                                    <li class="report-item <?php echo esc_attr( $report['type'] ); ?>">
                                        <div class="report-item-header">
                                            <span class="report-badge <?php echo esc_attr($report['type']); ?>">
                                                <?php echo $report['type']==='danger' ? '🔴 مهم' : ($report['type']==='warning' ? '🟡 هشدار' : '🟢 خوب'); ?>
                                            </span>
                                            <h4><?php echo esc_html( $report['title'] ); ?></h4>
                                        </div>
                                        <p><strong>❓ علت:</strong> <?php echo esc_html( $report['cause'] ); ?></p>
                                        <p><strong>✅ راه حل:</strong> <?php echo esc_html( $report['fix'] ); ?></p>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: Backup Manager -->
                <div id="tab-backup" class="tab-pane">
                    <div class="glass-card">
                        <div class="card-header"><h2>مدیریت نقاط امن (بکاپ پایگاه داده)</h2></div>
                        <div class="card-body">
                            <div class="ms-guide-box">
                                <strong>💡 چرا باید بکاپ بگیرم و چطور اطلاعاتم را برگردانم؟</strong>
                                <p>همیشه قبل از پاکسازی دیتابیس یا اعمال تغییرات، یک «نقطه امن» ایجاد کنید. سیستم ما در عرض چند ثانیه از قلب سایت شما (دیتابیس) یک کپی می‌گیرد.</p>
                                <ul>
                                    <li><strong>چگونه بکاپ بگیرم؟</strong> فقط کافیست روی دکمه آبی رنگ زیر کلیک کنید.</li>
                                    <li><strong>چگونه سایت را به گذشته برگردانم؟</strong> اگر مشکلی پیش آمد، ابتدا بکاپ را «دانلود» کنید. سپس وارد کنترل پنل هاست شوید، ابزار <code>phpMyAdmin</code> را باز کرده و در تب <code>Import</code> فایل را آپلود کنید.</li>
                                </ul>
                            </div>

                            <div style="margin-bottom: 20px;">
                                <button class="btn-glass ms-create-backup-btn" style="background:var(--ms-primary); font-size:16px; padding:12px 24px; width:auto;">➕ ایجاد یک نقطه امن جدید (بکاپ)</button>
                            </div>

                            <table class="ms-table">
                                <thead>
                                    <tr>
                                        <th>نام فایل بکاپ</th>
                                        <th>تاریخ ایجاد</th>
                                        <th>حجم فایل</th>
                                        <th>عملیات (مدیریت)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($ms_backups)): ?>
                                        <tr><td colspan="4" style="text-align:center;">هنوز هیچ بکاپی گرفته نشده است.</td></tr>
                                    <?php else: ?>
                                        <?php foreach($ms_backups as $bkp): ?>
                                        <tr>
                                            <td dir="ltr" style="text-align:left; font-size:12px;"><?php echo esc_html($bkp['filename']); ?></td>
                                            <td dir="ltr" style="text-align:left; font-size:13px;"><?php echo esc_html($bkp['date']); ?></td>
                                            <td style="font-weight:bold; color:var(--ms-text-muted);"><?php echo esc_html($bkp['size']); ?></td>
                                            <td>
                                                <a href="<?php echo esc_url( wp_nonce_url( admin_url('admin-post.php?action=ms_download_backup&file=' . urlencode($bkp['filename'])), 'ms_download_backup' ) ); ?>" class="btn-glass" style="background:var(--ms-success); padding:5px 10px; font-size:12px; display:inline-block; text-align:center; width:auto;">⬇️ دانلود</a>
                                                <button class="btn-glass ms-delete-backup-btn" style="background:var(--ms-danger); padding:5px 10px; font-size:12px; width:auto;" data-file="<?php echo esc_attr($bkp['filename']); ?>">🗑️ حذف</button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- TAB 3: Network -->
                <div id="tab-network" class="tab-pane">
                    <div class="glass-card">
                        <div class="card-header"><h2>مدیریت ارتباطات خارجی و تحریم‌ها (بسیار مهم در ایران)</h2></div>
                        <div class="card-body">
                            <div class="ms-guide-box">
                                <strong>💡 راهنمای ساده: چرا این بخش به سرعت سایت شما کمک می‌کند؟</strong>
                                <p>گاهی قالب یا افزونه‌های شما تلاش می‌کنند به سایت‌های خارجی (مثل فونت گوگل) متصل شوند. چون سرور در ایران است یا سایت مقصد ما را تحریم کرده، ارتباط برقرار نمی‌شود و سایت برای چند ثانیه "گیر" می‌کند.</p>
                                <ul>
                                    <li><strong>ستون «زمان پاسخ» را ببینید:</strong> اگر عدد این ستون بالا (مثلاً بیشتر از ۲ ثانیه) بود، یعنی آن دامنه سایت شما را کند می‌کند و بهتر است مسدودش کنید.</li>
                                    <li><strong>نگران خرابی نباشید:</strong> اگر بعد از مسدود کردن مشکلی دیدید، دوباره روی "آزادسازی" کلیک کنید تا همه‌چیز به حالت اول برگردد.</li>
                                </ul>
                            </div>

                            <button class="btn-glass ms-clear-logs-btn" style="background:var(--ms-text-muted); width:auto; padding:8px 16px; font-size:13px; margin-bottom:15px;">🔄 پاک کردن لیست و شروع نظارت مجدد</button>

                            <table class="ms-table">
                                <thead>
                                    <tr>
                                        <th>آدرس سایت خارجی</th>
                                        <th>وضعیت ارتباط</th>
                                        <th>زمان پاسخ</th>
                                        <th>تعداد تماس</th>
                                        <th>عملیات امن</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($network_logs)): ?>
                                        <tr><td colspan="5" style="text-align:center;">هنوز هیچ ارتباط خارجی ثبت نشده است. کمی در سایت خود کار کنید و سپس این صفحه را رفرش کنید.</td></tr>
                                    <?php else: ?>
                                        <?php foreach($network_logs as $host => $log):
                                            $is_blocked = in_array($host, $blocked_domains, true);
                                            $has_error = ! empty($log['is_error']);
                                            $duration = $log['duration'] ?? null;
                                            $is_slow = ( $duration !== null && $duration > 2 );
                                        ?>
                                        <tr>
                                            <td dir="ltr" style="text-align:left; font-weight:bold;"><?php echo esc_html($host); ?></td>
                                            <td dir="ltr" style="text-align:left; font-size:13px; color: <?php echo $has_error ? 'var(--ms-danger)' : 'var(--ms-success)'; ?>;">
                                                <?php echo $has_error ? '❌ مسدود یا فیلتر شده' : '✅ ارتباط موفق'; ?><br>
                                                <span style="font-size:11px; color:#888;"><?php echo esc_html($log['status']); ?></span>
                                            </td>
                                            <td style="font-weight:bold; color: <?php echo $is_slow ? 'var(--ms-danger)' : 'var(--ms-text-muted)'; ?>;">
                                                <?php echo $duration !== null ? esc_html($duration) . ' ثانیه' : '—'; ?>
                                                <?php if($is_slow): ?><br><span style="font-size:10px; color:var(--ms-danger);">⚠️ کُند</span><?php endif; ?>
                                            </td>
                                            <td style="text-align:center;"><?php echo esc_html($log['count'] ?? 1); ?></td>
                                            <td>
                                                <?php if($is_blocked): ?>
                                                    <button class="btn-glass ms-network-action" style="background:var(--ms-success); width:auto;" data-domain="<?php echo esc_attr($host); ?>" data-type="unblock">🔓 آزادسازی</button>
                                                <?php else: ?>
                                                    <button class="btn-glass ms-network-action" style="background:var(--ms-danger); width:auto;" data-domain="<?php echo esc_attr($host); ?>" data-type="block">🚫 مسدودسازی</button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- TAB 4: Assets -->
                <div id="tab-assets" class="tab-pane">
                    <div class="glass-card">
                        <div class="card-header"><h2>سنگین‌ترین تصاویر سایت</h2></div>
                        <div class="card-body">
                            <p>تصاویر حجیم یکی از اصلی‌ترین دلایل کُندی سایت در موبایل هستند. لیست زیر تصاویری را نشان می‌دهد که بیش از حد سنگین هستند.</p>
                            <?php if( empty($heavy_images) ): ?>
                                <p style="color:var(--ms-success); font-weight:bold;">🎉 عالی! تصویر خیلی سنگینی در سایت شما پیدا نشد.</p>
                            <?php else: ?>
                                <table class="ms-table">
                                    <thead><tr><th>نام عکس</th><th>حجم عکس (باید زیر ۰.۳ مگابایت باشد)</th><th>لینک</th></tr></thead>
                                    <tbody>
                                        <?php foreach($heavy_images as $img): ?>
                                            <tr>
                                                <td><?php echo esc_html($img['title']); ?></td>
                                                <td style="color:var(--ms-danger);font-weight:bold;"><?php echo esc_html($img['size']); ?></td>
                                                <td><a href="<?php echo esc_url($img['url']); ?>" target="_blank">دیدن عکس</a></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- TAB 5: Database -->
                <div id="tab-database" class="tab-pane">
                    <?php
                        // ساخت نمونه برای داده‌های اضافی دیتابیس
                        $db_profiler = new \Mediasanat\PA\Modules\DatabaseProfiler();
                        $revisions   = $db_profiler->count_post_revisions();
                        $spam        = $db_profiler->count_spam_comments();
                        $trash       = $db_profiler->count_trashed_posts();
                        $total_db    = $db_profiler->get_total_db_size();
                        $big_options = $db_profiler->get_largest_autoloads(10);
                    ?>
                    <div class="glass-card">
                        <div class="card-header"><h2>جزئیات و سلامت دیتابیس (پایگاه داده)</h2></div>
                        <div class="card-body">
                            <div class="ms-guide-box">
                                <strong>دیتابیس چیست و چه ربطی به سرعت دارد؟</strong>
                                <p>دیتابیس قلب سایت شماست. بخشی به نام <strong>Autoload</strong> تنظیمات سایت را نگه می‌دارد. اگر افزونه‌های زیادی نصب و پاک کنید، این بخش پر از زباله می‌شود و سایت کُند می‌شود.</p>
                            </div>

                            <div class="data-grid" style="grid-template-columns: repeat(4, 1fr);">
                                <div class="glass-card stat-card">
                                    <h3>💾 حجم کل دیتابیس</h3>
                                    <div class="stat-value normal"><?php echo esc_html($total_db); ?> MB</div>
                                </div>
                                <div class="glass-card stat-card">
                                    <h3>🗄️ حجم Autoload</h3>
                                    <div class="stat-value <?php echo $autoload_size > 1 ? 'danger' : 'success'; ?>"><?php echo esc_html($autoload_size); ?> MB</div>
                                </div>
                                <div class="glass-card stat-card">
                                    <h3>📝 نسخه‌های قدیمی نوشته‌ها</h3>
                                    <div class="stat-value <?php echo $revisions > 200 ? 'warning' : 'normal'; ?>"><?php echo esc_html($revisions); ?></div>
                                </div>
                                <div class="glass-card stat-card">
                                    <h3>🗑️ اسپم و زباله</h3>
                                    <div class="stat-value <?php echo ($spam+$trash) > 50 ? 'warning' : 'normal'; ?>"><?php echo esc_html($spam + $trash); ?></div>
                                </div>
                            </div>

                            <h3 style="margin-top:30px;">🔍 سنگین‌ترین تنظیمات Autoload (برای شناسایی افزونه مقصر)</h3>
                            <p style="color:var(--ms-text-muted); font-size:13px;">اگر یکی از این موارد حجم غیرعادی داشت، احتمالاً مربوط به یک افزونه پرمصرف است. با جستجوی نام آن در گوگل می‌توانید افزونه مربوطه را پیدا کنید.</p>
                            <table class="ms-table">
                                <thead><tr><th>نام تنظیم (Option)</th><th>حجم (کیلوبایت)</th></tr></thead>
                                <tbody>
                                    <?php foreach($big_options as $opt): ?>
                                        <tr>
                                            <td dir="ltr" style="text-align:left; font-family:monospace; font-size:12px;"><?php echo esc_html($opt['option_name']); ?></td>
                                            <td style="font-weight:bold; color: <?php echo $opt['size_kb'] > 100 ? 'var(--ms-danger)' : 'var(--ms-text-muted)'; ?>;"><?php echo esc_html($opt['size_kb']); ?> KB</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- TAB 6: Server Info -->
                <div id="tab-server" class="tab-pane">
                    <div class="glass-card">
                        <div class="card-header"><h2>اطلاعات فنی سرور و هاست</h2></div>
                        <div class="card-body">
                            <div class="ms-guide-box">
                                <strong>این اطلاعات به چه دردی می‌خورد؟</strong>
                                <p>اگر خواستید از پشتیبانی هاست کمک بگیرید یا مشکلی را رفع کنید، این اطلاعات را به آن‌ها نشان دهید. موارد قرمز رنگ نیاز به توجه دارند.</p>
                            </div>
                            <table class="ms-table">
                                <tbody>
                                    <tr><td>نسخه PHP</td><td dir="ltr" style="text-align:left; font-weight:bold; color: <?php echo version_compare($server_health['php_version'],'8.0','<') ? 'var(--ms-danger)' : 'var(--ms-success)'; ?>;"><?php echo esc_html($server_health['php_version']); ?></td></tr>
                                    <tr><td>نسخه وردپرس</td><td dir="ltr" style="text-align:left;"><?php echo esc_html($server_health['wp_version']); ?></td></tr>
                                    <tr><td>نسخه دیتابیس (MySQL)</td><td dir="ltr" style="text-align:left;"><?php echo esc_html($server_health['db_version']); ?></td></tr>
                                    <tr><td>محدودیت حافظه (Memory Limit)</td><td dir="ltr" style="text-align:left; font-weight:bold;"><?php echo esc_html($server_health['memory_limit']); ?></td></tr>
                                    <tr><td>حداکثر زمان اجرا</td><td dir="ltr" style="text-align:left;"><?php echo esc_html($server_health['max_exec_time']); ?> ثانیه</td></tr>
                                    <tr><td>حداکثر حجم آپلود</td><td dir="ltr" style="text-align:left;"><?php echo esc_html($server_health['upload_max']); ?></td></tr>
                                    <tr><td>گواهی امنیتی SSL (HTTPS)</td><td dir="ltr" style="text-align:left; font-weight:bold; color: <?php echo $server_health['is_https'] ? 'var(--ms-success)' : 'var(--ms-danger)'; ?>;"><?php echo $server_health['is_https'] ? '✅ فعال' : '❌ غیرفعال'; ?></td></tr>
                                    <tr><td>شتاب‌دهنده OPcache</td><td dir="ltr" style="text-align:left; color: <?php echo $server_health['opcache_active'] ? 'var(--ms-success)' : 'var(--ms-warning)'; ?>;"><?php echo $server_health['opcache_active'] ? '✅ فعال' : '⚠️ غیرفعال'; ?></td></tr>
                                    <tr><td>کش Redis</td><td dir="ltr" style="text-align:left;"><?php echo $server_health['redis_active'] ? '✅ موجود' : '➖ نصب نشده'; ?></td></tr>
                                    <tr><td>پردازش تصویر (GD / Imagick)</td><td dir="ltr" style="text-align:left;"><?php echo ($server_health['gd_active'] || $server_health['imagick_active']) ? '✅ فعال' : '❌ غیرفعال'; ?></td></tr>
                                    <tr><td>فضای خالی دیسک</td><td dir="ltr" style="text-align:left;"><?php echo esc_html($server_health['disk_free']); ?></td></tr>
                                    <tr><td>نرم‌افزار سرور</td><td dir="ltr" style="text-align:left; font-size:12px;"><?php echo esc_html($server_health['server_software']); ?></td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </section>
        </main>

        <?php \Mediasanat\PA\Core\SafetyEngine::render_backup_warning_modal(); ?>
    </div>
</div>