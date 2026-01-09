<x-tabs>
    <x-tab :label="'Profile'"
           :target="route('account.profile')"
           :active="request()->routeIs('account.profile')" />
    <x-tab :label="'Notifications'"
           :target="route('account.notifications')"
           :active="request()->routeIs('account.notifications')" />
    @if(!Auth::user()->is_handler)
        <x-tab :label="'Costumes'"
               :target="route('account.costumes')"
               :active="request()->routeIs('account.costumes')" />
    @endif
</x-tabs>