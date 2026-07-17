<?php
/** Remove only data created by Mostech Resilience Monitor. */
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;

delete_option( 'ms_resilience_mode' );
delete_option( 'ms_blocked_domains' );
delete_option( 'ms_resilience_allowlist' );
delete_option( 'ms_pa_version' );
delete_option( 'ms_pa_operation_mode' );
delete_option( 'ms_pa_trial_until' );
delete_option( 'ms_pa_domain_rules' );
delete_option( 'ms_pa_policy_migrated_130' );
delete_transient( 'ms_homepage_stats' );
delete_transient( 'ms_ext_req_log' );
delete_transient( 'ms_pa_heavy_images' );
wp_clear_scheduled_hook( 'ms_pa_end_enforcement_trial' );

if ( is_multisite() ) {
    delete_site_option( 'ms_resilience_mode' );
    delete_site_option( 'ms_blocked_domains' );
    delete_site_option( 'ms_resilience_allowlist' );
    delete_site_option( 'ms_pa_version' );
    delete_site_option( 'ms_pa_operation_mode' );
    delete_site_option( 'ms_pa_trial_until' );
    delete_site_option( 'ms_pa_domain_rules' );
    delete_site_option( 'ms_pa_policy_migrated_130' );
}
