(function () {
    'use strict';
    function qs(selector, context) { return (context || document).querySelector(selector); }
    function qsa(selector, context) { return Array.prototype.slice.call((context || document).querySelectorAll(selector)); }
    function toast(message, type) { var el = qs('#ms-toast'); if (!el) return; el.textContent = message; el.className = 'ms-toast show ' + (type || 'success'); window.setTimeout(function () { el.className = 'ms-toast'; }, 4200); }
    function post(action, data) {
        var payload = new URLSearchParams(Object.assign({ action: action, security: msPaConfig.nonce }, data || {}));
        return fetch(msPaConfig.ajax_url, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' }, body: payload.toString() })
            .then(function (response) { if (!response.ok) throw new Error('پاسخ نامعتبر سرور'); return response.json(); })
            .then(function (response) { if (!response.success) throw new Error(typeof response.data === 'string' ? response.data : 'عملیات انجام نشد'); return response.data; });
    }
    function postForm(action, data) {
        var form = new FormData(); form.append('action', action); form.append('security', msPaConfig.nonce);
        Object.keys(data || {}).forEach(function (key) { form.append(key, data[key]); });
        return fetch(msPaConfig.ajax_url, { method: 'POST', credentials: 'same-origin', body: form })
            .then(function (response) { if (!response.ok) throw new Error('پاسخ نامعتبر سرور'); return response.json(); })
            .then(function (response) { if (!response.success) throw new Error(typeof response.data === 'string' ? response.data : 'عملیات انجام نشد'); return response.data; });
    }
    function setBusy(button, text) { if (!button) return; button.dataset.originalText = button.textContent; button.textContent = text; button.disabled = true; }
    function clearBusy(button) { if (!button) return; button.textContent = button.dataset.originalText || button.textContent; button.disabled = false; }
    function openTab(name) { qsa('.ms-nav button').forEach(function (button) { button.classList.toggle('active', button.dataset.tab === name); }); qsa('.tab-pane').forEach(function (pane) { pane.classList.toggle('active', pane.id === 'tab-' + name); }); if (history.replaceState) history.replaceState(null, '', '#' + name); window.scrollTo({ top: 0, behavior: 'smooth' }); }

    qsa('.ms-nav button').forEach(function (button) { button.addEventListener('click', function () { openTab(button.dataset.tab); }); });
    qsa('[data-open-tab]').forEach(function (button) { button.addEventListener('click', function () { openTab(button.dataset.openTab); }); });
    if (location.hash && qs('[data-tab="' + location.hash.slice(1) + '"]')) openTab(location.hash.slice(1));

    function browserScan() {
        var url = new URL(msPaConfig.home_url, location.href); url.searchParams.set('ms_pa_scan', String(Date.now())); url.searchParams.set('_ms_nonce', msPaConfig.scan_nonce);
        var started = performance.now();
        return fetch(url.toString(), { credentials: 'same-origin', cache: 'no-store' }).then(function (response) {
            if (!response.ok) throw new Error('صفحه اصلی کد ' + response.status + ' برگرداند');
            var duration = Math.max(0.01, (performance.now() - started) / 1000);
            return response.text().then(function (html) { if (html.length > 5 * 1024 * 1024) throw new Error('حجم HTML بیشتر از سقف ۵ مگابایت است'); return postForm('ms_analyze_browser_html', { html: html, duration: duration.toFixed(3) }); });
        });
    }
    qsa('.ms-retest-btn').forEach(function (button) { button.addEventListener('click', function () { setBusy(button, 'در حال اسکن مرورگر…'); browserScan().then(function () { location.reload(); }).catch(function () { button.textContent = 'در حال اسکن سرور…'; return post('ms_retest_speed').then(function (stats) { if (!stats || stats.status !== 'success') throw new Error((stats && stats.message) || 'اسکن سرور انجام نشد'); location.reload(); }); }).catch(function (error) { toast(error.message, 'danger'); clearBusy(button); }); }); });
    qsa('.ms-scan-images-btn').forEach(function (button) { button.addEventListener('click', function () { setBusy(button, 'در حال بررسی فایل‌ها…'); post('ms_scan_heavy_images').then(function () { location.hash = 'assets'; location.reload(); }).catch(function (error) { toast(error.message, 'danger'); clearBusy(button); }); }); });

    var enforceConfirm = qs('#ms-enforce-confirm');
    var enforceButton = qs('#ms-enforce-button');
    if (enforceConfirm && enforceButton) enforceConfirm.addEventListener('change', function () { enforceButton.disabled = '1' === enforceButton.dataset.emergency || !enforceConfirm.checked; });
    qsa('.ms-mode-button').forEach(function (button) {
        button.addEventListener('click', function () {
            var mode = button.dataset.mode;
            var duration = 0;
            var confirmed = '0';
            if ('enforce' === mode) {
                if (!enforceConfirm || !enforceConfirm.checked) { toast('ابتدا تأیید خطر را علامت بزنید.', 'danger'); return; }
                if (!window.confirm('هشدار نهایی: دامنه‌های Blocklist واقعاً مسدود می‌شوند و ممکن است پرداخت، پیامک یا سرویس ضروری قطع شود. ادامه می‌دهید؟')) return;
                duration = qs('#ms-enforce-duration').value;
                confirmed = '1';
            }
            setBusy(button, 'در حال تغییر حالت…');
            post('ms_set_operation_mode', { mode: mode, duration: duration, confirmed: confirmed }).then(function () { location.hash = 'network'; location.reload(); }).catch(function (error) { toast(error.message, 'danger'); clearBusy(button); });
        });
    });

    var ruleForm = qs('#ms-domain-rule-form');
    if (ruleForm) ruleForm.addEventListener('submit', function (event) {
        event.preventDefault();
        var button = qs('button[type="submit"]', ruleForm); var data = new FormData(ruleForm); var list = data.get('list');
        if ('block' === list && 'enforce' === msPaConfig.current_mode && !window.confirm('حالت اجرایی فعال است؛ این دامنه بلافاصله پس از ذخیره مسدود می‌شود. ادامه می‌دهید؟')) return;
        setBusy(button, 'در حال ذخیره…');
        post('ms_save_domain_rule', { domain: data.get('domain'), list: list, category: data.get('category') }).then(function () { location.hash = 'network'; location.reload(); }).catch(function (error) { toast(error.message, 'danger'); clearBusy(button); });
    });
    qsa('.ms-quick-rule').forEach(function (button) { button.addEventListener('click', function () { var list = button.dataset.list; if ('block' === list && 'enforce' === msPaConfig.current_mode && !window.confirm('حالت اجرایی فعال است؛ این دامنه بلافاصله مسدود می‌شود. ادامه می‌دهید؟')) return; setBusy(button, '…'); post('ms_save_domain_rule', { domain: button.dataset.domain, list: list, category: button.dataset.category }).then(function () { location.reload(); }).catch(function (error) { toast(error.message, 'danger'); clearBusy(button); }); }); });
    qsa('.ms-delete-rule').forEach(function (button) { button.addEventListener('click', function () { if (!window.confirm('قانون دامنه «' + button.dataset.domain + '» حذف شود؟')) return; setBusy(button, '…'); post('ms_delete_domain_rule', { domain: button.dataset.domain }).then(function () { location.hash = 'network'; location.reload(); }).catch(function (error) { toast(error.message, 'danger'); clearBusy(button); }); }); });

    function filterRules() { var term = (qs('#ms-rule-search') || {}).value || ''; var list = (qs('#ms-rule-list-filter') || {}).value || ''; var category = (qs('#ms-rule-category-filter') || {}).value || ''; term = term.toLowerCase().trim(); qsa('[data-domain-row]').forEach(function (row) { var visible = (!term || row.dataset.domain.indexOf(term) !== -1) && (!list || row.dataset.list === list) && (!category || row.dataset.category === category); row.style.display = visible ? '' : 'none'; }); }
    ['#ms-rule-search', '#ms-rule-list-filter', '#ms-rule-category-filter'].forEach(function (selector) { var input = qs(selector); if (input) { input.addEventListener('input', filterRules); input.addEventListener('change', filterRules); } });
    qsa('.ms-clear-logs-btn').forEach(function (button) { button.addEventListener('click', function () { if (!window.confirm('تمام لاگ‌های تجمیعی شبکه پاک شوند؟')) return; setBusy(button, 'در حال پاک‌سازی…'); post('ms_clear_network_logs').then(function () { location.hash = 'network'; location.reload(); }).catch(function (error) { toast(error.message, 'danger'); clearBusy(button); }); }); });

    var trial = qs('.ms-trial-banner');
    if (trial) { var remaining = parseInt(trial.dataset.remaining || '0', 10); var output = qs('#ms-trial-countdown'); var timer = window.setInterval(function () { remaining -= 1; if (remaining <= 0) { window.clearInterval(timer); location.reload(); return; } var minutes = Math.floor(remaining / 60); var seconds = remaining % 60; output.textContent = 'خاموش‌شدن خودکار تا ' + minutes + ':' + String(seconds).padStart(2, '0'); }, 1000); }
}());
