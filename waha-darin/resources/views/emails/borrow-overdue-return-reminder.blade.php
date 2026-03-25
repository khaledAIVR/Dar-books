@component('mail::message')
Hello {{ optional($borrowOrder->user)->name ?? 'Customer' }},

Your borrow order **#{{ $borrowOrder->id }}** is now overdue (it ended on **{{ $borrowOrder->end_date }}**).

Please return the book(s) as soon as possible. After you ship the return, **enter your return shipment number** in your orders page so our team can track it.

@php
    $ordersUrl = rtrim(config('app.client_url', config('app.url')), '/') . '/orders';
@endphp
@component('mail::button', ['url' => $ordersUrl])
Open my orders
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent

