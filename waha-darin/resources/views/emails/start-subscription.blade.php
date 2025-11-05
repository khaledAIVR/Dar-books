<p>{{$subscription->user->email}}</p>
<p>{{$subscription->user->name}}</p>
<p>{{\Carbon\Carbon::parse($borrowOrder->start)
        ->setTimezone(new DateTimeZone("africa/cairo"))->format("Y-m-d H:i:s")}}</p>
<p>{{$subscription->end}}</p>
