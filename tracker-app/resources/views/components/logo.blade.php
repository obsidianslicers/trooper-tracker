@props(['storage_path', 'default_path', 'width'=>null, 'height'=>null])
<img src="{{ map_image_url($storage_path, $default_path) }}"
     alt="Logo"
     class="img-fluid"
     {{$width?"width=$width":""}}
     {{$height?"height=$height":""}} />