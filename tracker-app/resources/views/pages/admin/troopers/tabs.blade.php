<x-tabs>
    <x-tab :label="'Profile'"
           :target="route('admin.troopers.profile', compact('trooper'))"
           :active="request()->routeIs('admin.troopers.profile')" />
    @if(Auth::user()->is_administrator)
        <x-tab :label="'Authority'"
               :target="route('admin.troopers.authority', compact('trooper'))"
               :active="request()->routeIs('admin.troopers.authority')" />
    @endif
    <x-tab :label="'Memberships'"
           :target="route('admin.troopers.membership', compact('trooper'))"
           :active="request()->routeIs('admin.troopers.membership')" />
    @if($trooper->has_guardian_required_membership)
        <x-tab :label="'Guardian'"
               :target="route('admin.troopers.guardian', compact('trooper'))"
               :active="request()->routeIs('admin.troopers.guardian')" />
    @endif
    <x-tab :label="'Costumes'"
           :target="route('admin.troopers.costumes', compact('trooper'))"
           :active="request()->routeIs('admin.troopers.costumes')" />
    <x-tab :label="'Events'"
           :target="route('admin.troopers.events', compact('trooper'))"
           :active="request()->routeIs('admin.troopers.events')" />
    <x-tab :label="'Changes'"
           :target="route('admin.troopers.changes', compact('trooper'))"
           :active="request()->routeIs('admin.troopers.changes')" />
</x-tabs>