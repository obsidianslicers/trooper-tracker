<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get('/ping', fn () => 'pong from '.config('plugins.hello.name'))->name('ping');
