@if($count == 1)
    @include('partials.notice', ['notice' => $notice])
@elseif($count > 1)
    <div class="alert alert-info alert-dismissible fade show mt-2">
        <p>
            <b>
                <i class="fa fa-fw fa-solid fa-circle-info"></i>
            </b>
            <a href="{{ route('account.notices') }}">
                You have {{ $count }} unread notices.
            </a>
        </p>
    </div>
@endif