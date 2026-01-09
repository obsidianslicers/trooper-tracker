<x-tabs>
    <x-tab :label="'Update'"
           :target="route('admin.organizations.update', compact('organization'))"
           :active="request()->routeIs('admin.organizations.update')" />
    @if($organization->type == \App\Enums\OrganizationType::ORGANIZATION)
        <x-tab :label="'Costumes'"
               :target="route('admin.organizations.costumes', compact('organization'))"
               :active="request()->routeIs('admin.organizations.costumes')" />
    @endif
</x-tabs>