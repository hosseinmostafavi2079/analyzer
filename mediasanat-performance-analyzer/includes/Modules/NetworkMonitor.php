<?php
namespace Mediasanat\PA\Modules;

if ( ! defined( 'ABSPATH' ) ) exit;

class NetworkMonitor {
    const LOG_TRANSIENT = 'ms_ext_req_log';
    const LOG_TTL       = 12 * HOUR_IN_SECONDS;
    const LOG_LIMIT     = 100;

    private $policy;
    private $pending_logs = [];

    public function __construct( PolicyManager $policy = null ) {
        $this->policy = $policy ?: new PolicyManager();
    }

    public function init() {
        add_filter( 'http_request_args', [ $this, 'mark_request_start' ], 10, 2 );
        add_action( 'http_api_debug', [ $this, 'log_external_requests' ], 10, 5 );
        add_filter( 'pre_http_request', [ $this, 'block_external_requests' ], 10, 3 );
        add_filter( 'script_loader_src', [ $this, 'filter_frontend_asset' ], 999 );
        add_filter( 'style_loader_src', [ $this, 'filter_frontend_asset' ], 999 );
        add_action( 'shutdown', [ $this, 'flush_logs' ], 1 );
    }

    public function mark_request_start( $args, $url ) {
        $evaluation = $this->policy->evaluate( $url );
        if ( ! in_array( $evaluation['decision'], [ 'ignore', 'internal' ], true ) ) $args['_ms_start'] = microtime( true );
        return $args;
    }

    public function block_external_requests( $pre, $parsed_args, $url ) {
        if ( false !== $pre ) return $pre;
        $evaluation = $this->policy->evaluate( $url );
        if ( 'block' !== $evaluation['decision'] ) return $pre;

        $this->queue_log( $evaluation['host'], [
            'status'     => 'blocked',
            'is_error'   => true,
            'duration'   => 0,
            'decision'   => 'block',
            'channel'    => 'server',
        ] );

        return new \WP_Error(
            'mostech_resilience_blocked',
            'این ارتباط خارجی طبق سیاست موقت سایت در دسترس نیست.'
        );
    }

    public function log_external_requests( $response, $context, $class, $args, $url ) {
        if ( 'response' !== $context ) return;
        $evaluation = $this->policy->evaluate( $url );
        if ( in_array( $evaluation['decision'], [ 'ignore', 'internal' ], true ) ) return;

        $duration = isset( $args['_ms_start'] ) ? round( max( 0, microtime( true ) - (float) $args['_ms_start'] ), 3 ) : null;
        if ( is_wp_error( $response ) ) {
            $status = 'error:request_failed';
            $is_error = true;
        } else {
            $code = (int) wp_remote_retrieve_response_code( $response );
            $status = $code ? 'http:' . $code : 'http:unknown';
            $is_error = $code >= 400 || 0 === $code;
        }

        $this->queue_log( $evaluation['host'], [
            'status'     => $status,
            'is_error'   => $is_error,
            'duration'   => $duration,
            'decision'   => $evaluation['decision'],
            'channel'    => 'server',
        ] );
    }

    public function filter_frontend_asset( $src ) {
        if ( ! is_string( $src ) || '' === $src ) return $src;
        if ( $this->is_authorized_scan() || is_admin() ) return $src;

        $evaluation = $this->policy->evaluate( $src );
        if ( in_array( $evaluation['decision'], [ 'ignore', 'internal' ], true ) ) return $src;
        $this->queue_log( $evaluation['host'], [
            'status'     => 'frontend_asset',
            'is_error'   => false,
            'duration'   => null,
            'decision'   => $evaluation['decision'],
            'channel'    => 'frontend',
        ] );
        return 'block' === $evaluation['decision'] ? false : $src;
    }

    public function get_logs() {
        $logs = get_transient( self::LOG_TRANSIENT );
        if ( ! is_array( $logs ) ) return [];
        $minimum = time() - self::LOG_TTL;
        $logs = array_filter( $logs, function( $item ) use ( $minimum ) { return (int) ( $item['last_seen'] ?? 0 ) >= $minimum; } );
        uasort( $logs, function( $a, $b ) { return ( $b['last_seen'] ?? 0 ) <=> ( $a['last_seen'] ?? 0 ); } );
        return array_slice( $logs, 0, self::LOG_LIMIT, true );
    }

    public function flush_logs() {
        if ( empty( $this->pending_logs ) ) return;
        $logs = $this->get_logs();
        $changed = false;
        $now = time();
        foreach ( $this->pending_logs as $host => $pending ) {
            $existing = isset( $logs[ $host ] ) ? $logs[ $host ] : [];
            $same_event = $existing
                && ( $existing['status'] ?? '' ) === $pending['status']
                && ( $existing['decision'] ?? '' ) === $pending['decision']
                && ( $existing['channel'] ?? '' ) === $pending['channel'];
            if ( $same_event && (int) ( $existing['last_seen'] ?? 0 ) > $now - 60 ) continue;
            $category = $pending['category'];
            $logs[ $host ] = [
                'domain'      => $host,
                'origin'      => 'https://' . $host,
                'status'      => $pending['status'],
                'is_error'    => $pending['is_error'],
                'duration'    => $pending['duration'],
                'count'       => (int) ( $existing['count'] ?? 0 ) + $pending['count'],
                'first_seen'  => (int) ( $existing['first_seen'] ?? $now ),
                'last_seen'   => $now,
                'decision'    => $pending['decision'],
                'channel'     => $pending['channel'],
                'category'    => $category,
            ];
            $changed = true;
        }
        $this->pending_logs = [];
        if ( ! $changed ) return;
        uasort( $logs, function( $a, $b ) { return ( $b['last_seen'] ?? 0 ) <=> ( $a['last_seen'] ?? 0 ); } );
        $logs = array_slice( $logs, 0, self::LOG_LIMIT, true );
        set_transient( self::LOG_TRANSIENT, $logs, self::LOG_TTL );
    }

    public function clear_logs() {
        $this->pending_logs = [];
        delete_transient( self::LOG_TRANSIENT );
    }

    public function get_blocked_domains() {
        $domains = [];
        foreach ( $this->policy->get_rules() as $domain => $rule ) if ( 'block' === ( $rule['list'] ?? '' ) ) $domains[] = $domain;
        return $domains;
    }

    public function is_resilience_mode() {
        return 'enforce' === $this->policy->get_mode_state()['mode'];
    }

    private function queue_log( $host, $data ) {
        $host = $this->policy->normalize_domain( $host );
        if ( ! $host || $this->policy->is_internal_domain( $host ) ) return;
        $rule = $this->policy->get_rule( $host );
        $category = $rule['category'] ?? $this->policy->auto_category( $host );
        if ( isset( $this->pending_logs[ $host ] ) ) {
            $this->pending_logs[ $host ]['count']++;
            $this->pending_logs[ $host ]['status'] = $data['status'];
            $this->pending_logs[ $host ]['decision'] = $data['decision'];
            if ( null !== $data['duration'] ) $this->pending_logs[ $host ]['duration'] = $data['duration'];
            return;
        }
        $this->pending_logs[ $host ] = array_merge( $data, [ 'count' => 1, 'category' => $category ] );
    }

    private function is_authorized_scan() {
        $scan_nonce = isset( $_GET['_ms_nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_ms_nonce'] ) ) : '';
        if ( isset( $_GET['ms_pa_scan'] ) && $scan_nonce && current_user_can( 'manage_options' ) && wp_verify_nonce( $scan_nonce, 'ms_pa_frontend_scan' ) ) return true;
        $scan_token = isset( $_GET['ms_pa_token'] ) ? sanitize_key( wp_unslash( $_GET['ms_pa_token'] ) ) : '';
        return $scan_token && get_transient( 'ms_pa_scan_' . $scan_token );
    }
}
