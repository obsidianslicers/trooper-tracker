@if($event->charity_name)
    <div class="mt-3">
        <x-section-title>Charity</x-section-title>
        <p class="mb-1"><strong>{{ $event->charity_name }}</strong></p>
        <p class="mb-1">Direct Funds: ${{ number_format($event->charity_direct_funds) }}</p>
        <p class="mb-1">Indirect Funds: ${{ number_format($event->charity_indirect_funds) }}</p>
        <p class="mb-1">Hours: {{ $event->charity_hours }}</p>
    </div>
@endif