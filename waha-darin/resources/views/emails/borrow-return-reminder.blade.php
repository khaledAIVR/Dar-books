@component('mail::message')
Hello {{ optional($borrowOrder->user)->name ?? 'Customer' }},

This is a reminder that your borrow period for order **#{{ $borrowOrder->id }}** ends on **{{ $borrowOrder->end_date }}**.

Please make sure you return the book(s) on time. If you ship the return, please enter your return shipment number in your orders page.

@php
    $ordersUrl = rtrim(config('app.client_url', config('app.url')), '/') . '/orders';
@endphp
@component('mail::button', ['url' => $ordersUrl])
Open my orders
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent

