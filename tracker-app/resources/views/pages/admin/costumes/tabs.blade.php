<x-tabs>
    <x-tab :label="'Update'"
           :target="route('admin.costumes.update', compact('costume'))"
           :active="request()->routeIs('admin.costumes.update')" />
</x-tabs>