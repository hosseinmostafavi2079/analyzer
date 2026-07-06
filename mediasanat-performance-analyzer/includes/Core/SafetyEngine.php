<?php
namespace Mediasanat\PA\Core;

if ( ! defined( 'ABSPATH' ) ) exit;

class SafetyEngine {
    
    public static function verify_ajax_request( $nonce_action, $nonce_name = 'security' ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'شما مجوز دسترسی به این بخش را ندارید.' );
        }
        if ( ! isset( $_POST[$nonce_name] ) || ! wp_verify_nonce( $_POST[$nonce_name], $nonce_action ) ) {
            wp_send_json_error( 'خطای امنیتی: درخواست نامعتبر است.' );
        }
        return true;
    }

    public static function render_backup_warning_modal() {
        ?>
        <div id="ms-safety-modal" class="mediasanat-modal" style="display:none;">
            <div class="mediasanat-modal-content">
                <div class="modal-header">
                    <span class="modal-icon">⚠️</span>
                    <h3>هشدار ایمنی پایگاه داده</h3>
                </div>
                <div class="modal-body">
                    <p id="ms-modal-message">آیا از انجام این عملیات مطمئن هستید؟</p>
                    <p class="critical-text">تایید کنید که قبل از انجام این عملیات، <strong>بکاپ کامل از دیتابیس</strong> تهیه کرده‌اید. سیستم هیچ‌گونه تغییری را به صورت خودکار بازگردانی نمی‌کند.</p>
                </div>
                <div class="modal-footer">
                    <button class="btn-cancel" id="ms-modal-cancel">لغو عملیات</button>
                    <button class="btn-danger" id="ms-modal-confirm">بله، بکاپ دارم (اجرا)</button>
                </div>
            </div>
        </div>
        <?php
    }
}