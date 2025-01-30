<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    protected $table = 'contracts';

    protected $fillable = [
        'contract_id',
        'status',
        'cliente_id'
    ];
}
