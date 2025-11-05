<p>{{$borrowOrder->user->email}}</p>
<p>{{$borrowOrder->user->name}}</p>
<p>{{\Carbon\Carbon::parse($borrowOrder->start_data)
        ->setTimezone(new DateTimeZone("africa/cairo"))->format("Y-m-d H:i:s")}}</p>
<p>{{$borrowOrder->end_date}}</p>
