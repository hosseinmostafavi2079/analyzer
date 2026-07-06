<div class="wrap ms-app-wrap" dir="rtl">
    <div class="ms-glass-dashboard">
        
        <header class="app-header">
            <div class="brand">
                <h1>تحلیل‌گر عملکرد مدیاصنعت</h1>
                <span class="version">نسخه 1.0.0 | معماری ایمن (Read-Only)</span>
            </div>
            <div class="global-status safe">
                🛡️ سپر ایمنی پایگاه داده فعال است
            </div>
        </header>

        <main class="app-grid">
            <aside class="app-sidebar glass-panel">
                <ul class="nav-tabs">
                    <li class="active">📊 داشبورد کلان</li>
                    <li>🗄️ تحلیل دیتابیس</li>
                    <li>⚙️ وضعیت سرور</li>
                </ul>
            </aside>

            <section class="app-content">
                <!-- Data Row -->
                <div class="data-grid">
                    <div class="glass-card stat-card">
                        <h3>حجم Autoload</h3>
                        <div class="stat-value <?php echo $autoload_size > 1 ? 'danger' : 'success'; ?>">
                            <?php echo esc_html( $autoload_size ); ?> MB
                        </div>
                    </div>
                    
                    <div class="glass-card stat-card">
                        <h3>حافظه PHP (Memory Limit)</h3>
                        <div class="stat-value normal">
                            <?php echo esc_html( $server_health['memory_limit'] ); ?>
                        </div>
                    </div>

                    <div class="glass-card stat-card action-card">
                        <h3>Transients منقضی شده</h3>
                        <div class="stat-value warning"><?php echo esc_html( $transients ); ?></div>
                        <?php if( $transients > 0 ): ?>
                            <button class="btn-glass" data-action="ms_clear_transients" data-msg="درخواست حذف <?php echo $transients; ?> ترانزینت منقضی شده را دارید.">پاکسازی ایمن</button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- AI Solutions -->
                <div class="glass-card report-card">
                    <div class="card-header">
                        <h2>موتور راهکارهای هوشمند مدیاصنعت</h2>
                    </div>
                    <div class="card-body">
                        <ul class="solutions-list">
                            <?php foreach( $reports as $report ): ?>
                                <li class="report-item <?php echo esc_attr( $report['type'] ); ?>">
                                    <strong><?php echo esc_html( $report['title'] ); ?></strong>
                                    <p><?php echo esc_html( $report['fix'] ); ?></p>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </section>
        </main>
        
        <?php \Mediasanat\PA\Core\SafetyEngine::render_backup_warning_modal(); ?>
    </div>
</div>