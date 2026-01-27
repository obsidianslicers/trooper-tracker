<x-card :label="'Trooper Milestones'">
    <div class="container">
        <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-3">

            @forelse ($milestones as $milestone)
                @include('pages.dashboard.achievement', compact('milestone'))
            @empty
                <p>No milestones achieved yet.</p>
            @endforelse

        </div>
    </div>
</x-card>