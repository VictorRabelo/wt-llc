<?php

namespace App\Http\Controllers\Contabilidade;

use App\Enums\CodeStatusEnum;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Repositories\Contracts\Contabilidade\ContabilidadeRepositoryInterface;

class ContabilidadeController extends Controller
{
    private $contabilidadeRepository;

    public function __construct(ContabilidadeRepositoryInterface $contabilidadeRepository)
    {
        $this->contabilidadeRepository = $contabilidadeRepository;
    }

    public function index(Request $request)
    {
        try {
            $queryParams = $request->all();
            $res = $this->contabilidadeRepository->index($queryParams);

            if (isset($res->code) && $res->code == CodeStatusEnum::ERROR_SERVER) {
                return response()->json(['message' => $res->message], $res->code);
            }

            return response()->json(['response' => $res], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => $e->getMessage(), 'message' => 'Erro de servidor'], 500);
        }
    }

    public function show($id)
    {
        try {
            $res = $this->contabilidadeRepository->show($id);
            
            if (!$res) {
                return response()->json(['message' => 'Erro de servidor'], 500);
            }
            
            return response()->json($res, 200);


        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => $e->getMessage(), 'message' => 'Erro de servidor'], 500);
        }
    }
    
    public function store(Request $request)
    {
        try {
            $dados = $request->all();
            $res = $this->contabilidadeRepository->create($dados);

            if (isset($res['code']) && $res['code'] == 500) {
                return response()->json(['response' => $res], $res['code']);
            } else {
                return response()->json(['response' => $res], 201);
            }
            
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => $e->getMessage(), 'message' => 'Erro de servidor'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $dados = $request->all();

            $res = $this->contabilidadeRepository->update($dados, $id);

            if ($res['code'] == 500) {
                return response()->json(['message' => $res['message']], $res['code']);
            }

            return response()->json($res['message'], $res['code']);
            
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => $e->getMessage(), 'message' => 'Erro de servidor'], 500);
        }
    }
    
    public function destroy($id, Request $request)
    {
        try {

            $res = $this->contabilidadeRepository->deleteVenda($id, $request->all());

            if ($res['code'] == 500) {
                return response()->json(['response' => 'Erro de Servidor'], 500);
            }

            return response()->json($res, 200);
            
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => $e->getMessage(), 'message' => 'Erro de servidor'], 500);
        }
    }

    public function finishVenda(Request $request)
    {
        try {
            $dados = $request->all();

            $res = $this->contabilidadeRepository->finishVenda($dados);

            if ($res['code'] == 500) {
                return response()->json(['message' => $res['message']], $res['code']);
            }

            return response()->json($res['message'], $res['code']);
            
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => $e->getMessage(), 'message' => 'Erro de servidor'], 500);
        }
    }
    
    // Item
    public function showItem($id)
    {
        try {
            $res = $this->contabilidadeRepository->getItemById($id);
            
            if (!$res) {
                return response()->json(['message' => 'Falha ao processar o produto!'], 500);
            }
            
            return response()->json($res, 200);


        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => $e->getMessage(), 'message' => 'Erro de servidor'], 500);
        }
    }
    
    public function showItemApp($id)
    {

        try {
            $res = $this->contabilidadeRepository->showItemApp($id);
            
            if (!$res) {
                return response()->json(['message' => 'Falha ao processar o produto!'], 500);
            }
            
            return response()->json($res, 200);


        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => $e->getMessage(), 'message' => 'Erro de servidor'], 500);
        }
    }

    public function storeItem(Request $request)
    {   
        try {
            $dados = $request->all();
            $res = $this->contabilidadeRepository->createItem($dados);

            if (isset($res['code']) && $res['code'] == 500) {
                return response()->json($res, 500);
            }

            return response()->json(['response' => $res], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => $e->getMessage(), 'message' => 'Erro de servidor'], 500);
        }
    }

    public function updateItem(Request $request, $id)
    {
        try {
            $dados = $request->all();

            $res = $this->contabilidadeRepository->updateItem($dados, $id);

            if (isset($res->code) && $res->code == CodeStatusEnum::ERROR_SERVER) {
                return response()->json(['message' => $res->message], $res->code);
            }

            return response()->json($res, 200);
            
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => $e->getMessage(), 'message' => 'Erro de servidor'], 500);
        }
    }

    public function destroyItem($id)
    {
        try {

            $res = $this->contabilidadeRepository->deleteItem($id);

            if (!$res) {
                return response()->json(['response' => 'Erro de Servidor'], 500);
            }

            return response()->json($res, 200);
            
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => $e->getMessage(), 'message' => 'Erro de servidor'], 500);
        }
    }
}
