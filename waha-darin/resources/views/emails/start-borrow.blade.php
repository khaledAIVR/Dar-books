@component('mail::message')
Hello {{ optional($borrowOrder->user)->name ?? 'Customer' }},

Your borrow order **#{{ $borrowOrder->id ?? '' }}** is starting now.

Borrow period: **{{ $borrowOrder->start_date ?? '' }}** to **{{ $borrowOrder->end_date ?? '' }}**.

@php
    $ordersUrl = rtrim(config('app.client_url', config('app.url')), '/') . '/orders';
@endphp
@component('mail::button', ['url' => $ordersUrl])
Open my orders
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
