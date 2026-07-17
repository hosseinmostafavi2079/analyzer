<?php
namespace Mediasanat\PA\Core;

if ( ! defined( 'ABSPATH' ) ) exit;

class SafetyEngine {
    
    public static function verify_ajax_request( $nonce_action, $nonce_name = 'security' ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'شما مجوز دسترسی به این بخش را ندارید.' );
        }
        $nonce = isset( $_POST[ $nonce_name ] ) ? sanitize_text_field( wp_unslash( $_POST[ $nonce_name ] ) ) : '';
        if ( ! $nonce || ! wp_verify_nonce( $nonce, $nonce_action ) ) {
            wp_send_json_error( 'خطای امنیتی: درخواست نامعتبر است.' );
        }
        return true;
    }
}
