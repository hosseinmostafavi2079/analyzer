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
                </ul>
            </aside>

            <section class="app-content">
                
                <!-- TAB 1: Main Dashboard -->
                <div id="tab-dashboard" class="tab-pane active">
                    <div class="data-grid">
                        <div class="glass-card stat-card">
                            <h3>⏱️ زمان پاسخگویی سایت</h3>
                            <?php $speed = $homepage_stats['time'] ?? 0; ?>
                            <div class="stat-value <?php echo $speed > 2 ? 'danger' : 'success'; ?>">
                                <?php echo esc_html( $speed ); ?> ثانیه
                            </div>
                            <p class="stat-desc">مدت زمانی که طول می‌کشد تا سرور اولین اطلاعات را به کاربر بفرستد.</p>
                        </div>
                        
                        <div class="glass-card stat-card">
                            <h3>🗄️ بار اضافه دیتابیس</h3>
                            <div class="stat-value <?php echo $autoload_size > 1 ? 'danger' : 'success'; ?>">
                                <?php echo esc_html( $autoload_size ); ?> MB
                            </div>
                            <p class="stat-desc">حجم اطلاعاتی که بی‌دلیل در هر بازدید بارگذاری می‌شود.</p>
                        </div>

                        <div class="glass-card stat-card action-card">
                            <h3>🗑️ فایل‌های موقت اضافی</h3>
                            <div class="stat-value warning"><?php echo esc_html( $transients ); ?></div>
                            <p class="stat-desc">کش‌های تاریخ‌گذشته که فقط فضا اشغال کرده‌اند.</p>
                            <?php if( $transients > 0 ): ?>
                                <button class="btn-glass" data-action="ms_clear_transients" data-msg="آیا مایلید فایل‌های موقت و تاریخ‌گذشته سایت پاک شوند؟ این کار هیچ آسیبی به اطلاعات اصلی شما نمی‌زند.">پاکسازی امن و خودکار</button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- User-Friendly Solutions -->
                    <div class="glass-card report-card">
                        <div class="card-header"><h2>دستیار هوشمند رفع مشکلات</h2></div>
                        <div class="card-body">
                            <p style="margin-bottom:20px; color:var(--ms-text-muted);">سیستم به صورت خودکار سایت شما را بررسی کرده و راهکارهای زیر را برای افزایش سرعت پیشنهاد می‌دهد:</p>
                            <ul class="solutions-list">
                                <?php foreach( $reports as $report ): ?>
                                    <li class="report-item <?php echo esc_attr( $report['type'] ); ?>">
                                        <h4 style="margin-top:0;"><?php echo esc_html( $report['title'] ); ?></h4>
                                        <p><strong>علت مشکل:</strong> <?php echo esc_html( $report['cause'] ); ?></p>
                                        <p><strong>راه حل:</strong> <?php echo esc_html( $report['fix'] ); ?></p>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: Backup Manager -->
                <div id="tab-backup" class="tab-pane">
                    <div class="glass-card">
                        <div class="card-header">
                            <h2>مدیریت نقاط امن (بکاپ پایگاه داده)</h2>
                        </div>
                        <div class="card-body">
                            <div class="ms-guide-box">
                                <strong>💡 چرا باید بکاپ بگیرم و چطور اطلاعاتم را برگردانم؟</strong>
                                <p>همیشه قبل از پاکسازی دیتابیس یا اعمال تغییرات برای افزایش سرعت، یک «نقطه امن» ایجاد کنید. سیستم ما در عرض چند ثانیه از قلب سایت شما (دیتابیس) یک کپی می‌گیرد.</p>
                                <ul>
                                    <li><strong>چگونه بکاپ بگیرم؟</strong> فقط کافیست روی دکمه آبی رنگ زیر کلیک کنید. سیستم خودش بقیه کارها را انجام می‌دهد.</li>
                                    <li><strong>چگونه سایت را به گذشته برگردانم؟</strong> اگر مشکلی پیش آمد، ابتدا بکاپ را از لیست زیر «دانلود» کنید. سپس وارد کنترل پنل هاست خود شوید، ابزار <code>phpMyAdmin</code> را باز کرده و در تب <code>Import</code> فایل دانلود شده را آپلود کنید تا سایت دقیقاً به همین لحظه برگردد.</li>
                                </ul>
                            </div>

                            <div style="margin-bottom: 20px;">
                                <button class="btn-glass ms-create-backup-btn" style="background:var(--ms-primary); font-size:16px; padding:12px 24px;">➕ ایجاد یک نقطه امن جدید (بکاپ)</button>
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
                                        <tr><td colspan="4" style="text-align:center;">هنوز هیچ بکاپی توسط دستیار مدیاصنعت گرفته نشده است.</td></tr>
                                    <?php else: ?>
                                        <?php foreach($ms_backups as $bkp): ?>
                                        <tr>
                                            <td dir="ltr" style="text-align:left; font-size:12px;"><?php echo esc_html($bkp['filename']); ?></td>
                                            <td dir="ltr" style="text-align:left; font-size:13px;"><?php echo esc_html($bkp['date']); ?></td>
                                            <td style="font-weight:bold; color:var(--ms-text-muted);"><?php echo esc_html($bkp['size']); ?></td>
                                            <td>
                                                <a href="<?php echo admin_url('admin-post.php?action=ms_download_backup&file=' . urlencode($bkp['filename'])); ?>" class="btn-glass" style="background:var(--ms-success); padding:5px 10px; font-size:12px; display:inline-block; text-align:center;">⬇️ دانلود</a>
                                                <button class="btn-glass ms-delete-backup-btn" style="background:var(--ms-danger); padding:5px 10px; font-size:12px;" data-file="<?php echo esc_attr($bkp['filename']); ?>">🗑️ حذف</button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- TAB 3: Network & Filter Bypass -->
                <div id="tab-network" class="tab-pane">
                    <div class="glass-card">
                        <div class="card-header">
                            <h2>مدیریت ارتباطات خارجی و تحریم‌ها (بسیار مهم در ایران)</h2>
                        </div>
                        <div class="card-body">
                            <div class="ms-guide-box">
                                <strong>💡 راهنمای ساده: چرا این بخش به سرعت سایت شما کمک می‌کند؟</strong>
                                <p>گاهی اوقات قالب یا افزونه‌های شما تلاش می‌کنند به سایت‌های خارجی (مثل فونت‌های گوگل یا لایسنس‌ها) متصل شوند. چون سرورهای ما در ایران هستند یا سایت مقصد ما را تحریم کرده، این ارتباط برقرار نمی‌شود و سایت شما برای چند ثانیه "گیر" می‌کند و کُند می‌شود.</p>
                                <ul>
                                    <li><strong>چطور مسدود کنم؟</strong> در لیست زیر اگر دامنه‌ای خطای قرمز رنگ داشت، روی "مسدودسازی" کلیک کنید. با این کار سایت شما دیگر منتظر آن دامنه نمی‌ماند و سرعتش چند برابر می‌شود.</li>
                                    <li><strong>نگران خرابی سایت نباشید:</strong> اگر سایتی را مسدود کردید و متوجه شدید بخشی از سایت کار نمی‌کند (مثلا عکسی لود نمی‌شود)، کافیست دوباره به همینجا بیایید و روی "آزادسازی" کلیک کنید تا همه چیز به حالت اول برگردد.</li>
                                </ul>
                            </div>
                            
                            <table class="ms-table" style="margin-top: 20px;">
                                <thead>
                                    <tr>
                                        <th>آدرس سایت خارجی</th>
                                        <th>وضعیت ارتباط</th>
                                        <th>عملیات امن</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($network_logs)): ?>
                                        <tr><td colspan="3" style="text-align:center;">هنوز هیچ ارتباط خارجی ثبت نشده است. کمی در سایت خود کار کنید و سپس این صفحه را رفرش کنید.</td></tr>
                                    <?php else: ?>
                                        <?php foreach($network_logs as $host => $log): 
                                            $is_blocked = in_array($host, $blocked_domains);
                                            $has_error = strpos($log['status'], 'خطا') !== false;
                                        ?>
                                        <tr>
                                            <td dir="ltr" style="text-align:left; font-weight:bold;"><?php echo esc_html($host); ?></td>
                                            <td dir="ltr" style="text-align:left; font-size:13px; color: <?php echo $has_error ? 'var(--ms-danger)' : 'var(--ms-primary)'; ?>;">
                                                <?php echo $has_error ? '❌ مسدود یا فیلتر شده' : '✅ ارتباط موفق'; ?><br>
                                                <span style="font-size:11px; color:#888;"><?php echo esc_html($log['status']); ?></span>
                                            </td>
                                            <td>
                                                <?php if($is_blocked): ?>
                                                    <button class="btn-glass ms-network-action" style="background:var(--ms-success);" data-domain="<?php echo esc_attr($host); ?>" data-type="unblock">🔓 آزادسازی دسترسی</button>
                                                <?php else: ?>
                                                    <button class="btn-glass ms-network-action" style="background:var(--ms-danger);" data-domain="<?php echo esc_attr($host); ?>" data-type="block">🚫 مسدودسازی برای افزایش سرعت</button>
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
                            <p>تصاویر حجیم یکی از اصلی‌ترین دلایل کُندی سایت در موبایل هستند. لیست زیر تصاویری را نشان می‌دهد که بیش از حد سنگین هستند و باید حجم آن‌ها را کم کنید.</p>
                            <?php if( empty($heavy_images) ): ?>
                                <p style="color:var(--ms-success); font-weight:bold;">🎉 عالی! تصویر خیلی سنگینی در سایت شما پیدا نشد.</p>
                            <?php else: ?>
                                <table class="ms-table">
                                    <thead><tr><th>نام عکس</th><th>حجم عکس (باید زیر 0.3 مگابایت باشد)</th><th>لینک</th></tr></thead>
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
                    <div class="glass-card">
                        <div class="card-header"><h2>جزئیات و سلامت دیتابیس (پایگاه داده)</h2></div>
                        <div class="card-body">
                            <div class="ms-guide-box">
                                <strong>دیتابیس چیست و چه ربطی به سرعت دارد؟</strong>
                                <p>دیتابیس قلب سایت شماست که تمام نوشته‌ها، محصولات و تنظیمات در آن ذخیره می‌شود. بخشی از دیتابیس به نام <strong>Autoload</strong> وجود دارد که تنظیمات سایت را در خود نگه می‌دارد. اگر افزونه‌های زیادی نصب و پاک کنید، این بخش پر از زباله می‌شود و سایت شما در هر بار باز شدن کُند می‌شود.</p>
                            </div>
                            <h3 style="margin-top:20px;">حجم فعلی Autoload سایت شما: <span style="color:var(--ms-primary);"><?php echo esc_html( $autoload_size ); ?> مگابایت</span></h3>
                            <p>اگر این عدد بالای ۱ مگابایت است، به یک فرد متخصص یا افزونه‌های پاکسازی دیتابیس نیاز دارید تا اطلاعات باقیمانده از افزونه‌های قدیمی را برایتان حذف کند.</p>
                        </div>
                    </div>
                </div>

            </section>
        </main>
        
        <?php \Mediasanat\PA\Core\SafetyEngine::render_backup_warning_modal(); ?>
    </div>
</div>