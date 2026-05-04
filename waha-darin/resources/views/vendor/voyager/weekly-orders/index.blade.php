
@extends('voyager::master')

@section('page_title', __('Weekly Orders'))

@section('content')
<div class="page-content container-fluid" id="weekly-orders-app">
    <div class="wo-header">
        <div class="wo-title">
            <h1 class="page-title mb-0">{{ __('Weekly Orders') }}</h1>
            <p class="text-muted mb-0">{{ __('Manage received, shipped, and delivered borrow orders.') }}</p>
        </div>
        <div class="wo-actions">
            <button type="button" class="btn btn-sm btn-outline-primary" data-refresh>
                <i class="voyager-refresh mr-1"></i>{{ __('Refresh') }}
            </button>
        </div>
    </div>

    <div class="wo-shell">
        <div class="wo-loading" data-loading>
            <i class="voyager-refresh voyager-animate-spin mr-2"></i>{{ __('Loading orders...') }}
        </div>

        <div class="wo-tabs" data-ui style="display:none;">
            <button type="button" class="wo-tab is-active" data-tab="received">
                {{ __('Received') }} <span class="wo-count" data-count="received">0</span>
            </button>
            <button type="button" class="wo-tab" data-tab="shipped">
                {{ __('Shipped') }} <span class="wo-count" data-count="shipped">0</span>
            </button>
            <button type="button" class="wo-tab" data-tab="delivered">
                {{ __('Delivered') }} <span class="wo-count" data-count="delivered">0</span>
            </button>
            <button type="button" class="wo-tab" data-tab="returned">
                {{ __('Returned') }} <span class="wo-count" data-count="returned">0</span>
            </button>
        </div>

        <div class="wo-panels" data-ui style="display:none;">
            <div class="wo-bulk" data-bulk style="display:none;">
                <label class="wo-bulk__left">
                    <input type="checkbox" data-bulk-select-all />
                    <span>{{ __('Select all') }}</span>
                </label>
                <button type="button" class="btn btn-primary btn-sm" data-bulk-deliver disabled>
                    {{ __('Mark delivered') }} <span data-bulk-count>(0)</span>
                </button>
            </div>
            <div class="wo-panel is-active" data-panel="received"></div>
            <div class="wo-panel" data-panel="shipped"></div>
            <div class="wo-panel" data-panel="delivered"></div>
            <div class="wo-panel" data-panel="returned"></div>
        </div>
    </div>
</div>

<template id="weekly-order-template">
    <details class="wo-order">
        <summary class="wo-order__summary">
            <div class="wo-order__left">
                <div class="wo-order__select" data-field="select-wrap" style="display:none;">
                    <input type="checkbox" data-field="select" aria-label="{{ __('Select order') }}" />
                </div>
                <div class="wo-order__chev">
                    <i class="voyager-angle-down" aria-hidden="true"></i>
                </div>
                <div class="wo-order__meta">
                    <div class="wo-order__title">
                        {{ __('Order') }} #<span data-field="id"></span>
                    </div>
                    <div class="wo-order__sub text-muted" data-field="user"></div>
                </div>
            </div>
            <div class="wo-order__right">
                <span class="wo-badge" data-field="status"></span>
                <span class="wo-order__date text-muted" data-field="created"></span>
            </div>
        </summary>

        <div class="wo-order__body">
            <div class="wo-grid">
                <div class="wo-block">
                    <div class="wo-block__title">{{ __('Recipient') }}</div>
                    <div data-field="customer-name"></div>
                    <div class="text-muted" data-field="customer-phone"></div>
                    <div class="text-muted" data-field="customer-email"></div>
                </div>

                <div class="wo-block">
                    <div class="wo-block__title">{{ __('Address') }}</div>
                    <div data-field="address-line-one"></div>
                    <div data-field="address-line-two"></div>
                    <div class="text-muted" data-field="address-extra"></div>
                </div>

                <div class="wo-block">
                    <div class="wo-block__title">{{ __('Books') }}</div>
                    <ul class="wo-books" data-field="books"></ul>
                </div>

                <div class="wo-block">
                    <div class="wo-block__title">{{ __('Schedule') }}</div>
                    <div><strong>{{ __('Start') }}:</strong> <span data-field="start-date"></span></div>
                    <div><strong>{{ __('Due') }}:</strong> <span data-field="end-date"></span></div>
                </div>
            </div>

            <div class="wo-divider"></div>

            <div class="wo-actions-row" data-section="received-fields">
                <div class="wo-field">
                    <label class="wo-label">{{ __('Shipment number') }}</label>
                    <input type="text" class="form-control" data-field="shipment-input" placeholder="1234567890" autocomplete="off">
                </div>
                <div class="wo-field">
                    <label class="wo-label">{{ __('Cancel note (optional)') }}</label>
                    <textarea class="form-control" rows="1" data-field="cancel-note"></textarea>
                </div>
            </div>

            <div class="wo-actions-row" data-section="shipped-fields" style="display:none;">
                <div class="wo-inline">
                    <strong>{{ __('Shipment number') }}:</strong> <span data-field="shipment-number"></span>
                </div>
            </div>

            <div class="wo-actions-row" data-section="delivered-fields" style="display:none;">
                <div class="wo-inline">
                    <strong>{{ __('Delivered at') }}:</strong> <span data-field="delivered-at"></span>
                </div>
                <div class="wo-inline">
                    <strong>{{ __('Return shipment number') }}:</strong> <span data-field="return-shipment-number"></span>
                </div>
            </div>

            <div class="wo-buttons">
                <button type="button" class="btn btn-danger" data-action="cancel">
                    <i class="voyager-x mr-1"></i>{{ __('Cancel') }}
                </button>
                <button type="button" class="btn btn-success" data-action="confirm">
                    <i class="voyager-check mr-1"></i>{{ __('Confirm') }}
                </button>
                <button type="button" class="btn btn-primary" data-action="deliver" style="display:none;">
                    <i class="voyager-check mr-1"></i>{{ __('Mark delivered') }}
                </button>
                <button type="button" class="btn btn-primary" data-action="returned" style="display:none;">
                    <i class="voyager-check mr-1"></i>{{ __('Mark returned') }}
                </button>
            </div>
        </div>
    </details>
</template>
@endsection

@section('css')
<style>
    /* Weekly Orders — clean layout (aligned with manage-subscriptions) */

    #weekly-orders-app {
        padding: 18px 24px;
    }

    #weekly-orders-app .wo-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 16px;
    }

    #weekly-orders-app .wo-shell {
        background: #fff;
        border: 1px solid rgba(0, 0, 0, 0.08);
        border-radius: 14px;
        overflow: hidden;
    }

    #weekly-orders-app .wo-loading {
        padding: 28px;
        text-align: center;
        color: #667085;
    }

    #weekly-orders-app .wo-tabs {
        display: flex;
        gap: 10px;
        padding: 12px 14px;
        background: #f7f7f7;
        border-bottom: 1px solid rgba(0, 0, 0, 0.06);
    }

    #weekly-orders-app .wo-tab {
        border: 1px solid rgba(0, 0, 0, 0.10);
        background: #fff;
        color: #344054;
        border-radius: 999px;
        padding: 10px 14px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        transition: background 0.15s ease, border-color 0.15s ease;
    }

    #weekly-orders-app .wo-tab:hover {
        background: rgba(0, 0, 0, 0.02);
    }

    #weekly-orders-app .wo-tab.is-active {
        border-color: rgba(0, 0, 0, 0.20);
        box-shadow: 0 1px 0 rgba(0, 0, 0, 0.06);
    }

    #weekly-orders-app .wo-count {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 22px;
        height: 22px;
        padding: 0 6px;
        border-radius: 999px;
        background: rgba(0, 0, 0, 0.07);
        font-size: 12px;
        font-weight: 800;
    }

    /* Tab colors: workflow left→right from blue toward red */
    #weekly-orders-app .wo-tab[data-tab="received"] {
        background: #dbeafe;
        color: #1e3a8a;
        border-color: #93c5fd;
    }

    #weekly-orders-app .wo-tab[data-tab="shipped"] {
        background: #e9d5ff;
        color: #5b21b6;
        border-color: #c4b5fd;
    }

    #weekly-orders-app .wo-tab[data-tab="delivered"] {
        background: #fce7f3;
        color: #9f1239;
        border-color: #f9a8d4;
    }

    #weekly-orders-app .wo-tab[data-tab="returned"] {
        background: #fee2e2;
        color: #991b1b;
        border-color: #fca5a5;
    }

    #weekly-orders-app .wo-tab[data-tab="received"] .wo-count {
        background: rgba(30, 58, 138, 0.16);
    }

    #weekly-orders-app .wo-tab[data-tab="shipped"] .wo-count {
        background: rgba(91, 33, 182, 0.16);
    }

    #weekly-orders-app .wo-tab[data-tab="delivered"] .wo-count {
        background: rgba(159, 18, 57, 0.16);
    }

    #weekly-orders-app .wo-tab[data-tab="returned"] .wo-count {
        background: rgba(153, 27, 27, 0.16);
    }

    #weekly-orders-app .wo-panels {
        padding: 14px;
    }

    #weekly-orders-app .wo-bulk {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 12px 14px 0;
    }

    #weekly-orders-app .wo-bulk__left {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        margin: 0;
        font-weight: 800;
        color: #344054;
    }

    #weekly-orders-app .wo-order__select {
        width: 34px;
        height: 34px;
        border-radius: 10px;
        border: 1px solid rgba(0, 0, 0, 0.10);
        display: flex;
        align-items: center;
        justify-content: center;
        background: #fff;
        flex: 0 0 auto;
    }

    #weekly-orders-app .wo-panel {
        display: none;
        margin: 0;
        padding: 0;
    }

    #weekly-orders-app .wo-panel.is-active {
        display: block;
    }

    /* Order card (same shell as manage-subscriptions) */
    #weekly-orders-app details.wo-order {
        background: #fff;
        border: 1px solid rgba(0, 0, 0, 0.08);
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 1px 2px rgba(16, 24, 40, 0.06);
        margin: 0 0 14px;
    }

    #weekly-orders-app details.wo-order summary::-webkit-details-marker {
        display: none;
    }

    #weekly-orders-app .wo-order__summary {
        cursor: pointer;
        user-select: none;
        padding: 14px 16px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        border-bottom: 1px solid rgba(0, 0, 0, 0.06);
    }

    #weekly-orders-app .wo-order__left {
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 0;
    }

    #weekly-orders-app .wo-order__chev {
        width: 34px;
        height: 34px;
        border-radius: 10px;
        border: 1px solid rgba(0, 0, 0, 0.10);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #475467;
        flex: 0 0 auto;
        background: #fff;
    }

    #weekly-orders-app details.wo-order[open] .wo-order__chev i {
        transform: rotate(180deg);
        display: inline-block;
        transition: transform 0.15s ease;
    }

    #weekly-orders-app .wo-order__meta {
        min-width: 0;
    }

    #weekly-orders-app .wo-order__title {
        font-weight: 800;
        color: #101828;
        line-height: 1.2;
    }

    #weekly-orders-app .wo-order__sub {
        font-size: 12px;
        line-height: 1.2;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        max-width: 60vw;
    }

    #weekly-orders-app .wo-order__right {
        display: flex;
        align-items: center;
        gap: 10px;
        flex: 0 0 auto;
        text-align: right;
    }

    #weekly-orders-app .wo-order__date {
        font-size: 12px;
        white-space: nowrap;
    }

    #weekly-orders-app .wo-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        padding: 6px 10px;
        background: #e5e7eb;
        border: 1px solid rgba(0, 0, 0, 0.10);
        font-weight: 800;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        font-size: 11px;
        white-space: nowrap;
    }
    /* Status badges: same blue → red progression as tabs */
    #weekly-orders-app .wo-badge[data-status="received"]               { background:#dbeafe; color:#1e3a8a; border-color:#93c5fd; }
    #weekly-orders-app .wo-badge[data-status="shipped"]                { background:#e9d5ff; color:#5b21b6; border-color:#c4b5fd; }
    #weekly-orders-app .wo-badge[data-status="delivered"]              { background:#fce7f3; color:#9f1239; border-color:#f9a8d4; }
    #weekly-orders-app .wo-badge[data-status="waitingreturnshipment"]  { background:#fecaca; color:#b91c1c; border-color:#f87171; }
    #weekly-orders-app .wo-badge[data-status="returned"],
    #weekly-orders-app .wo-badge[data-status="completed"]              { background:#fee2e2; color:#991b1b; border-color:#fca5a5; }
    #weekly-orders-app .wo-badge[data-status="cancelled"]              { background:#fef2f2; color:#7f1d1d; border-color:#f87171; }

    #weekly-orders-app .wo-order__body {
        padding: 14px 16px 16px;
    }

    #weekly-orders-app .wo-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 14px;
    }

    #weekly-orders-app .wo-block__title {
        font-weight: 800;
        font-size: 12px;
        color: #475467;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        margin-bottom: 8px;
    }

    #weekly-orders-app .wo-books {
        list-style: none;
        margin: 0;
        padding: 0;
    }

    #weekly-orders-app .wo-book {
        display: flex;
        gap: 10px;
        padding: 8px 0;
        border-bottom: 1px solid rgba(0, 0, 0, 0.06);
    }

    #weekly-orders-app .wo-book:last-child {
        border-bottom: none;
    }

    #weekly-orders-app .wo-book__img {
        width: 46px;
        height: 70px;
        border-radius: 10px;
        overflow: hidden;
        border: 1px solid rgba(0, 0, 0, 0.08);
        background: rgba(0, 0, 0, 0.03);
        flex: 0 0 auto;
    }

    #weekly-orders-app .wo-book__img img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    #weekly-orders-app .wo-book__meta {
        min-width: 0;
    }

    #weekly-orders-app .wo-book__title {
        font-weight: 800;
        margin-bottom: 2px;
    }

    #weekly-orders-app .wo-divider {
        height: 1px;
        background: rgba(0, 0, 0, 0.08);
        margin: 14px 0;
    }

    #weekly-orders-app .wo-actions-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
        margin-bottom: 12px;
    }

    #weekly-orders-app .wo-label {
        display: block;
        font-weight: 800;
        font-size: 12px;
        color: #475467;
        margin-bottom: 6px;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    #weekly-orders-app .wo-inline {
        padding: 10px 12px;
        border: 1px solid rgba(0, 0, 0, 0.08);
        border-radius: 12px;
        background: rgba(0, 0, 0, 0.02);
    }

    #weekly-orders-app .wo-buttons {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-top: 6px;
    }

    #weekly-orders-app .wo-buttons .btn {
        border-radius: 12px;
        font-weight: 800;
    }

    @media (max-width: 640px) {
        #weekly-orders-app {
            padding: 16px;
        }

        #weekly-orders-app .wo-actions-row {
            grid-template-columns: 1fr;
        }

        #weekly-orders-app .wo-order__right {
            flex-direction: column;
            align-items: flex-end;
        }
    }
</style>
@endsection

@push('javascript')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const app = document.getElementById('weekly-orders-app');
        if (!app) {
            return;
        }

        const state = { received: [], shipped: [], delivered: [], returned: [] };
        let activeTab = 'received';
        const selectedShipped = new Set();

        const endpoints = {
            index: '{{ route('voyager.weekly-orders.data') }}',
            confirm: function (id) {
                return '{{ url('/admin/weekly-orders') }}' + '/' + id + '/confirm';
            },
            deliver: function (id) {
                return '{{ url('/admin/weekly-orders') }}' + '/' + id + '/deliver';
            },
            returned: function (id) {
                return '{{ url('/admin/weekly-orders') }}' + '/' + id + '/returned';
            },
            cancel: function (id) {
                return '{{ url('/admin/weekly-orders') }}' + '/' + id + '/cancel';
            }
        };

        const template = document.getElementById('weekly-order-template');
        const loader = app.querySelector('[data-loading]');
        const uiEls = app.querySelectorAll('[data-ui]');
        const tabButtons = app.querySelectorAll('[data-tab]');
        const panels = app.querySelectorAll('[data-panel]');
        const bulkBar = app.querySelector('[data-bulk]');
        const bulkSelectAll = app.querySelector('[data-bulk-select-all]');
        const bulkDeliverBtn = app.querySelector('[data-bulk-deliver]');
        const bulkCount = app.querySelector('[data-bulk-count]');
        const panelEls = {
            received: app.querySelector('[data-panel="received"]'),
            shipped: app.querySelector('[data-panel="shipped"]'),
            delivered: app.querySelector('[data-panel="delivered"]'),
            returned: app.querySelector('[data-panel="returned"]'),
        };
        const countEls = {
            received: app.querySelector('[data-count="received"]'),
            shipped: app.querySelector('[data-count="shipped"]'),
            delivered: app.querySelector('[data-count="delivered"]'),
            returned: app.querySelector('[data-count="returned"]'),
        };
        const refreshButtons = app.querySelectorAll('[data-refresh]');
        const csrf = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '';

        refreshButtons.forEach(function (btn) {
            btn.addEventListener('click', fetchOrders);
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

            // Bulk bar is only relevant for shipped tab
            if (bulkBar) {
                bulkBar.style.display = (tab === 'shipped') ? 'flex' : 'none';
            }
            updateBulkUI();
            renderActive();
        }

        tabButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                setActiveTab(btn.getAttribute('data-tab'));
            });
        });

        function renderCounts() {
            ['received', 'shipped', 'delivered', 'returned'].forEach(function (key) {
                if (countEls[key]) countEls[key].textContent = String((state[key] || []).length);
            });
        }

        function renderActive() {
            const listType = activeTab;
            const host = panelEls[listType];
            if (!host) return;

            host.innerHTML = '';
            const orders = state[listType] || [];

            if (!orders.length) {
                const empty = document.createElement('div');
                empty.className = 'text-center text-muted py-4';
                empty.textContent = '{{ __('No orders.') }}';
                host.appendChild(empty);
                return;
            }

            orders.forEach(function (order) {
                const fragment = template.content.cloneNode(true);
                populateOrder(fragment, order, listType);
                host.appendChild(fragment);
            });

            updateBulkUI();
        }

        function badgeDataStatusKey(rawStatus) {
            var s = (rawStatus || 'Received').toLowerCase().replace(/\s+/g, '');
            /* API uses ReturnedBack; tabs / CSS use "returned" */
            if (s === 'returnedback') return 'returned';
            return s;
        }

        function populateOrder(fragment, order, listType) {
            const shipping = order.shipping || {};
            const user = order.user || {};

            fragment.querySelector('[data-field="id"]').textContent = order.id;
            fragment.querySelector('[data-field="user"]').textContent = formatRecipientLabel(shipping);
            const statusEl = fragment.querySelector('[data-field="status"]');
            const statusKey = badgeDataStatusKey(order.status);
            statusEl.textContent = (order.status || 'Received').toUpperCase();
            statusEl.setAttribute('data-status', statusKey);
            fragment.querySelector('[data-field="created"]').textContent = formatDateTime(order.created_at);

            fragment.querySelector('[data-field="customer-name"]').textContent = shipping.name || '{{ __('N/A') }}';
            fragment.querySelector('[data-field="customer-phone"]').textContent = shipping.phone ? '{{ __('Phone:') }} ' + shipping.phone : '';
            fragment.querySelector('[data-field="customer-email"]').textContent = user.email ? '{{ __('Email:') }} ' + user.email : '';

            fragment.querySelector('[data-field="address-line-one"]').textContent = shipping.address_line_one || '';
            fragment.querySelector('[data-field="address-line-two"]').textContent = shipping.address_line_two || '';

            var extraParts = [];
            if (shipping.country) extraParts.push(shipping.country);
            if (shipping.region) extraParts.push(shipping.region);
            if (shipping.zip_code) extraParts.push(shipping.zip_code);
            fragment.querySelector('[data-field="address-extra"]').textContent = extraParts.join(', ');

            fragment.querySelector('[data-field="start-date"]').textContent = formatDateOnly(order.start_date);
            fragment.querySelector('[data-field="end-date"]').textContent = formatDateOnly(order.end_date);

            const selectWrap = fragment.querySelector('[data-field="select-wrap"]');
            const selectInput = fragment.querySelector('[data-field="select"]');

            const receivedFields = fragment.querySelector('[data-section="received-fields"]');
            const shippedFields = fragment.querySelector('[data-section="shipped-fields"]');
            const deliveredFields = fragment.querySelector('[data-section="delivered-fields"]');

            if (listType === 'received') {
                if (selectWrap) selectWrap.style.display = 'none';
                receivedFields.style.display = '';
                shippedFields.style.display = 'none';
                deliveredFields.style.display = 'none';
            } else if (listType === 'shipped') {
                if (selectWrap) selectWrap.style.display = '';
                receivedFields.style.display = 'none';
                shippedFields.style.display = '';
                deliveredFields.style.display = 'none';
                const shipmentNumberEl = fragment.querySelector('[data-field="shipment-number"]');
                if (shipmentNumberEl) shipmentNumberEl.textContent = order.shipment_number || '';
            } else {
                if (selectWrap) selectWrap.style.display = 'none';
                receivedFields.style.display = 'none';
                shippedFields.style.display = 'none';
                deliveredFields.style.display = '';
                const deliveredAtEl = fragment.querySelector('[data-field="delivered-at"]');
                if (deliveredAtEl) deliveredAtEl.textContent = formatDateTime(order.delivered_at);
                const returnShipmentEl = fragment.querySelector('[data-field="return-shipment-number"]');
                if (returnShipmentEl) returnShipmentEl.textContent = order.return_shipment_number || '{{ __('N/A') }}';
            }

            if (listType === 'shipped' && selectInput) {
                selectInput.checked = selectedShipped.has(order.id);
                selectInput.addEventListener('click', function (e) {
                    // Prevent <details> toggle when clicking checkbox
                    e.stopPropagation();
                });
                selectInput.addEventListener('change', function (e) {
                    if (e.target.checked) {
                        selectedShipped.add(order.id);
                    } else {
                        selectedShipped.delete(order.id);
                    }
                    updateBulkUI();
                });
            }

            var booksList = fragment.querySelector('[data-field="books"]');
            booksList.innerHTML = '';
            
            if (Array.isArray(order.books) && order.books.length > 0) {
                order.books.forEach(function (book) {
                    var li = document.createElement('li');
                    li.className = 'wo-book';

                    if (book.cover_photo) {
                        var imgWrap = document.createElement('div');
                        imgWrap.className = 'wo-book__img';
                        var imgEl = document.createElement('img');
                        imgEl.src = book.cover_photo;
                        imgEl.alt = book.title || '{{ __('Book cover') }}';
                        imgWrap.appendChild(imgEl);
                        li.appendChild(imgWrap);
                    }

                    var meta = document.createElement('div');
                    meta.className = 'wo-book__meta';

                    var titleEl = document.createElement('div');
                    titleEl.className = 'wo-book__title';
                    titleEl.textContent = book.title || '{{ __('Untitled') }}';
                    meta.appendChild(titleEl);

                    var authorName = null;
                    if (book.author) {
                        if (typeof book.author === 'string') {
                            authorName = book.author;
                        } else if (book.author.name) {
                            authorName = book.author.name;
                        }
                    }

                    if (authorName) {
                        var authorEl = document.createElement('div');
                        authorEl.className = 'text-muted wo-book__author';
                        authorEl.textContent = '{{ __('Author') }}: ' + authorName;
                        meta.appendChild(authorEl);
                    }

                    li.appendChild(meta);
                    booksList.appendChild(li);
                });
            } else {
                var emptyLi = document.createElement('li');
                emptyLi.className = 'text-muted';
                emptyLi.textContent = '{{ __('No books recorded.') }}';
                booksList.appendChild(emptyLi);
            }

            const confirmBtn = fragment.querySelector('[data-action="confirm"]');
            const cancelBtn = fragment.querySelector('[data-action="cancel"]');
            const deliverBtn = fragment.querySelector('[data-action="deliver"]');
            const returnedBtn = fragment.querySelector('[data-action="returned"]');
            const shipmentInput = fragment.querySelector('[data-field="shipment-input"]');
            const cancelNoteInput = fragment.querySelector('[data-field="cancel-note"]');
            
            // Check if order is already processed
            const isProcessed = order.shipment_status && order.shipment_status !== 'pending';
            const isCancelled = order.shipment_status === 'cancelled' || order.status === 'Cancelled';
            
            if (isProcessed) {
                // Disable buttons and show status for processed orders
                confirmBtn.disabled = true;
                cancelBtn.disabled = true;
                shipmentInput.disabled = true;
                cancelNoteInput.disabled = true;
                
                if (order.shipment_status === 'confirmed' && order.shipment_number) {
                    shipmentInput.value = order.shipment_number;
                    shipmentInput.classList.add('bg-light');
                }
                
                if (isCancelled) {
                    const statusBadge = fragment.querySelector('[data-field="status"]');
                    if (statusBadge) {
                        statusBadge.textContent = 'CANCELLED';
                        statusBadge.className = 'badge badge-pill badge-danger text-uppercase';
                    }
                }
            }

            // Toggle buttons by list type
            if (listType === 'received') {
                deliverBtn.style.display = 'none';
                returnedBtn.style.display = 'none';
                confirmBtn.style.display = '';
                cancelBtn.style.display = '';
            } else if (listType === 'shipped') {
                deliverBtn.style.display = '';
                returnedBtn.style.display = 'none';
                confirmBtn.style.display = 'none';
                cancelBtn.style.display = 'none';
                shipmentInput.disabled = true;
                cancelNoteInput.disabled = true;
            } else if (listType === 'delivered') {
                deliverBtn.style.display = 'none';
                confirmBtn.style.display = 'none';
                cancelBtn.style.display = 'none';
                // Show "mark returned" only if customer provided a return shipment number
                returnedBtn.style.display = order.return_shipment_number ? '' : 'none';
                shipmentInput.disabled = true;
                cancelNoteInput.disabled = true;
            } else {
                // returned tab
                deliverBtn.style.display = 'none';
                returnedBtn.style.display = 'none';
                confirmBtn.style.display = 'none';
                cancelBtn.style.display = 'none';
                shipmentInput.disabled = true;
                cancelNoteInput.disabled = true;
            }

            confirmBtn.addEventListener('click', function () {
                if (isProcessed) return;
                
                const shipmentNumber = shipmentInput.value.trim();
                if (!shipmentNumber) {
                    toastr.warning('{{ __('Please add a shipment number first.') }}');
                    return;
                }

                setButtonsDisabled(confirmBtn, cancelBtn, true);
                mutate(endpoints.confirm(order.id), {
                    shipment_number: shipmentNumber
                }).then(function (json) {
                    removeOrder(order.id, listType);
                    if (json && json.order) {
                        upsertOrder('shipped', json.order);
                    }
                    toastr.success('{{ __('Shipment confirmed') }} #' + order.id);
                }).catch(function () {
                    toastr.error('{{ __('Unable to confirm shipment right now.') }}');
                }).finally(function () {
                    setButtonsDisabled(confirmBtn, cancelBtn, false);
                });
            });

            cancelBtn.addEventListener('click', function () {
                if (isProcessed) return;
                
                setButtonsDisabled(confirmBtn, cancelBtn, true);
                mutate(endpoints.cancel(order.id), {
                    cancellation_note: cancelNoteInput.value.trim() || null
                }).then(function () {
                    removeOrder(order.id, listType);
                    toastr.info('{{ __('Order cancelled') }} #' + order.id);
                }).catch(function () {
                    toastr.error('{{ __('Unable to cancel this order right now.') }}');
                }).finally(function () {
                    setButtonsDisabled(confirmBtn, cancelBtn, false);
                });
            });

            deliverBtn.addEventListener('click', function () {
                setButtonsDisabled(confirmBtn, cancelBtn, true);
                deliverBtn.disabled = true;
                mutate(endpoints.deliver(order.id), {}).then(function (json) {
                    removeOrder(order.id, 'shipped');
                    if (json && json.order) {
                        upsertOrder('delivered', json.order);
                    }
                    toastr.success('{{ __('Order marked delivered') }} #' + order.id);
                }).catch(function () {
                    toastr.error('{{ __('Unable to mark delivered right now.') }}');
                }).finally(function () {
                    deliverBtn.disabled = false;
                    setButtonsDisabled(confirmBtn, cancelBtn, false);
                });
            });

            returnedBtn.addEventListener('click', function () {
                returnedBtn.disabled = true;
                mutate(endpoints.returned(order.id), {}).then(function (json) {
                    removeOrder(order.id, 'delivered');
                    if (json && json.order) {
                        upsertOrder('returned', json.order);
                    }
                    toastr.success('{{ __('Order marked returned') }} #' + order.id);
                }).catch(function () {
                    toastr.error('{{ __('Unable to mark returned right now.') }}');
                }).finally(function () {
                    returnedBtn.disabled = false;
                });
            });
        }

        function setButtonsDisabled(confirmBtn, cancelBtn, disabled) {
            confirmBtn.disabled = disabled;
            cancelBtn.disabled = disabled;
        }

        function removeOrder(id, listType) {
            if (!listType || !state[listType]) {
                // Fallback: remove from all lists
                ['received', 'shipped', 'delivered', 'returned'].forEach(function (key) {
                    state[key] = (state[key] || []).filter(function (order) {
                        return order.id !== id;
                    });
                });
                renderCounts();
                renderActive();
                return;
            }

            state[listType] = state[listType].filter(function (order) {
                return order.id !== id;
            });
            if (listType === 'shipped') {
                selectedShipped.delete(id);
            }
            renderCounts();
            renderActive();
        }

        function upsertOrder(listType, order) {
            if (!listType || !state[listType] || !order) {
                return;
            }
            state[listType] = (state[listType] || []).filter(function (o) {
                return o.id !== order.id;
            });
            state[listType].unshift(order);
            renderCounts();
            renderActive();
        }

        function updateBulkUI() {
            if (!bulkBar || activeTab !== 'shipped') return;

            const shippedOrders = state.shipped || [];
            const total = shippedOrders.length;
            const selectedCount = selectedShipped.size;

            if (bulkCount) bulkCount.textContent = '(' + selectedCount + ')';
            if (bulkDeliverBtn) bulkDeliverBtn.disabled = selectedCount === 0;

            if (bulkSelectAll) {
                bulkSelectAll.checked = total > 0 && selectedCount === total;
                bulkSelectAll.indeterminate = selectedCount > 0 && selectedCount < total;
            }
        }

        async function bulkDeliverSelected() {
            const ids = Array.from(selectedShipped);
            if (!ids.length) return;

            bulkDeliverBtn.disabled = true;

            const results = await Promise.allSettled(
                ids.map(function (id) {
                    return mutate(endpoints.deliver(id), {});
                })
            );

            let moved = 0;
            let failed = 0;

            results.forEach(function (res, idx) {
                const id = ids[idx];
                if (res.status === 'fulfilled' && res.value && res.value.order) {
                    // Move from shipped -> delivered in memory (no refresh)
                    state.shipped = (state.shipped || []).filter(function (o) { return o.id !== id; });
                    selectedShipped.delete(id);
                    state.delivered = (state.delivered || []).filter(function (o) { return o.id !== id; });
                    state.delivered.unshift(res.value.order);
                    moved++;
                } else {
                    failed++;
                }
            });

            renderCounts();
            renderActive();

            if (moved) toastr.success('{{ __('Order marked delivered') }} (' + moved + ')');
            if (failed) toastr.error('{{ __('Unable to mark delivered right now.') }} (' + failed + ')');
        }

        if (bulkSelectAll) {
            bulkSelectAll.addEventListener('change', function (e) {
                const all = !!e.target.checked;
                selectedShipped.clear();
                if (all) {
                    (state.shipped || []).forEach(function (o) { selectedShipped.add(o.id); });
                }
                renderActive();
            });
        }

        if (bulkDeliverBtn) {
            bulkDeliverBtn.addEventListener('click', function () {
                bulkDeliverSelected();
            });
        }

        function mutate(url, payload) {
            return fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf
                },
                body: JSON.stringify(payload)
            }).then(function (response) {
                if (!response.ok) {
                    throw new Error('Request failed');
                }
                return response.json();
            });
        }

        function fetchOrders() {
            setLoading(true);
            fetch(endpoints.index, {
                headers: { 'Accept': 'application/json' }
            }).then(function (response) {
                if (!response.ok) {
                    throw new Error('Request failed');
                }
                return response.json();
            }).then(function (json) {
                state.received = Array.isArray(json.received) ? json.received : [];
                state.shipped = Array.isArray(json.shipped) ? json.shipped : [];
                state.delivered = Array.isArray(json.delivered) ? json.delivered : [];
                state.returned = Array.isArray(json.returned) ? json.returned : [];
                renderCounts();
                renderActive();
            }).catch(function () {
                toastr.error('{{ __('Unable to load weekly orders.') }}');
            }).finally(function () {
                setLoading(false);
            });
        }

        function formatRecipientLabel(shipping) {
            const name = shipping.name || '{{ __('Customer') }}';
            const phone = shipping.phone || '';
            return phone ? name + ' - ' + phone : name;
        }

        function formatDateTime(isoString) {
            if (!isoString) {
                return '';
            }
            try {
                const date = new Date(isoString);
                return date.toLocaleString();
            } catch (error) {
                return isoString;
            }
        }

        function formatDateOnly(dateString) {
            if (!dateString) {
                return '';
            }
            try {
                const date = new Date(dateString + 'T00:00:00');
                return date.toLocaleDateString();
            } catch (error) {
                return dateString;
            }
        }

        setActiveTab('received');
        fetchOrders();

        // Auto-refresh every 30 s so status updates appear without manual reload
        var _pollTimer = setInterval(fetchOrders, 30000);
        document.addEventListener('visibilitychange', function () {
            if (document.hidden) {
                clearInterval(_pollTimer);
            } else {
                fetchOrders();
                _pollTimer = setInterval(fetchOrders, 30000);
            }
        });
    });
</script>
@endpush
