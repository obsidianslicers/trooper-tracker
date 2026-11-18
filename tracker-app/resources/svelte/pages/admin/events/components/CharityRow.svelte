<script lang="ts">
    import { t } from "$lib/i18n";
    import InputError from "$lib/components/form/InputError.svelte";
    import SaveStatus from "$lib/components/form/SaveStatus.svelte";
    import { untrack } from "svelte";
    import { CharityViewModel, type EventShiftCharity } from "../models";

    interface Props {
        event_id: number;
        shift: EventShiftCharity;
    }
    let { event_id, shift }: Props = $props();

    const vm = new CharityViewModel(
        untrack(() => event_id),
        untrack(() => shift),
    );
    const id = (field: string) => `charity-${shift.id}-${field}`;
    const name = (field: string) => `shifts[${shift.id}][${field}]`;

    const grow = (e: Event) => {
        const textarea = e.currentTarget as HTMLTextAreaElement;
        textarea.style.height = "auto";
        textarea.style.height = `${textarea.scrollHeight}px`;
    };
</script>

<div class="d-flex justify-content-between mb-2">
    <div class="text-muted small fw-semibold">{shift.date} &mdash; {shift.time}</div>
    <SaveStatus state={vm.save_state} />
</div>

<div class="d-flex flex-wrap gap-2 align-items-start">
    <div class="flex-grow-1">
        <label class="form-label small text-muted mb-1" for={id("name")}>{t("Charity")}</label>
        <input id={id("name")} type="text" class="form-control form-control-sm" name={name("charity_name")}
            bind:value={vm.form.charity_name} onchange={vm.save} />
        <InputError errors={vm.error("charity_name")} />
    </div>
    <div style="width:75px">
        <label class="form-label small text-muted mb-1" for={id("hours")}>{t("Hours")}</label>
        <input id={id("hours")} type="text" inputmode="numeric" class="form-control form-control-sm"
            name={name("charity_hours")} placeholder={String(shift.shift_hours)}
            bind:value={vm.form.charity_hours} onchange={vm.save} />
        <InputError errors={vm.error("charity_hours")} />
    </div>
    <div style="width:90px">
        <label class="form-label small text-muted mb-1" for={id("direct")}>{t("Direct $")}</label>
        <input id={id("direct")} type="text" inputmode="decimal" class="form-control form-control-sm"
            name={name("charity_direct_funds")}
            bind:value={vm.form.charity_direct_funds} onchange={vm.save} />
    </div>
    <div style="width:90px">
        <label class="form-label small text-muted mb-1" for={id("indirect")}>{t("Indirect $")}</label>
        <input id={id("indirect")} type="text" inputmode="decimal" class="form-control form-control-sm"
            name={name("charity_indirect_funds")}
            bind:value={vm.form.charity_indirect_funds} onchange={vm.save} />
    </div>
    <div class="flex-grow-1">
        <label class="form-label small text-muted mb-1" for={id("notes")}>{t("Notes")}</label>
        <textarea id={id("notes")} rows="1" class="form-control form-control-sm" style="resize:none;overflow:hidden"
            name={name("charity_notes")}
            bind:value={vm.form.charity_notes} oninput={grow} onchange={vm.save}></textarea>
    </div>
</div>
<InputError errors={vm.error("charity_direct_funds")} />
<InputError errors={vm.error("charity_indirect_funds")} />
