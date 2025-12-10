<div id="organization-logo-container">

  <x-transmission-bar :id="'image'" />

  <form class="m-2"
        hx-post="{{ route('admin.organizations.update-image', compact('organization')) }}"
        hx-swap="outerHTML"
        hx-trigger="submit"
        hx-select="#organization-logo-container"
        hx-target="#organization-logo-container"
        hx-indicator="#transmission-bar-image"
        enctype="multipart/form-data">

    <img src="{{ map_image_url($organization->image_path_lg, 'img/icons/organization-128x128.png') }}"
         alt="Organization Logo"
         class="img-fluid m-3 pointer"
         width="128"
         height="128"
         onclick="document.getElementById('logo-input').click();" />

    <input type="file"
           name="logo"
           id="logo-input"
           class="d-none"
           accept="image/*"
           onchange="this.form.requestSubmit();">
    <p class="form-help text-muted">
      Click to Replace Logo
    </p>
  </form>

</div>