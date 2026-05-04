@extends('voyager::master')

@section('page_title', __('Manage Subscriptions'))

@section('content')
<div class="page-content container-fluid" id="manage-subscriptions-app">
    <div class="sub-header">
        <div class="sub-title">
            <h1 class="page-title mb-0">{{ __('Manage Subscriptions') }}</h1>
            <p class="text-muted mb-0">{{ __('Move subscriptions between pending, activated, and deactivated.') }}</p>
        </div>
        <div class="sub-actions">
            <button type="button" class="btn btn-sm btn-outline-primary" data-refresh>
                <i class="voyager-refresh mr-1"></i>{{ __('Refresh') }}
            </button>
        </div>
    </div>

    <div class="sub-shell">
        <div class="sub-loading" data-loading>
            <i class="voyager-refresh voyager-animate-spin mr-2"></i>{{ __('Loading subscriptions...') }}
        </div>

        <div class="sub-tabs" data-ui style="display:none;">
            <button type="button" class="sub-tab is-active" data-tab="pending">
                {{ __('Pending') }} <span class="sub-count" data-count="pending">0</span>
            </button>
            <button type="button" class="sub-tab" data-tab="active">
                {{ __('Activated') }} <span class="sub-count" data-count="active">0</span>
            </button>
            <button type="button" class="sub-tab" data-tab="deactivated">
                {{ __('Deactivated') }} <span class="sub-count" data-count="deactivated">0</span>
            </button>
        </div>

        <div class="sub-panels" data-ui style="display:none;">
            <div class="sub-panel is-active" data-panel="pending"></div>
            <div class="sub-panel" data-panel="active"></div>
            <div class="sub-panel" data-panel="deactivated"></div>
        </div>
    </div>
</div>

<template id="manage-subscription-template">
    <details class="sub-card">
        <summary class="sub-card__summary">
            <div class="sub-card__left">
                <div class="sub-card__chev">
                    <i class="voyager-angle-down" aria-hidden="true"></i>
                </div>
                <div class="sub-card__meta">
                    <div class="sub-card__title">
                        {{ __('Subscription') }} #<span data-field="id"></span>
                    </div>
                    <div class="sub-card__sub text-muted" data-field="user"></div>
                </div>
            </div>
            <div class="sub-card__right">
                <span class="sub-badge" data-field="status"></span>
                <span class="sub-card__date text-muted" data-field="created"></span>
            </div>
        </summary>

        <div class="sub-card__body">
            <div class="sub-grid">
                <div class="sub-block">
                    <div class="sub-block__title">{{ __('User') }}</div>
                    <div data-field="user-name"></div>
                    <div class="text-muted" data-field="user-email"></div>
                    <div class="text-muted" data-field="user-phone"></div>
                </div>

                <div class="sub-block">
                    <div class="sub-block__title">{{ __('Plan') }}</div>
                    <div data-field="plan-name"></div>
                    <div class="text-muted" data-field="plan-meta"></div>
                </div>

                <div class="sub-block">
                    <div class="sub-block__title">{{ __('Bank transfer') }}</div>
                    <div><strong>{{ __('Amount') }}:</strong> <span data-field="tx-amount"></span></div>
                    <div><strong>{{ __('Date') }}:</strong> <span data-field="tx-date"></span></div>
                </div>

                <div class="sub-block">
                    <div class="sub-block__title">{{ __('Period') }}</div>
                    <div><strong>{{ __('Start') }}:</strong> <span data-field="start"></span></div>
                    <div><strong>{{ __('End') }}:</strong> <span data-field="end"></span></div>
                </div>
            </div>

            <div class="sub-divider"></div>

            <div class="sub-buttons">
                <button type="button" class="btn btn-success" data-action="activate">
                    <i class="voyager-check mr-1"></i>{{ __('Activate') }}
                </button>
                <button type="button" class="btn btn-danger" data-action="deactivate">
                    <i class="voyager-x mr-1"></i>{{ __('Deactivate') }}
                </button>
            </div>
        </div>
    </details>
</template>
@endsection

@section('css')
<style>
    /* Same styling as weekly orders, scoped to manage-subscriptions */
    #manage-subscriptions-app { padding: 18px 24px; }
    #manage-subscriptions-app .sub-header { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; margin-bottom:16px; }
    #manage-subscriptions-app .sub-shell { background:#fff; border:1px solid rgba(0,0,0,0.08); border-radius:14px; overflow:hidden; }
    #manage-subscriptions-app .sub-loading { padding:28px; text-align:center; color:#667085; }

    #manage-subscriptions-app .sub-tabs { display:flex; gap:10px; padding:12px 14px; background:#f7f7f7; border-bottom:1px solid rgba(0,0,0,0.06); }
    #manage-subscriptions-app .sub-tab { border:1px solid rgba(0,0,0,0.10); background:#fff; color:#344054; border-radius:999px; padding:10px 14px; font-weight:700; display:inline-flex; align-items:center; gap:10px; transition: background .15s ease, border-color .15s ease; }
    #manage-subscriptions-app .sub-tab:hover { background: rgba(0,0,0,0.02); }
    #manage-subscriptions-app .sub-tab.is-active { border-color: rgba(0,0,0,0.20); box-shadow: 0 1px 0 rgba(0,0,0,0.06); }
    #manage-subscriptions-app .sub-count { display:inline-flex; align-items:center; justify-content:center; min-width:22px; height:22px; padding:0 6px; border-radius:999px; background: rgba(0,0,0,0.07); font-size:12px; font-weight:800; }
    #manage-subscriptions-app .sub-tab[data-tab="pending"] { background:#fef3c7; color:#92400e; border-color:#fde68a; }
    #manage-subscriptions-app .sub-tab[data-tab="active"] { background:#d1fae5; color:#065f46; border-color:#a7f3d0; }
    #manage-subscriptions-app .sub-tab[data-tab="deactivated"] { background:#fee2e2; color:#991b1b; border-color:#fecaca; }
    #manage-subscriptions-app .sub-tab[data-tab="pending"] .sub-count { background:rgba(146,64,14,0.14); }
    #manage-subscriptions-app .sub-tab[data-tab="active"] .sub-count { background:rgba(6,95,70,0.14); }
    #manage-subscriptions-app .sub-tab[data-tab="deactivated"] .sub-count { background:rgba(153,27,27,0.14); }

    #manage-subscriptions-app .sub-panels { padding:14px; }
    #manage-subscriptions-app .sub-panel { display:none; margin:0; padding:0; }
    #manage-subscriptions-app .sub-panel.is-active { display:block; }

    #manage-subscriptions-app details.sub-card { background:#fff; border:1px solid rgba(0,0,0,0.08); border-radius:14px; overflow:hidden; box-shadow: 0 1px 2px rgba(16,24,40,0.06); margin:0 0 14px; }
    #manage-subscriptions-app details.sub-card summary::-webkit-details-marker { display:none; }
    #manage-subscriptions-app .sub-card__summary { cursor:pointer; user-select:none; padding:14px 16px; display:flex; justify-content:space-between; align-items:center; gap:12px; border-bottom:1px solid rgba(0,0,0,0.06); }
    #manage-subscriptions-app .sub-card__left { display:flex; align-items:center; gap:12px; min-width:0; }
    #manage-subscriptions-app .sub-card__chev { width:34px; height:34px; border-radius:10px; border:1px solid rgba(0,0,0,0.10); display:flex; align-items:center; justify-content:center; color:#475467; flex:0 0 auto; background:#fff; }
    #manage-subscriptions-app details.sub-card[open] .sub-card__chev i { transform: rotate(180deg); display:inline-block; transition: transform .15s ease; }
    #manage-subscriptions-app .sub-card__meta { min-width:0; }
    #manage-subscriptions-app .sub-card__title { font-weight:800; color:#101828; line-height:1.2; }
    #manage-subscriptions-app .sub-card__sub { font-size:12px; line-height:1.2; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:60vw; }
    #manage-subscriptions-app .sub-card__right { display:flex; align-items:center; gap:10px; flex:0 0 auto; text-align:right; }
    #manage-subscriptions-app .sub-card__date { font-size:12px; white-space:nowrap; }

    #manage-subscriptions-app .sub-badge { display:inline-flex; align-items:center; justify-content:center; border-radius:999px; padding:6px 10px; background:#fef3c7; border:1px solid rgba(0,0,0,0.10); font-weight:800; letter-spacing:.04em; text-transform:uppercase; font-size:11px; white-space:nowrap; }
    /* Traffic light */
    #manage-subscriptions-app .sub-badge.is-pending      { background:#fef3c7; color:#92400e; }
    #manage-subscriptions-app .sub-badge.is-active       { background:#d1fae5; color:#065f46; }
    #manage-subscriptions-app .sub-badge.is-deactivated  { background:#fee2e2; color:#991b1b; }
    #manage-subscriptions-app .sub-badge.is-expired      { background:#fee2e2; color:#991b1b; }

    #manage-subscriptions-app .sub-card__body { padding:14px 16px 16px; }
    #manage-subscriptions-app .sub-grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:14px; }
    #manage-subscriptions-app .sub-block__title { font-weight:800; font-size:12px; color:#475467; text-transform:uppercase; letter-spacing:.04em; margin-bottom:8px; }
    #manage-subscriptions-app .sub-divider { height:1px; background: rgba(0,0,0,0.08); margin:14px 0; }
    #manage-subscriptions-app .sub-buttons { display:flex; gap:10px; flex-wrap:wrap; margin-top:6px; }
    #manage-subscriptions-app .sub-buttons .btn { border-radius:12px; font-weight:800; }

    @media (max-width: 640px) {
        #manage-subscriptions-app { padding:16px; }
        #manage-subscriptions-app .sub-card__right { flex-direction:column; align-items:flex-end; }
    }
</style>
@endsection

@push('javascript')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const app = document.getElementById('manage-subscriptions-app');
        if (!app) return;

        const state = { pending: [], active: [], deactivated: [] };
        let activeTab = 'pending';

        const endpoints = {
            index: '{{ route('voyager.manage-subscriptions.data') }}',
            activate: function (id) { return '{{ url('/admin/manage-subscriptions') }}' + '/' + id + '/activate'; },
            deactivate: function (id) { return '{{ url('/admin/manage-subscriptions') }}' + '/' + id + '/deactivate'; },
        };

        const template = document.getElementById('manage-subscription-template');
        const loader = app.querySelector('[data-loading]');
        const uiEls = app.querySelectorAll('[data-ui]');
        const tabButtons = app.querySelectorAll('[data-tab]');
        const panels = app.querySelectorAll('[data-panel]');
        const panelEls = {
            pending: app.querySelector('[data-panel="pending"]'),
            active: app.querySelector('[data-panel="active"]'),
            deactivated: app.querySelector('[data-panel="deactivated"]'),
        };
        const countEls = {
            pending: app.querySelector('[data-count="pending"]'),
            active: app.querySelector('[data-count="active"]'),
            deactivated: app.querySelector('[data-count="deactivated"]'),
        };
        const refreshButtons = app.querySelectorAll('[data-refresh]');
        const csrf = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '';

        refreshButtons.forEach(function (btn) {
            btn.addEventListener('click', fetchSubscriptions);
        });

        function setLoading(isLoading) {
            loader.style.display = isLoading ? 'block' : 'none';
            uiEls.forEach(function (el) {
                el.style.display = isLoading ? 'none' : '';
            });
        }

        function setActiveTab(tab) {
            activeTab = tab;
            tabButtons.forEach(function (btn) {
                btn.classList.toggle('is-active', btn.getAttribute('data-tab') === tab);
            });
            panels.forEach(function (panel) {
                panel.classList.toggle('is-active', panel.getAttribute('data-panel') === tab);
            });
            renderActive();
        }

        tabButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                setActiveTab(btn.getAttribute('data-tab'));
            });
        });

        function renderCounts() {
            ['pending', 'active', 'deactivated'].forEach(function (key) {
                if (countEls[key]) countEls[key].textContent = String((state[key] || []).length);
            });
        }

        function renderActive() {
            const listType = activeTab;
            const host = panelEls[listType];
            if (!host) return;

            host.innerHTML = '';
            const items = state[listType] || [];

            if (!items.length) {
                const empty = document.createElement('div');
                empty.className = 'text-center text-muted py-4';
                empty.textContent = '{{ __('No subscriptions.') }}';
                host.appendChild(empty);
                return;
            }

            items.forEach(function (sub) {
                const fragment = template.content.cloneNode(true);
                populateSubscription(fragment, sub);
                host.appendChild(fragment);
            });
        }

        function statusLabel(status) {
            if (status === 'active') return '{{ __('Activated') }}';
            if (status === 'deactivated') return '{{ __('Deactivated') }}';
            if (status === 'expired') return '{{ __('Expired') }}';
            return '{{ __('Pending') }}';
        }

        function populateSubscription(fragment, sub) {
            const user = sub.user || {};
            const plan = sub.plan || {};
            const status = (sub.status || 'pending').toLowerCase();

            fragment.querySelector('[data-field="id"]').textContent = sub.id;
            fragment.querySelector('[data-field="user"]').textContent = formatUserLabel(user);

            const statusEl = fragment.querySelector('[data-field="status"]');
            statusEl.textContent = String(statusLabel(status)).toUpperCase();
            statusEl.className = 'sub-badge is-' + status;

            fragment.querySelector('[data-field="created"]').textContent = formatDateTime(sub.created_at);

            fragment.querySelector('[data-field="user-name"]').textContent = user.name || '{{ __('N/A') }}';
            fragment.querySelector('[data-field="user-email"]').textContent = user.email ? '{{ __('Email:') }} ' + user.email : '';
            fragment.querySelector('[data-field="user-phone"]').textContent = user.phone ? '{{ __('Phone:') }} ' + user.phone : '';

            fragment.querySelector('[data-field="plan-name"]').textContent = plan.name || '{{ __('N/A') }}';
            const planParts = [];
            if (plan.price != null) planParts.push('{{ __('Price') }}: ' + plan.price);
            if (plan.books_quota != null) planParts.push('{{ __('Books quota') }}: ' + plan.books_quota);
            fragment.querySelector('[data-field="plan-meta"]').textContent = planParts.join(' • ');

            fragment.querySelector('[data-field="tx-amount"]').textContent = sub.transaction_amount != null ? String(sub.transaction_amount) : '{{ __('N/A') }}';
            fragment.querySelector('[data-field="tx-date"]').textContent = sub.transaction_date ? formatDateTime(sub.transaction_date) : '{{ __('N/A') }}';

            fragment.querySelector('[data-field="start"]').textContent = sub.start ? formatDateTime(sub.start) : '{{ __('N/A') }}';
            fragment.querySelector('[data-field="end"]').textContent = sub.end ? formatDateTime(sub.end) : '{{ __('N/A') }}';

            const activateBtn = fragment.querySelector('[data-action="activate"]');
            const deactivateBtn = fragment.querySelector('[data-action="deactivate"]');

            // Activation should only be done after bank transfer review (pending -> active).
            activateBtn.style.display = (status === 'pending') ? '' : 'none';
            // Deactivation is allowed from pending/active.
            deactivateBtn.style.display = (status === 'pending' || status === 'active') ? '' : 'none';

            activateBtn.addEventListener('click', function (e) {
                e.preventDefault();
                activateBtn.disabled = true;
                deactivateBtn.disabled = true;
                mutate(endpoints.activate(sub.id), {}).then(function (json) {
                    const updated = json && json.subscription ? json.subscription : null;
                    removeSub(sub.id);
                    if (updated) upsertSub('active', updated);
                    toastr.success('{{ __('Subscription activated') }} #' + sub.id);
                }).catch(function () {
                    toastr.error('{{ __('Unable to activate right now.') }}');
                }).finally(function () {
                    activateBtn.disabled = false;
                    deactivateBtn.disabled = false;
                });
            });

            deactivateBtn.addEventListener('click', function (e) {
                e.preventDefault();
                activateBtn.disabled = true;
                deactivateBtn.disabled = true;
                mutate(endpoints.deactivate(sub.id), {}).then(function (json) {
                    const updated = json && json.subscription ? json.subscription : null;
                    removeSub(sub.id);
                    if (updated) upsertSub('deactivated', updated);
                    toastr.info('{{ __('Subscription deactivated') }} #' + sub.id);
                }).catch(function () {
                    toastr.error('{{ __('Unable to deactivate right now.') }}');
                }).finally(function () {
                    activateBtn.disabled = false;
                    deactivateBtn.disabled = false;
                });
            });
        }

        function removeSub(id) {
            ['pending', 'active', 'deactivated'].forEach(function (key) {
                state[key] = (state[key] || []).filter(function (s) { return s.id !== id; });
            });
            renderCounts();
            renderActive();
        }

        function upsertSub(listType, sub) {
            if (!state[listType] || !sub) return;
            state[listType] = (state[listType] || []).filter(function (s) { return s.id !== sub.id; });
            state[listType].unshift(sub);
            renderCounts();
            renderActive();
        }

        function mutate(url, payload) {
            return fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf
                },
                body: JSON.stringify(payload || {})
            }).then(function (response) {
                if (!response.ok) throw new Error('Request failed');
                return response.json();
            });
        }

        function fetchSubscriptions() {
            setLoading(true);
            fetch(endpoints.index, { headers: { 'Accept': 'application/json' } })
                .then(function (response) {
                    if (!response.ok) throw new Error('Request failed');
                    return response.json();
                })
                .then(function (json) {
                    state.pending = Array.isArray(json.pending) ? json.pending : [];
                    state.active = Array.isArray(json.active) ? json.active : [];
                    state.deactivated = Array.isArray(json.deactivated) ? json.deactivated : [];
                    renderCounts();
                    renderActive();
                })
                .catch(function () {
                    toastr.error('{{ __('Unable to load subscriptions.') }}');
                })
                .finally(function () { setLoading(false); });
        }

        function formatUserLabel(user) {
            const name = user.name || '{{ __('Customer') }}';
            const email = user.email || '';
            return email ? name + ' — ' + email : name;
        }

        function formatDateTime(isoString) {
            if (!isoString) return '';
            try { return new Date(isoString).toLocaleString(); } catch (e) { return isoString; }
        }

        setActiveTab('pending');
        fetchSubscriptions();

        var _pollTimer = setInterval(fetchSubscriptions, 30000);
        document.addEventListener('visibilitychange', function () {
            if (document.hidden) {
                clearInterval(_pollTimer);
            } else {
                fetchSubscriptions();
                _pollTimer = setInterval(fetchSubscriptions, 30000);
            }
        });
    });
</script>
@endpush
