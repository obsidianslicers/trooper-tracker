@props(['deployment_profile'])

<div class="row g-3 mb-4">
    <x-service-record.profile-card :label="'Trooper Rank'">
        <x-number-format :value="$deployment_profile['trooper']->getTrooperAchievement(App\Enums\AchievementType::TROOPER_RANK)->value" />
    </x-service-record.profile-card>
    <x-service-record.profile-card :label="'Total Shifts'">
        <x-number-format :value="$deployment_profile['trooper']->getTrooperAchievement(App\Enums\AchievementType::TROOPER_SHIFTS)->value" />
    </x-service-record.profile-card>
    <x-service-record.profile-card :label="'Service Hours'">
        <x-number-format :value="$deployment_profile['trooper']->getTrooperAchievement(App\Enums\AchievementType::VOLUNTEER_HOURS)->value" />
    </x-service-record.profile-card>
    <x-service-record.profile-card :label="'Direct Funds'">
        $ <x-number-format :value="$deployment_profile['trooper']->getTrooperAchievement(App\Enums\AchievementType::DIRECT_FUNDS)->value" />
    </x-service-record.profile-card>
    <x-service-record.profile-card :label="'Indirect Funds'">
        $ <x-number-format :value="$deployment_profile['trooper']->getTrooperAchievement(App\Enums\AchievementType::INDIRECT_FUNDS)->value" />
    </x-service-record.profile-card>

    {{--
    <div class="d-flex justify-content-between align-items-center px-2 mb-3">
        <span class="small text-muted">Member Since: <strong>{{ $record['member_since'] }}</strong></span>
        <span class="badge bg-secondary text-uppercase">Active Duty</span>
    </div>
    --}}
</div>