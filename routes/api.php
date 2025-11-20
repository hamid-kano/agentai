<?php

use App\Http\Controllers\AIAgentController;
use Illuminate\Support\Facades\Route;

Route::post('/ai', [AIAgentController::class, 'handle']);
