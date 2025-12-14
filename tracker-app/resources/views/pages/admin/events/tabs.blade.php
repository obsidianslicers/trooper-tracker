<x-tabs>
  <x-tab :label="'Overview'"
         :target="route('admin.events.update',compact('event'))"
         :active="request()->routeIs('admin.events.update')" />
  <x-tab :label="'Shifts'"
         :target="route('admin.events.shifts',compact('event'))"
         :active="request()->routeIs('admin.events.shifts')" />
  <x-tab :label="'Roster'"
         :target="route('admin.events.troopers',compact('event'))"
         :active="request()->routeIs('admin.events.troopers')" />
</x-tabs>