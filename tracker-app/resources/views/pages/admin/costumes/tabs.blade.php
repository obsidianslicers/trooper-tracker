<x-tabs>
    <x-tab :label="'Update'"
           :target="route('admin.costumes.update', compact('costume'))"
           :active="request()->routeIs('admin.costumes.update')" />
    @unless($costume->countsAsHandler())
        <x-tab :label="'Delete'"
               :target="route('admin.costumes.delete', compact('costume'))"
               :active="request()->routeIs('admin.costumes.delete')" />
    @endunless
</x-tabs>