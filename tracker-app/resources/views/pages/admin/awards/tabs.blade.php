<x-tabs>
    <x-tab :label="__('admin.Overview')"
           :target="route('admin.awards.update', compact('award'))"
           :active="request()->routeIs('admin.awards.update')" />
    <x-tab :label="__('admin.Troopers Awarded')"
           :target="route('admin.awards.list-troopers', compact('award'))"
           :active="request()->routeIs('admin.awards.list-troopers')" />
</x-tabs>