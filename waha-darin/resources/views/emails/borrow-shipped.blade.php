@component('mail::message')
Hello {{ optional($borrowOrder->user)->name ?? 'Customer' }},

Your borrow order **#{{ $borrowOrder->id }}** has been shipped.

@if ($borrowOrder->shipment_number)
Shipment number: **{{ $borrowOrder->shipment_number }}**
@endif

@if ($borrowOrder->tracking_url)
@component('mail::button', ['url' => $borrowOrder->tracking_url])
Track shipment
@endcomponent
@endif

Borrow period: **{{ $borrowOrder->start_date }}** to **{{ $borrowOrder->end_date }}**.

Thanks,<br>
{{ config('app.name') }}
@endcomponent

