<script lang="ts">
    import SubmitButtonContainer from "$lib/components/form/SubmitButtonContainer.svelte";
    import SubmitButton from "$lib/components/ui/buttons/SubmitButton.svelte";
    import { MembershipsViewModel } from "$lib/domains/account";

    interface Props {
        vm: MembershipsViewModel;
    }
    let { vm }: Props = $props();
</script>

<h6 class="mb-3">Organization Join Requests</h6>

<div>
    <form onsubmit={(e) => vm.addTrooperRequest(e)}>
        <p class="text-muted mb-3">
            Select an organization in which you are already a member of. A
            moderator will verify your membership before it is added to your
            profile.

            {#if vm.is_visitor}
                Choose the most specific unit you are visiting.
            {:else}
                Choose the most specific unit you belong to, typically based on
                your geographic location.
            {/if}

            You can view any organization's events without being assigned. Each
            organization allows only one membership assignment. If you select a
            different unit within the same organization, your previous
            assignment or pending request will be updated.
        </p>
        <!-- 
        <x-input-container>
            <x-label>Club / Organization:</x-label>
            <select
                name="organization_id"
                id="organization_id"
                class="form-select @error('organization_id') is-invalid @enderror"
            >
                <option value="">— Select an organization —</option>
                @foreach($grouped as $root_id => $orgs) @php($root_org = $orgs->firstWhere('depth',
                0)) @php($child_orgs = $orgs->where('depth', '>', 0)->values()) @if($root_org)
                <option value="$root_org->id">$root_org->name</option>
                @endif @if($child_orgs->isNotEmpty())
                <optgroup label="$root_names[$root_id] ?? 'Other'">
                    @foreach($child_orgs as $org)
                    <option value="$org->id"
                        >str_repeat('— ', $org->depth)$org->name</option
                    >
                    @endforeach
                </optgroup>
                @endif @endforeach
            </select>
            <x-input-error :property="'organization_id'" />
        </x-input-container>

        <div class="mt-3 mb-3 p-3 bg-body-tertiary rounded border">
            <p class="text-muted small mb-0">
                <i class="fa fa-fw fa-circle-info"></i>
                Requesting access to all levels:
            </p>
            <div class="mt-3">
                display selected tree/chain if something is selected
            </div>
        </div>

        <div>
            <p>show if selectedId (also focus on isIdentifierRequired)</p>
            <x-input-container>
                <input
                    type="text"
                    name="identifier"
                    id="identifier"
                    class="form-control @error('identifier') is-invalid @enderror"
                    maxlength="64"
                    value="old('identifier')"
                />
                <x-input-help>
                    Enter your member ID for this organization if you have one.
                    Leave blank if unknown.
                </x-input-help>
                <x-input-error :property="'identifier'" />
            </x-input-container>
        </div> -->

        <SubmitButtonContainer>
            <SubmitButton
                label="Request Access"
                submitting={vm.form.processing}
                disabled={!vm.dirty}
            />
        </SubmitButtonContainer>
    </form>
</div>

<pre>
    orgs: Js::from($available_clubs_data),
    orgMap: Js::from($ancestor_map_data),
    selectedId: @js((string) old('organization_id', '')),
    get selectedOrg() 
        const id = parseInt(this.selectedId);
        return this.orgs.find(o => o.id === id) ?? null;
    
    get selectedChain() 
        if (!this.selectedOrg?.node_path) return [];
        return this.selectedOrg.node_path
            .split(':')
            .filter(id => id !== '')
            .map(id => this.orgMap[parseInt(id)])
            .filter(Boolean);
    
    get isIdentifierRequired() 
        if (!this.selectedOrg?.identifier_validation) return false;
        return this.selectedOrg.identifier_validation.split('|').includes('required');
                        
</pre>
