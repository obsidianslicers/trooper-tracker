<!-- Meta Data -->
<meta charset="UTF-8" />
<meta name="viewport"
      content="width=device-width, initial-scale=1.0" />
<meta http-equiv="X-UA-Compatible"
      content="ie=edge" />
<meta name="csrf-token"
      content="{{ csrf_token() }}">
<script>window.__authState = {{ auth()->check() ? 'true' : 'false' }};</script>

<link rel="icon"
      href="{{ url('img/favicon.png') }}"
      type="image/x-icon">
<link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css" />
<link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/bootswatch/5.2.3/darkly/bootstrap.min.css" />
@vite(['resources/css/app.scss'])
@if(config('services.google.maps_api_key'))
    <script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google.maps_api_key') }}&libraries=marker">
    </script>
@endif