<?php

use Illuminate\Support\Facades\Route;

Route::get('/ping', fn () => 'pong from '.config('plugins.hello.name'))->name('ping');
