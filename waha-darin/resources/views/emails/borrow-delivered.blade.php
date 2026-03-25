@component('mail::message')
Hello {{ optional($borrowOrder->user)->name ?? 'Customer' }},

Your borrow order **#{{ $borrowOrder->id }}** has been delivered.

Please return the book(s) before **{{ $borrowOrder->end_date }}**.

@php
    $ordersUrl = rtrim(config('app.client_url', config('app.url')), '/') . '/orders';
@endphp
@component('mail::button', ['url' => $ordersUrl])
Open my orders
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent

