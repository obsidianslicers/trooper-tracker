<x-tabs>
    <x-tab :label="__('admin.Overview')"
           :target="route('admin.events.update', compact('event'))"
           :active="request()->routeIs('admin.events.update')" />
    <x-tab :label="__('admin.Shifts')"
           :target="route('admin.events.shifts', compact('event'))"
           :active="request()->routeIs('admin.events.shifts')" />
    <x-tab :label="__('admin.Charity')"
           :target="route('admin.events.charity', compact('event'))"
           :active="request()->routeIs('admin.events.charity')" />
    <x-tab :label="__('admin.Roster')"
           :target="route('admin.events.troopers', compact('event'))"
           :active="request()->routeIs('admin.events.troopers')" />
    <x-tab :label="__('admin.Administrative Uploads')"
           :target="route('admin.events.uploads', compact('event'))"
           :active="request()->routeIs('admin.events.uploads')" />
    <x-tab :label="__('admin.Mission Review')"
           :target="route('admin.events.mission-review', compact('event'))"
           :active="request()->routeIs('admin.events.mission-review')" />
</x-tabs>