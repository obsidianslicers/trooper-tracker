<!--
    One report on the Reports page; the whole card opens it.
-->
<script lang="ts">
    interface Props {
        label: string;
        icon: string;
        href: string;
        description: string;
    }
    let { label, icon, href, description }: Props = $props();
</script>

<div class="col">
    <div class="card h-100 position-relative">
        <div class="card-header">
            <i class="text-white fa fa-fw {icon} me-2"></i>
            <a {href} class="stretched-link text-reset text-decoration-none">{label}</a>
        </div>
        <div class="card-body">
            <p class="card-text">{description}</p>
        </div>
    </div>
</div>
