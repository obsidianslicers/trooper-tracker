@if(count($changes) > 0)
    <p>
        @foreach($changes as $change)
            {{ $change }}<br>
        @endforeach
    </p>
@endif
