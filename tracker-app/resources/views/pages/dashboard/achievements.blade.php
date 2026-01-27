<x-card :label="'Trooper Achievements'">
    <div class="container">
        <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-3">

            @foreach ($achievements as $achievement)
                @include('pages.dashboard.achievement', ['icon' => $achievement->icon, 'title' => $achievement->title,])
            @endforeach

        </div>
    </div>
</x-card>