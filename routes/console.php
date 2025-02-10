<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Definir comandos Artisan personalizados
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Agendar o comando ixc:contrato:handle para ser executado a cada 2 minutos
Schedule::command('ixc:contrato:handle')->everyTwoMinutes();
