<p>{{ $subscription->user->name ?? '' }}</p>
<p>{{ $subscription->user->email ?? '' }}</p>

<p>Your subscription is activated.</p>

<p><strong>Start:</strong> {{ optional($subscription->start)->timezone('Africa/Cairo')->format('Y-m-d H:i:s') }}</p>
<p><strong>End:</strong> {{ optional($subscription->end)->timezone('Africa/Cairo')->format('Y-m-d H:i:s') }}</p>
