<x-tabs>
  <x-tab :label="'Overview'"
         :target="route('admin.awards.update',compact('award'))"
         :active="request()->routeIs('admin.awards.update')" />
  <x-tab :label="'Troopers Awarded'"
         :target="route('admin.awards.list-troopers',compact('award'))"
         :active="request()->routeIs('admin.awards.list-troopers')" />
</x-tabs>