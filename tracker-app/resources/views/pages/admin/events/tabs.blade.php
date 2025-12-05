<x-tabs>
  <x-tab :label="'Overview'"
         :target="route('admin.events.update',['event'=>$event])"
         :active="request()->routeIs('admin.events.update')" />
  <x-tab :label="'Shifts'" />
  <x-tab :label="'Venue'"
         :target="route('admin.events.venue',['event'=>$event])"
         :active="request()->routeIs('admin.events.venue')" />
  @if($event->limit_organizations)
  <x-tab :label="'Organization Limits'"
         :target="route('admin.events.organizations',['event'=>$event])"
         :active="request()->routeIs('admin.events.organizations')" />
  @endif
  <x-tab :label="'Roster'" />
</x-tabs>