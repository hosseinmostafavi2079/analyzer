=== Mostech Resilience Monitor ===
Contributors: hoseinmos
Tags: performance, resilience, external dependencies, iran, monitoring
Requires at least: 5.5
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.2.0
License: GPLv2 or later

Local WordPress performance and external-dependency analysis designed for unstable international connectivity.

== Description ==

The plugin scans the site's own homepage, code references, local media and server settings. It does not call a SaaS API, load a CDN asset, send telemetry, or request an external domain during analysis.

Core features:

* Detect runtime resources and all external URL references in homepage HTML, with clear risk classification.
* Never download detected external resources during a scan.
* Monitor outbound WordPress HTTP API calls locally for 24 hours.
* Optional resilience mode to stop non-allowlisted external server calls and enqueued frontend assets.
* Analyze response time, locally measurable page weight, request count and large images in uploads and active themes.
* Provide prioritized Persian guidance and an in-product beginner academy.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`.
2. Activate it from the WordPress plugins screen.
3. Open «تاب‌آوری سایت» and run a fresh scan.
4. Resolve external dependencies before testing resilience mode on staging.

== Important limitations ==

* The homepage test is a server-side loopback measurement, not a browser Core Web Vitals test.
* Assets injected after JavaScript execution may not appear in the HTML scan; outbound server calls are monitored separately.
* Resilience mode can intentionally stop payment, SMS, maps, social login, license and update APIs. Test critical journeys before production use.
* Re-host third-party assets only when their license permits it.

== Changelog ==

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
