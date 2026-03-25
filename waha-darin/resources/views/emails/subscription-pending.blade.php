<p>{{ $subscription->user->name ?? '' }}</p>
<p>{{ $subscription->user->email ?? '' }}</p>

<p>Your subscription is pending.</p>
<p>We will activate it after we review and confirm your bank transfer.</p>

@if(!empty($subscription->transaction_amount))
    <p><strong>Amount:</strong> {{ $subscription->transaction_amount }}</p>
@endif
@if(!empty($subscription->transaction_date))
    <p><strong>Transfer date:</strong> {{ $subscription->transaction_date }}</p>
@endif

