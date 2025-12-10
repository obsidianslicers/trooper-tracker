<x-tabs>
  <x-tab :label="'Profile'"
         :target="route('admin.troopers.profile',compact('trooper'))"
         :active="request()->routeIs('admin.troopers.profile')" />
  @if(Auth::user()->isAdministrator())
  <x-tab :label="'Authority'"
         :target="route('admin.troopers.authority',compact('trooper'))"
         :active="request()->routeIs('admin.troopers.authority')" />
  @endif
  <x-tab :label="'Memberships'"
         :target="route('admin.troopers.membership',compact('trooper'))"
         :active="request()->routeIs('admin.troopers.membership')" />
</x-tabs>