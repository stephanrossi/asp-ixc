<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $table = 'clientes';

    protected $fillable = [
        'cliente_id',
        'razao',
        'cnpj_cpf',
        'email'
    ];
}
