<?php
namespace Mediasanat\PA\Modules;

if ( ! defined( 'ABSPATH' ) ) exit;

class PolicyManager {
    const OPTION_MODE        = 'ms_pa_operation_mode';
    const OPTION_TRIAL_UNTIL = 'ms_pa_trial_until';
    const OPTION_RULES       = 'ms_pa_domain_rules';
    const CRON_HOOK          = 'ms_pa_end_enforcement_trial';

    public function __construct() {
        $this->maybe_migrate_legacy_settings();
    }

    public function init() {
        add_action( self::CRON_HOOK, [ $this, 'expire_trial' ] );
    }

    public function get_mode_state() {
        $emergency = $this->is_emergency_off();
        $mode = get_option( self::OPTION_MODE, 'monitor' );
        if ( ! in_array( $mode, [ 'monitor', 'simulate', 'enforce' ], true ) ) $mode = 'monitor';
        $until = (int) get_option( self::OPTION_TRIAL_UNTIL, 0 );

        if ( 'enforce' === $mode && ( ! $until || $until <= time() ) ) {
            $this->expire_trial();
            $mode = 'monitor';
            $until = 0;
        }
        if ( $emergency ) {
            if ( 'enforce' === $mode ) $this->expire_trial();
            $mode = 'monitor';
            $until = 0;
        }

        return [
            'mode'          => $mode,
            'trial_until'   => $until,
            'remaining'     => $until > time() ? $until - time() : 0,
            'emergency_off' => $emergency,
        ];
    }

    public function set_mode( $mode, $duration = 0, $confirmed = false ) {
        if ( ! in_array( $mode, [ 'monitor', 'simulate', 'enforce' ], true ) ) {
            return new \WP_Error( 'invalid_mode', 'حالت اجرایی نامعتبر است.' );
        }
        if ( 'enforce' === $mode ) {
            if ( $this->is_emergency_off() ) return new \WP_Error( 'emergency_off', 'خاموش‌کن اضطراری در wp-config.php فعال است.' );
            if ( ! $confirmed ) return new \WP_Error( 'confirmation_required', 'تأیید صریح مدیر برای حالت اجرایی لازم است.' );
            $allowed_durations = [ 5, 15, 30, 60 ];
            $duration = (int) $duration;
            if ( ! in_array( $duration, $allowed_durations, true ) ) return new \WP_Error( 'invalid_duration', 'مدت آزمایش باید ۵، ۱۵، ۳۰ یا ۶۰ دقیقه باشد.' );
            $until = time() + ( $duration * MINUTE_IN_SECONDS );
            update_option( self::OPTION_TRIAL_UNTIL, $until, false );
            wp_clear_scheduled_hook( self::CRON_HOOK );
            wp_schedule_single_event( $until, self::CRON_HOOK );
        } else {
            delete_option( self::OPTION_TRIAL_UNTIL );
            wp_clear_scheduled_hook( self::CRON_HOOK );
        }
        update_option( self::OPTION_MODE, $mode, false );
        return $this->get_mode_state();
    }

    public function expire_trial() {
        update_option( self::OPTION_MODE, 'monitor', false );
        delete_option( self::OPTION_TRIAL_UNTIL );
        wp_clear_scheduled_hook( self::CRON_HOOK );
    }

    public function get_rules() {
        $rules = get_option( self::OPTION_RULES, [] );
        return is_array( $rules ) ? $rules : [];
    }

    public function get_rule( $domain ) {
        $domain = $this->normalize_domain( $domain );
        $rules = $this->get_rules();
        return $domain && isset( $rules[ $domain ] ) ? $rules[ $domain ] : null;
    }

    public function set_rule( $domain, $list, $category ) {
        $domain = $this->normalize_domain( $domain );
        if ( ! $domain ) return new \WP_Error( 'invalid_domain', 'دامنه واردشده معتبر نیست.' );
        if ( ! in_array( $list, [ 'allow', 'block' ], true ) ) return new \WP_Error( 'invalid_list', 'نوع فهرست معتبر نیست.' );
        $categories = $this->get_categories();
        if ( ! isset( $categories[ $category ] ) ) return new \WP_Error( 'invalid_category', 'دسته‌بندی دامنه معتبر نیست.' );
        if ( 'block' === $list && $this->is_internal_domain( $domain ) ) {
            return new \WP_Error( 'internal_domain', 'دامنه خود سایت یا درخواست داخلی وردپرس قابل مسدودسازی نیست.' );
        }
        $rules = $this->get_rules();
        $rules[ $domain ] = [
            'domain'     => $domain,
            'list'       => $list,
            'category'   => $category,
            'updated_at' => time(),
        ];
        ksort( $rules );
        update_option( self::OPTION_RULES, $rules, false );
        return $rules[ $domain ];
    }

    public function delete_rule( $domain ) {
        $domain = $this->normalize_domain( $domain );
        if ( ! $domain ) return new \WP_Error( 'invalid_domain', 'دامنه واردشده معتبر نیست.' );
        $rules = $this->get_rules();
        unset( $rules[ $domain ] );
        update_option( self::OPTION_RULES, $rules, false );
        return true;
    }

    public function evaluate( $url_or_domain ) {
        $host = $this->normalize_domain( $url_or_domain );
        $state = $this->get_mode_state();
        if ( ! $host ) return [ 'host' => '', 'decision' => 'ignore', 'mode' => $state['mode'], 'rule' => null ];
        if ( $this->is_internal_domain( $host ) ) return [ 'host' => $host, 'decision' => 'internal', 'mode' => $state['mode'], 'rule' => null ];

        $rule = $this->get_rule( $host );
        if ( $rule && 'allow' === $rule['list'] ) return [ 'host' => $host, 'decision' => 'allow', 'mode' => $state['mode'], 'rule' => $rule ];
        if ( $rule && 'block' === $rule['list'] ) {
            if ( 'enforce' === $state['mode'] ) return [ 'host' => $host, 'decision' => 'block', 'mode' => $state['mode'], 'rule' => $rule ];
            if ( 'simulate' === $state['mode'] ) return [ 'host' => $host, 'decision' => 'would_block', 'mode' => $state['mode'], 'rule' => $rule ];
        }
        return [ 'host' => $host, 'decision' => 'observe', 'mode' => $state['mode'], 'rule' => $rule ];
    }

    public function is_internal_domain( $domain ) {
        $domain = $this->normalize_domain( $domain );
        if ( ! $domain ) return true;
        if ( in_array( $domain, [ 'localhost', '127.0.0.1', '::1' ], true ) ) return true;
        foreach ( $this->get_internal_domains() as $internal ) {
            if ( $domain === $internal ) return true;
        }
        return false;
    }

    public function normalize_domain( $value ) {
        $value = strtolower( trim( (string) $value ) );
        if ( ! $value ) return '';
        if ( false === strpos( $value, '://' ) ) $value = 'https://' . ltrim( $value, '/' );
        $host = wp_parse_url( $value, PHP_URL_HOST );
        if ( ! $host ) return '';
        $host = strtolower( rtrim( $host, '.' ) );
        if ( function_exists( 'idn_to_ascii' ) ) {
            $ascii = idn_to_ascii( $host );
            if ( $ascii ) $host = strtolower( $ascii );
        }
        if ( false === strpos( $host, '.' ) ) return '';
        return preg_match( '/^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9\-]{0,61}[a-z0-9])?\.)*[a-z0-9](?:[a-z0-9\-]{0,61}[a-z0-9])?$/', $host ) ? $host : '';
    }

    public function get_categories() {
        return [
            'payment'   => [ 'label' => 'پرداخت', 'impact' => 'قطع این دامنه می‌تواند پرداخت، بازگشت از درگاه یا تأیید تراکنش را مختل کند.', 'warning' => 'پیش از Blocklist، خرید و بازگشت از درگاه را روی سایت آزمایشی تست کنید.' ],
            'sms'       => [ 'label' => 'پیامک', 'impact' => 'قطع این دامنه می‌تواند ارسال کد ورود، اعلان یا پیامک سفارش را متوقف کند.', 'warning' => 'ارسال کد ورود و پیامک سفارش را قبل از مسدودسازی تست کنید.' ],
            'login'     => [ 'label' => 'ورود', 'impact' => 'قطع این دامنه ممکن است ورود اجتماعی، ورود یک‌بارمصرف یا احراز هویت را متوقف کند.', 'warning' => 'صفحه ورود و بازیابی دسترسی مدیر را در یک تب جدا آزمایش کنید.' ],
            'captcha'   => [ 'label' => 'کپچا', 'impact' => 'قطع این دامنه می‌تواند اعتبارسنجی ضدربات فرم ورود، تماس یا خرید را از کار بیندازد.', 'warning' => 'تمام فرم‌های دارای کپچا را قبل از Blocklist ارسال و بررسی کنید.' ],
            'font'      => [ 'label' => 'فونت', 'impact' => 'قطع این دامنه ممکن است باعث تأخیر یا نمایش فونت جایگزین شود.', 'warning' => '' ],
            'cdn'       => [ 'label' => 'CDN', 'impact' => 'قطع این دامنه ممکن است فایل‌های CSS، JavaScript یا تصویر را از دسترس خارج کند.', 'warning' => '' ],
            'map'       => [ 'label' => 'نقشه', 'impact' => 'قطع این دامنه می‌تواند نقشه، انتخاب موقعیت یا محاسبه مسیر را غیرفعال کند.', 'warning' => '' ],
            'analytics' => [ 'label' => 'آمار', 'impact' => 'قطع این دامنه معمولاً ثبت آمار و رفتار کاربر را متوقف می‌کند، نه عملکرد اصلی سایت را.', 'warning' => '' ],
            'license'   => [ 'label' => 'لایسنس', 'impact' => 'قطع این دامنه می‌تواند بررسی لایسنس، به‌روزرسانی یا دریافت اطلاعات محصول را متوقف کند.', 'warning' => 'به‌روزرسانی و اعتبار لایسنس قالب یا افزونه را پس از شبیه‌سازی بررسی کنید.' ],
            'unknown'   => [ 'label' => 'ناشناخته', 'impact' => 'اثر قطع این دامنه مشخص نیست؛ قبل از مسدودسازی در حالت شبیه‌سازی بررسی شود.', 'warning' => 'تا زمانی که کاربرد دامنه مشخص نشده، آن را وارد Blocklist نکنید.' ],
        ];
    }

    public function auto_category( $domain, $types = [] ) {
        $haystack = strtolower( $domain . ' ' . implode( ' ', (array) $types ) );
        $patterns = [
            'payment'   => 'pay|payment|zarinpal|idpay|behpardakht|sadad|asanpardakht|پرداخت',
            'sms'       => 'sms|kavenegar|melipayamak|ippanel|faraz|پیامک',
            'login'     => 'login|auth|oauth|openid|ورود|احراز',
            'captcha'   => 'captcha|recaptcha|hcaptcha|turnstile|کپچا',
            'font'      => 'font|typekit|gstatic|googleapis|فونت',
            'cdn'       => 'cdn|cloudflare|jsdelivr|unpkg|cdnjs',
            'map'       => 'map|neshan|balad|mapbox|نقشه',
            'analytics' => 'analytics|metric|stat|clarity|hotjar|tagmanager',
            'license'   => 'license|licence|update|wordpress\.org|elementor',
        ];
        foreach ( $patterns as $category => $pattern ) if ( preg_match( '/(?:' . $pattern . ')/i', $haystack ) ) return $category;
        return 'unknown';
    }

    public function get_category_data( $category ) {
        $categories = $this->get_categories();
        return $categories[ $category ] ?? $categories['unknown'];
    }

    public function is_emergency_off() {
        return defined( 'MOSTECH_RESILIENCE_EMERGENCY_OFF' ) && true === MOSTECH_RESILIENCE_EMERGENCY_OFF;
    }

    private function get_internal_domains() {
        $urls = [ home_url( '/' ), site_url( '/' ), admin_url( '/' ), admin_url( 'admin-ajax.php' ), site_url( 'wp-cron.php' ), home_url( 'wp-json/' ) ];
        if ( function_exists( 'network_home_url' ) ) $urls[] = network_home_url( '/' );
        if ( function_exists( 'network_admin_url' ) ) $urls[] = network_admin_url( '/' );
        if ( function_exists( 'rest_url' ) ) $urls[] = rest_url( '/' );
        $domains = [];
        foreach ( $urls as $url ) {
            $host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
            if ( ! $host ) continue;
            $domains[ $host ] = true;
            $base = 0 === strpos( $host, 'www.' ) ? substr( $host, 4 ) : $host;
            $domains[ $base ] = true;
            $domains[ 'www.' . $base ] = true;
        }
        return array_keys( $domains );
    }

    private function maybe_migrate_legacy_settings() {
        if ( get_option( 'ms_pa_policy_migrated_130' ) ) return;
        $rules = $this->get_rules();
        foreach ( (array) get_option( 'ms_blocked_domains', [] ) as $domain ) {
            $domain = $this->normalize_domain( $domain );
            if ( $domain && ! $this->is_internal_domain( $domain ) ) $rules[ $domain ] = [ 'domain' => $domain, 'list' => 'block', 'category' => $this->auto_category( $domain ), 'updated_at' => time() ];
        }
        foreach ( (array) get_option( 'ms_resilience_allowlist', [] ) as $domain ) {
            $domain = $this->normalize_domain( $domain );
            if ( $domain ) $rules[ $domain ] = [ 'domain' => $domain, 'list' => 'allow', 'category' => $this->auto_category( $domain ), 'updated_at' => time() ];
        }
        update_option( self::OPTION_RULES, $rules, false );
        update_option( self::OPTION_MODE, 'monitor', false );
        update_option( 'ms_resilience_mode', false, false );
        add_option( 'ms_pa_policy_migrated_130', 1, '', false );
    }
}
