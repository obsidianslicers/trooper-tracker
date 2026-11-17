<?php

// Date and time patterns (PHP date() syntax), keyed by the English pattern used in
// $date->localFormat('M j, Y'). Another language maps each to its own order, e.g.
// 'M j, Y' => 'j. M Y' and 'g:ia' => 'H:i'. Month and day names are translated by Carbon.

return [
    'D' => 'D',
    'D - M d, Y' => 'D - M d, Y',
    'D - M d, Y g:ia' => 'D - M d, Y g:ia',
    'D M d, Y' => 'D M d, Y',
    'D, M j, Y' => 'D, M j, Y',
    'F Y' => 'F Y',
    'F j, Y' => 'F j, Y',
    'F j, Y g:i A' => 'F j, Y g:i A',
    'M d, Y' => 'M d, Y',
    'M j, Y' => 'M j, Y',
    'M j, Y g:i A' => 'M j, Y g:i A',
    'M j, Y g:ia' => 'M j, Y g:ia',
    'g' => 'g',
    'g:i A' => 'g:i A',
    'g:ia' => 'g:ia',
    'ga' => 'ga',
    'j' => 'j',
    'l, M j' => 'l, M j',
    'm/d' => 'm/d',
    'm/d - g:ia' => 'm/d - g:ia',
];
