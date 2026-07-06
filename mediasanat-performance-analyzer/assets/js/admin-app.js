jQuery(document).ready(function($) {
    let currentAction = '';

    // منطق تب‌ها (Tab Switching)
    $('.nav-tabs li').on('click', function() {
        // حذف کلاس فعال از تمام تب‌ها
        $('.nav-tabs li').removeClass('active');
        $('.tab-pane').removeClass('active');
        
        // اضافه کردن کلاس فعال به تب کلیک شده
        $(this).addClass('active');
        
        // نمایش محتوای مربوطه
        let target = $(this).data('tab');
        $('#tab-' + target).addClass('active');
    });

    // باز کردن مودال تاییدیه پاکسازی ترانزینت‌ها
    $('.btn-glass[data-action="ms_clear_transients"]').on('click', function(e) {
        e.preventDefault();
        currentAction = $(this).data('action');
        let message = $(this).data('msg');
        
        $('#ms-modal-message').text(message);
        $('#ms-safety-modal').fadeIn(200).css('display', 'flex');
    });

    // بستن مودال
    $('#ms-modal-cancel').on('click', function() {
        $('#ms-safety-modal').fadeOut(200);
        currentAction = '';
    });

    // اجرای عملیات امن (مانند پاکسازی ترانزینت)
    $('#ms-modal-confirm').on('click', function() {
        if (!currentAction) return;

        let $btn = $(this);
        let originalText = $btn.text();
        $btn.text('در حال پردازش...').prop('disabled', true);

        $.ajax({
            url: msPaConfig.ajax_url,
            type: 'POST',
            data: {
                action: currentAction,
                security: msPaConfig.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data);
                    location.reload(); 
                } else {
                    alert('خطا: ' + response.data);
                    $btn.text(originalText).prop('disabled', false);
                }
            },
            error: function() {
                alert('خطای ارتباط با سرور.');
                $btn.text(originalText).prop('disabled', false);
            }
        });
    });

    // عملیات مسدود/آزاد سازی دامنه‌های شبکه
    $('.ms-network-action').on('click', function(e) {
        e.preventDefault();
        let $btn = $(this);
        let domain = $btn.data('domain');
        let type = $btn.data('type');
        
        let confirmMsg = type === 'block' 
            ? 'آیا از مسدود کردن ارتباط وردپرس با دامنه [' + domain + '] مطمئن هستید؟ (این کار از هدر رفتن زمان برای سایت‌های فیلترشده جلوگیری می‌کند)' 
            : 'آیا از آزادسازی ارتباط با دامنه [' + domain + '] مطمئن هستید؟';
        
        if(!confirm(confirmMsg)) return;

        let originalText = $btn.text();
        $btn.text('...').prop('disabled', true);

        $.ajax({
            url: msPaConfig.ajax_url,
            type: 'POST',
            data: {
                action: 'ms_toggle_domain_block',
                security: msPaConfig.nonce,
                domain: domain,
                type: type
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data);
                    location.reload(); 
                } else {
                    alert('خطا: ' + response.data);
                    $btn.text(originalText).prop('disabled', false);
                }
            },
            error: function() {
                alert('خطای ارتباط با سرور.');
                $btn.text(originalText).prop('disabled', false);
            }
        });
    });

    // === کدهای مربوط به بکاپ ===

    // ایجاد بکاپ جدید
    $('.ms-create-backup-btn').on('click', function(e) {
        e.preventDefault();
        let $btn = $(this);
        let originalText = $btn.html();
        
        $btn.html('⏳ در حال پردازش و استخراج دیتابیس... (لطفاً منتظر بمانید)').prop('disabled', true).css('opacity', '0.7');

        $.ajax({
            url: msPaConfig.ajax_url,
            type: 'POST',
            data: {
                action: 'ms_create_backup',
                security: msPaConfig.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('✅ ' + response.data);
                    location.reload(); 
                } else {
                    alert('❌ خطا: ' + response.data);
                    $btn.html(originalText).prop('disabled', false).css('opacity', '1');
                }
            },
            error: function() {
                alert('خطای سرور در تهیه بکاپ.');
                $btn.html(originalText).prop('disabled', false).css('opacity', '1');
            }
        });
    });

    // حذف بکاپ
    $('.ms-delete-backup-btn').on('click', function(e) {
        e.preventDefault();
        let $btn = $(this);
        let filename = $btn.data('file');
        
        if(!confirm('آیا از حذف بکاپ [' + filename + '] مطمئن هستید؟ این عملیات غیرقابل بازگشت است.')) return;

        $btn.text('در حال حذف...').prop('disabled', true);

        $.ajax({
            url: msPaConfig.ajax_url,
            type: 'POST',
            data: {
                action: 'ms_delete_backup',
                security: msPaConfig.nonce,
                filename: filename
            },
            success: function(response) {
                if (response.success) {
                    $btn.closest('tr').fadeOut(300, function() { $(this).remove(); });
                } else {
                    alert('❌ خطا: ' + response.data);
                    $btn.text('🗑️ حذف').prop('disabled', false);
                }
            }
        });
    });

    // پاک کردن لیست لاگ ارتباطات شبکه
    $('.ms-clear-logs-btn').on('click', function(e) {
        e.preventDefault();
        if(!confirm('آیا لیست ارتباطات پاک شود؟ (دامنه‌های مسدودشده حذف نمی‌شوند، فقط تاریخچه پاک می‌شود)')) return;

        let $btn = $(this);
        $btn.text('در حال پاکسازی...').prop('disabled', true);

        $.ajax({
            url: msPaConfig.ajax_url,
            type: 'POST',
            data: {
                action: 'ms_clear_network_logs',
                security: msPaConfig.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data);
                    location.reload();
                } else {
                    alert('خطا: ' + response.data);
                    $btn.text('🔄 پاک کردن لیست و شروع نظارت مجدد').prop('disabled', false);
                }
            },
            error: function() {
                alert('خطای ارتباط با سرور.');
                $btn.text('🔄 پاک کردن لیست و شروع نظارت مجدد').prop('disabled', false);
            }
        });
    });

    // تست مجدد سرعت
    $('.ms-retest-btn').on('click', function(e) {
        e.preventDefault();
        let $btn = $(this);
        $btn.text('⏳ در حال تست مجدد سرعت... (چند ثانیه صبر کنید)').prop('disabled', true);
        $.ajax({
            url: msPaConfig.ajax_url,
            type: 'POST',
            data: { action: 'ms_retest_speed', security: msPaConfig.nonce },
            success: function(response) {
                location.reload();
            },
            error: function() {
                alert('خطای ارتباط با سرور.');
                $btn.text('🔄 تست مجدد سرعت').prop('disabled', false);
            }
        });
    });

});