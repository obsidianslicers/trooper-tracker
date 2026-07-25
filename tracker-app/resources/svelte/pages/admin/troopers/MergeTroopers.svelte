<script lang="ts">
    import InputContainer from "$lib/components/form/InputContainer.svelte";
    import TrooperPicker from "$lib/components/TrooperPicker.svelte";
    import SlimCard from "$lib/components/ui/SlimCard.svelte";

    type Trooper = {
        id: string | number;
        [key: string]: unknown;
    };

    let selected_trooper = $state<Trooper | null>(null);
</script>

<SlimCard>
    <InputContainer>
        <TrooperPicker bind:selected={selected_trooper} label="Trooper" />
    </InputContainer>
</SlimCard>
