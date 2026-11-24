<script lang="ts">
    let { message }: { message: string } = $props();
</script>

<div class="card bg-dark border-secondary text-center py-5 my-4">
    <div class="card-body">
        <p class="text-muted mb-0">{message}</p>
    </div>
</div>
