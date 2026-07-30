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
    <x-tab :label="'Club Memberships'"
           :target="route('account.club-memberships')"
           :active="request()->routeIs('account.club-memberships')" />
    <x-tab :label="'Friends'"
           :target="route('account.friends')"
           :active="request()->routeIs('account.friends')" />
    @if(Auth::user()->minors()->exists())
        <x-tab :label="'Cadets'"
               :target="route('account.minors')"
               :active="request()->routeIs('account.minors')" />
    @endif
</x-tabs>