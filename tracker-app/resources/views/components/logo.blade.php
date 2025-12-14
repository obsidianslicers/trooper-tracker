@props(['storage_path', 'default_path', 'width' => null, 'height' => null, 'fluid' => false])
<img src="{{ map_image_url($storage_path, $default_path) }}"
     alt="Logo"
     {{$width ? "width=$width" : ""}}
     {{$height ? "height=$height" : ""}}
     {{$fluid ? 'class=img-fluid' : ''}} />