<x-tabs>
  <x-tab :label="'Profile'"
         :target="route('account.profile')"
         :active="request()->routeIs('account.profile')" />
  <x-tab :label="'Notifications'"
         :target="route('account.notifications')"
         :active="request()->routeIs('account.notifications')" />
  <x-tab :label="'Costumes'"
         :target="route('account.costumes')"
         :active="request()->routeIs('account.costumes')" />
</x-tabs>