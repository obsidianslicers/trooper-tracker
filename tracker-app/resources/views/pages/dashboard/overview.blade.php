<!-- Profile Card -->
<x-card :label="'Overview'">

    <div class="row align-items-center">

        <div class="col-md-8 col-lg-6 align-self-start">
            <h4 class="mb-2 text-upper">
                {{ $trooper->name }}
            </h4>
            <x-table>
                <tr>
                    <th>Member Since:</th>
                    <td class="text-end">
                        #TODO
                    </td>
                </tr>
                @foreach ($metrics as $metric)
                    <tr>
                        <th>{{ $metric->type->toTitle() }}:</th>
                        <td class="text-end">
                            <x-number-format :value="$metric->value" />
                        </td>
                    </tr>
                @endforeach
            </x-table>

        </div>

        <div class="col-md-4 col-lg-3 text-center text-md-start align-self-start mb-3">
            {{--
            @if(isset($image_url))
            <img src="#"
                 class="img-fluid rounded mb-3"
                 alt="Profile Picture">
            @endif
            <div>
                <a href="#"
                   class="btn btn-outline-light w-100">
                    Boards Profile
                    <span class="fa fa-fw fa-external-link"></span>
                </a>
            </div>
            --}}
        </div>

    </div>
</x-card>