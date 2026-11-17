<!--
    A shift being added. It saves once it has a date and both times, then shows as
    an ordinary shift.
-->
<script lang="ts">
    import { t } from "$lib/i18n";
    import InputError from "$lib/components/form/InputError.svelte";
    import SaveStatus from "$lib/components/form/SaveStatus.svelte";
    import { untrack } from "svelte";
    import { ShiftViewModel } from "../models";
    import DateCell from "./DateCell.svelte";

    interface Props {
        event_id: number;
        draft: { key: number; date: string };
        onsaved: () => void;
    }
    let { event_id, draft, onsaved }: Props = $props();

    const vm = new ShiftViewModel(
        untrack(() => event_id),
        untrack(() => draft.key),
        untrack(() => ({ date: draft.date, starts_at: "", ends_at: "" })),
        untrack(() => onsaved),
    );
    const name = (field: string) => `shifts[${vm.shift_id}][${field}]`;
</script>

<tr>
    <td><span class="badge text-bg-secondary">{t("New")}</span></td>
    <td>
        <DateCell
            name={name("date")}
            label={t("Date")}
            bind:value={vm.form.date}
            invalid={!!vm.error("date")}
            onchange={vm.change}
        />
        <InputError errors={vm.error("date")} />
    </td>
    <td>
        <input
            type="time"
            class="form-control form-control-sm"
            aria-label={t("Starts At")}
            name={name("starts_at")}
            bind:value={vm.form.starts_at}
            onchange={vm.change}
        />
        <InputError errors={vm.error("starts_at")} />
    </td>
    <td>
        <input
            type="time"
            class="form-control form-control-sm"
            aria-label={t("Ends At")}
            name={name("ends_at")}
            bind:value={vm.form.ends_at}
            onchange={vm.change}
        />
        <InputError errors={vm.error("ends_at")} />
    </td>
    <td></td>
    <td><SaveStatus state={vm.save_state} /></td>
</tr>
