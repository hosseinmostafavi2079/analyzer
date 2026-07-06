jQuery(document).ready(function($) {
    let currentAction = '';

    // باز کردن مودال تاییدیه بکاپ
    $('.btn-glass').on('click', function(e) {
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

    // اجرای عملیات امن در صورت تایید
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
                    alert(response.data); // نمایش پیام موفقیت
                    location.reload(); // رفرش برای به‌روزرسانی دیتا
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
});