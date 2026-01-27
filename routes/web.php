<?php

use Illuminate\Support\Facades\Route;

Route::get('/healthz', fn () => response('ok', 200));
