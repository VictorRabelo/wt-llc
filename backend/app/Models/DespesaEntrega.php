<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class DespesaEntrega extends Model
{
    protected $table = 'despesa_entregas';
    protected $primaryKey = 'id_despesaEntrega';

    public $timestamps = true;
    
    protected $fillable = [
        'data', 'valor', 'descricao','entregador_id'
    ];

    protected $hidden = [];

    protected $casts = [
        'data' => 'date:d-m-Y',
    ];

    public function entregador() {
        return $this->hasOne(User::class, 'id', 'entregador_id');
    }
}
