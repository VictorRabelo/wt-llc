<?php

namespace App\Repositories\Contracts\Despesa;

use App\Repositories\Contracts\CrudRepositoryInterface;
use Illuminate\Http\Request;

interface DespesaRepositoryInterface extends CrudRepositoryInterface
{
    public function index();
    
    public function movimentacao();
    
    public function create($dados);

}