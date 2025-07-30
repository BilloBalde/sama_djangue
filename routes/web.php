<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

Route::get('/paytech/success', function () {
    return "Paiement réussi !";
});


require __DIR__.'/auth.php';
