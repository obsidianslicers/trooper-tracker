<script lang="ts">
    let { value }: { value: boolean } = $props();
</script>

<i class={["fa fa-fw me-2", value ? "fa-check text-success" : "fa-times text-danger"]}></i>
