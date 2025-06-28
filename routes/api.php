<?php

use Fullstack\Inbounder\Controllers\InboundMailController;
use Illuminate\Support\Facades\Route;

Route::post('/', InboundMailController::class)->name('inbounder.mailgun.webhook');
