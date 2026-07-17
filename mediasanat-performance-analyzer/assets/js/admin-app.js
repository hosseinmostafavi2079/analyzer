(function () {
    'use strict';

    function qs(selector, context) { return (context || document).querySelector(selector); }
    function qsa(selector, context) { return Array.prototype.slice.call((context || document).querySelectorAll(selector)); }

    function toast(message, type) {
        var el = qs('#ms-toast');
        if (!el) return;
        el.textContent = message;
        el.className = 'ms-toast show ' + (type || 'success');
        window.setTimeout(function () { el.className = 'ms-toast'; }, 4200);
    }

    function post(action, data) {
        var payload = new URLSearchParams(Object.assign({ action: action, security: msPaConfig.nonce }, data || {}));
        return fetch(msPaConfig.ajax_url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: payload.toString()
        }).then(function (response) {
            if (!response.ok) throw new Error('پاسخ نامعتبر سرور');
            return response.json();
        }).then(function (response) {
            if (!response.success) throw new Error(typeof response.data === 'string' ? response.data : 'عملیات انجام نشد');
            return response.data;
        });
    }

    function postForm(action, data) {
        var form = new FormData();
        form.append('action', action);
        form.append('security', msPaConfig.nonce);
        Object.keys(data || {}).forEach(function (key) { form.append(key, data[key]); });
        return fetch(msPaConfig.ajax_url, { method: 'POST', credentials: 'same-origin', body: form })
            .then(function (response) { if (!response.ok) throw new Error('پاسخ نامعتبر سرور'); return response.json(); })
            .then(function (response) { if (!response.success) throw new Error(typeof response.data === 'string' ? response.data : 'عملیات انجام نشد'); return response.data; });
    }

    function browserScan() {
        var url = new URL(msPaConfig.home_url, window.location.href);
        url.searchParams.set('ms_pa_scan', String(Date.now()));
        url.searchParams.set('_ms_nonce', msPaConfig.scan_nonce);
        var started = performance.now();
        return fetch(url.toString(), { credentials: 'same-origin', cache: 'no-store' }).then(function (response) {
            if (!response.ok) throw new Error('صفحه اصلی کد ' + response.status + ' برگرداند');
            var duration = Math.max(0.01, (performance.now() - started) / 1000);
            return response.text().then(function (html) {
                if (html.length > 5 * 1024 * 1024) throw new Error('حجم HTML بیشتر از سقف ۵ مگابایت است');
                return postForm('ms_analyze_browser_html', { html: html, duration: duration.toFixed(3) });
            });
        });
    }

    function openTab(name) {
        qsa('.ms-nav button').forEach(function (button) { button.classList.toggle('active', button.dataset.tab === name); });
        qsa('.tab-pane').forEach(function (pane) { pane.classList.toggle('active', pane.id === 'tab-' + name); });
        if (window.history && history.replaceState) history.replaceState(null, '', '#'+ name);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    qsa('.ms-nav button').forEach(function (button) { button.addEventListener('click', function () { openTab(button.dataset.tab); }); });
    qsa('[data-open-tab]').forEach(function (button) { button.addEventListener('click', function () { openTab(button.dataset.openTab); }); });
    if (window.location.hash && qs('[data-tab="' + window.location.hash.slice(1) + '"]')) openTab(window.location.hash.slice(1));

    qsa('.ms-network-action').forEach(function (button) {
        button.addEventListener('click', function () {
            var type = button.dataset.type;
            var message = type === 'block' ? 'تماس‌های سروری با «' + button.dataset.domain + '» مسدود شود؟ ممکن است بخشی از سایت از کار بیفتد.' : 'این دامنه دوباره آزاد شود؟';
            if (!window.confirm(message)) return;
            button.disabled = true;
            post('ms_toggle_domain_block', { domain: button.dataset.domain, type: type }).then(function (result) { toast(result); window.setTimeout(function () { location.reload(); }, 700); })
                .catch(function (error) { toast(error.message, 'danger'); button.disabled = false; });
        });
    });

    var resilience = qs('#ms-resilience-toggle');
    if (resilience) resilience.addEventListener('change', function () {
        var enabled = resilience.checked;
        if (enabled && !window.confirm('حالت تاب‌آوری می‌تواند APIهای پرداخت، پیامک، نقشه و سرویس‌های خارجی را متوقف کند. فعال شود؟')) { resilience.checked = false; return; }
        resilience.disabled = true;
        post('ms_toggle_resilience', { enabled: enabled ? '1' : '0' }).then(function (result) { toast(result); window.setTimeout(function () { location.reload(); }, 900); })
            .catch(function (error) { toast(error.message, 'danger'); resilience.checked = !enabled; resilience.disabled = false; });
    });

    qsa('.ms-clear-logs-btn').forEach(function (button) { button.addEventListener('click', function () { if (!window.confirm('تاریخچه تماس‌های شبکه پاک شود؟')) return; button.disabled = true; post('ms_clear_network_logs').then(function (result) { toast(result); window.setTimeout(function () { location.reload(); }, 700); }).catch(function (error) { toast(error.message, 'danger'); button.disabled = false; }); }); });
    qsa('.ms-retest-btn').forEach(function (button) {
        button.addEventListener('click', function () {
            button.disabled = true;
            button.textContent = 'در حال اسکن مرورگر…';
            browserScan().then(function () { location.reload(); }).catch(function () {
                button.textContent = 'در حال اسکن سرور…';
                return post('ms_retest_speed').then(function (stats) {
                    if (!stats || stats.status !== 'success') throw new Error((stats && stats.message) || 'اسکن سرور هم انجام نشد');
                    location.reload();
                });
            }).catch(function (error) {
                toast(error.message, 'danger');
                button.disabled = false;
                button.textContent = 'اسکن دوباره';
            });
        });
    });
}());
