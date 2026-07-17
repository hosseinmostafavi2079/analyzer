<?php
namespace Mediasanat\PA\Modules;

if ( ! defined( 'ABSPATH' ) ) exit;

class StatusMapper {
    public static function decision( $value, $rule_list = '' ) {
        $labels = [
            'ignore'      => 'خارج از دامنه پایش',
            'internal'    => 'درخواست داخلی و مجاز',
            'observe'     => 'تحت پایش و بدون مسدودسازی',
            'allow'       => 'مجاز به‌دلیل فهرست مجاز',
            'would_block' => 'در حالت اجرایی مسدود خواهد شد',
            'block'       => 'طبق قانون واقعاً مسدود شد',
        ];
        if ( 'observe' === $value && 'block' === $rule_list ) return 'در فهرست مسدود؛ حالت فعلی فقط پایش است';
        return $labels[ $value ] ?? 'وضعیت تصمیم نامشخص است';
    }

    public static function request_status( $value ) {
        $value = (string) $value;
        if ( 'error:request_failed' === $value ) return 'خطا در برقراری ارتباط';
        if ( 'blocked' === $value ) return 'درخواست طبق قانون مسدود شد';
        if ( 'frontend_asset' === $value ) return 'فایل سمت کاربر مشاهده شد';
        if ( 'http:unknown' === $value ) return 'پاسخ بدون کد وضعیت';
        if ( preg_match( '/^http:(\d{3})$/', $value, $match ) ) {
            $code = (int) $match[1];
            if ( $code >= 200 && $code < 300 ) return 'پاسخ موفق سرور (' . $code . ')';
            if ( $code >= 300 && $code < 400 ) return 'تغییر مسیر سرور (' . $code . ')';
            if ( $code >= 400 && $code < 500 ) return 'خطای درخواست (' . $code . ')';
            if ( $code >= 500 ) return 'خطای سرور مقصد (' . $code . ')';
        }
        if ( 'ثبت HTML' === $value || '—' === $value || '' === $value ) return 'مشاهده در تحلیل HTML';
        return 'وضعیت ثبت‌شده';
    }

    public static function mode( $value ) {
        $labels = [ 'monitor' => 'پایش', 'simulate' => 'شبیه‌سازی', 'enforce' => 'اجرایی' ];
        return $labels[ $value ] ?? 'پایش';
    }
}
