<?php

namespace App\Repositories\Contracts\Contabilidade;

use App\Repositories\Contracts\CrudRepositoryInterface;
use Illuminate\Http\Request;

interface ContabilidadeRepositoryInterface extends CrudRepositoryInterface
{
    public function index($queryParams);

    public function show($id);
    
    public function create($dados);

    public function update($dados, $id);

    public function deleteContabilidade($id, $params);

    public function finishContabilidade($dados);

    //itens
    public function getItemById($id);

    public function createItem($dados);

    public function updateItem($dados, $id);
    
    public function deleteItem($id);
}