<span class="badge d-inline-flex align-items-center gap-2 {{ $milestone->type == \App\Enums\AchievementType::TROOPED_501 ? 'text-success' : '' }}">
    <i class="fa-solid fa-fw {{ $milestone->type->toIcon() }}"></i>
    {{ $milestone->type->toTitle() }}
</span>