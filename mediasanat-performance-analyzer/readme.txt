=== Mostech Resilience Monitor ===
Contributors: hoseinmos
Tags: performance, resilience, external dependencies, iran, monitoring
Requires at least: 5.5
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.4.0
License: GPLv2 or later

Local WordPress performance and external-dependency analysis designed for unstable international connectivity.

== Description ==

The plugin scans the site's own homepage, code references, local media and server settings. It does not call a SaaS API, load a CDN asset, send telemetry, or request an external domain during analysis.

Core features:

* Detect runtime resources and all external URL references in homepage HTML, with clear risk classification.
* Never download detected external resources during a scan.
* Three explicit modes: monitor, simulate, and time-limited enforcement.
* Per-domain Allowlist and Blocklist with categories and impact guidance.
* Complete domain cards with observation source, current rule and decision, last seen time, count, impact and sensitive-service warnings.
* Monitor outbound WordPress HTTP API calls and enqueued external assets in aggregated 12-hour logs.
* Block only manager-selected Blocklist domains during confirmed enforcement trials.
* Analyze response time, locally measurable page weight, request count and large images in uploads and active themes.
* Provide prioritized Persian guidance and an in-product beginner academy.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`.
2. Activate it from the WordPress plugins screen.
3. Open «تاب‌آوری سایت» and run a fresh scan.
4. Build Allowlist and Blocklist rules, test simulation, then use a short enforcement trial.

== Important limitations ==

* The homepage test uses a manager-triggered browser fetch with a server loopback fallback; it is not a Core Web Vitals test.
* Assets injected after JavaScript execution may not appear in the HTML scan; outbound server calls are monitored separately.
* Enforcement can intentionally stop payment, SMS, maps, social login, license and update APIs. Test critical journeys before production use.
* Re-host third-party assets only when their license permits it.
* Hard-coded browser requests that bypass WordPress enqueue APIs can be detected by page analysis but cannot always be blocked by WordPress filters.

== Privacy and emergency stop ==

No telemetry is sent. Logs contain only the domain, aggregate count, status code, duration, channel and policy decision. URL paths, query strings, cookies, tokens, API keys, email addresses, phone numbers and order data are not stored. Logs expire after 12 hours and can be cleared manually.

To stop enforcement immediately, add this to `wp-config.php`:

`define( 'MOSTECH_RESILIENCE_EMERGENCY_OFF', true );`

== Changelog ==

= 1.4.0 =
* Hardened monitor, simulation and enforcement decision boundaries and replaced public block details with a generic message.
* Expanded protection for site, network-admin, REST, AJAX, cron and loopback destinations.
* Added login and captcha categories plus explicit warnings for payment, SMS, login, captcha, license and unknown services.
* Completed domain cards with observation type, rule, current decision, impact, last seen time and aggregate count.
* Added the required automatic-category accuracy notice and manual correction guidance.
* Rebuilt the Persian RTL academy as a ten-section beginner guide with examples, checklists, emergency recovery and glossary.
* Added scan timestamps while preserving manager-triggered cached analysis.

= 1.3.0 =
* Added monitor, simulation and confirmed time-limited enforcement modes.
* Added categorized Allowlist and Blocklist management with search and filters.
* Added 5, 15, 30 and 60-minute enforcement trials with automatic expiry.
* Added site-domain protection and an emergency wp-config.php constant.
* Reworked logs to store only aggregated, query-free, path-free metadata for 12 hours.
* Made homepage and media scans manager-triggered and cached their results.
* Added migration from legacy block and allow options into safe monitor mode.

= 1.2.0 =
* Renamed the product to Mostech Resilience Monitor.
* Added browser fallback scanning for hosts that block WordPress loopback requests.
* Added comprehensive URL reference discovery with runtime/reference classification.
* Combined page dependencies with observed WordPress HTTP API domains.
* Expanded large-image scanning to uploads, the active theme and parent theme.
* Removed the unrelated database section.

= 1.1.0 =
* Rebuilt the product around international-connectivity resilience.
* Added a focused professional dashboard and beginner academy.
* Added external dependency inventory and safe local size analysis.
* Added resilience controls and local outbound request monitoring.
* Removed unrelated backup and security scanner product areas.
* Added activation defaults and clean uninstall behavior.
