@extends('layouts.base')

@section('page-title', 'Trooper Authority')

@section('content')

<x-slim-container>

  <x-card>

    <form method="POST"
          novalidate="novalidate">
      @csrf

      <x-input-container>
        <x-label>
          Trooper:
        </x-label>
        <x-input-text :property="'trooper_name'"
                      :disabled="true"
                      :value="$trooper->name . ' (' . $trooper->username . ')'" />
      </x-input-container>

      <x-input-container>
        <x-label>
          Role:
        </x-label>
        <x-input-select :property="'membership_role'"
                        :options="\App\Enums\MembershipRole::toArray()"
                        :value="$trooper->membership_role->value" />
        <x-input-help>
          If selected as {{ \App\Enums\MembershipRole::ADMINISTRATOR->name }}, they have full control within the Command Staff.
          If selected as {{ \App\Enums\MembershipRole::MODERATOR->name }}, they have full control over their
          assigned organizations as {{ \App\Enums\MembershipRole::MODERATOR->name }}. A {{ \App\Enums\MembershipRole::MEMBER->name }},
          can sign up for events provided they are {{ \App\Enums\MembershipStatus::ACTIVE->name }},
        </x-input-help>
      </x-input-container>

      <x-submit-container>
        <x-submit-button>
          Save
        </x-submit-button>
        <x-link-button-cancel :url="route('admin.troopers.list')" />
      </x-submit-container>

      <x-table>
        <thead>
          <tr>
            <th>Assignment</th>
            <th>Moderator</th>
            <th class="text-center">Notify</th>
            <th class="text-center">Member</th>
          </tr>
        </thead>
        @php($selected_map = [])
        @foreach ($organization_authorities as $organization)

        @php($trooper_assignment = $organization->trooper_assignments->first())
        @php($parent_selected = $selected_map[$organization->parent_id] ?? false)

        <tr data-id="{{ $organization->id }}"
            data-parent-id="{{ $organization->parent_id }}">
          <td>
            @foreach(range(0, $organization->depth - 1) as $i)
            <i class="fa fa-fw"></i>
            @endforeach
            <label for="{{'moderators.'.$organization->id.'.selected'}}">
              {{ $organization->name }}
            </label>
          </td>
          <td class="cascade">
            @if($organization->type != \App\Enums\OrganizationType::ORGANIZATION)
            <x-input-checkbox :property="'moderators.'.$organization->id.'.selected'"
                              :checked="$parent_selected || ($trooper_assignment->moderator ?? false)"
                              :disabled="$parent_selected && $organization->type == \App\Enums\OrganizationType::UNIT" />
            @endif
          </td>
          <td class="text-center">
            <x-yes-no :value="$trooper_assignment->notify ?? false" />
          </td>
          <td class="text-center">
            <x-yes-no :value="$trooper_assignment->member ?? false" />
          </td>
        </tr>
        @php($selected_map[$organization->id] = $trooper_assignment->moderator ?? false)
        @endforeach
      </x-table>

      <x-submit-container>
        <x-submit-button>
          Save
        </x-submit-button>
        <x-link-button-cancel :url="route('admin.troopers.list')" />
      </x-submit-container>

    </form>

  </x-card>

</x-slim-container>

@endsection

@section('page-script')
<script type="text/javascript">
  document.addEventListener('DOMContentLoaded', () => {
    function bindCascadeCheckboxes() {
      document.querySelectorAll('td.cascade input[type="checkbox"]').forEach(cb => {
        cb.addEventListener('change', function () {
          const isChecked = this.checked;
          const row = this.closest('tr');
          const id = row.dataset.id;

          // Cascade down: find all rows with data-parent = this row's id
          const children = document.querySelectorAll(`tr[data-parent-id="${id}"] input[type="checkbox"]`);
          children.forEach(childCb => {
            childCb.checked = isChecked;

            if (isChecked) {
              childCb.disabled = true;   // disable child if parent is checked
            } else {
              childCb.disabled = false;  // re-enable child if parent is unchecked
            }

            // recursively trigger change if you want deeper cascade
            childCb.dispatchEvent(new Event('change'));
          });
        });
      });
    }

    bindCascadeCheckboxes();
    document.body.addEventListener('htmx:afterSettle', bindCascadeCheckboxes);
  });
</script>
@endsection