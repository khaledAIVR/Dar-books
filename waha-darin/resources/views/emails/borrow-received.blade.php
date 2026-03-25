@component('mail::message')
Hello {{ optional($borrowOrder->user)->name ?? 'Customer' }},

We received your borrow order **#{{ $borrowOrder->id }}**.

Borrow period: **{{ $borrowOrder->start_date }}** to **{{ $borrowOrder->end_date }}**.

@php
    $books = $borrowOrder->relationLoaded('books') ? $borrowOrder->books : (method_exists($borrowOrder, 'books') ? $borrowOrder->books()->get() : collect());
@endphp
@if ($books && $books->count())
**Books:**
@foreach ($books as $book)
- {{ $book->title ?? 'Untitled' }}
@endforeach
@endif

We will email you again once your order is shipped.

Thanks,<br>
{{ config('app.name') }}
@endcomponent

