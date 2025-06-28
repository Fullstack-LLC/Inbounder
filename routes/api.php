<?php

use Illuminate\Support\Facades\Route;
use Fullstack\Inbounder\Controllers\InboundMailController;

Route::post('/', InboundMailController::class)->name('inbounder.mailgun.webhook');
