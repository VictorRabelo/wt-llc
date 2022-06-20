<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

use App\Repositories\Eloquent\AbstractRepository;

use App\Models\User;
use App\Models\Venda;
use App\Models\Entrega;
use App\Models\Movition;
use App\Models\EntregaItem;
use App\Models\Produto;
use App\Models\ProdutoVenda;
use App\Resolvers\ApiCdiResolverInterface;
use App\Resolvers\AppResolverInterface;

use App\Utils\Messages;
use App\Utils\Tools;


class AppService extends AbstractRepository implements AppResolverInterface
{
    /**
     * @var Messages
     */
    protected $messages = Messages::class;

    /**
     * @var Tools
     */
    protected $tools = Tools::class;

    /**
     * @var ApiCdiResolverInterface
     */
    protected $baseApi = ApiCdiResolverInterface::class;

    public function authLogin($credentials){
        unset($credentials['app']);
        
        $tokenApi = $this->baseApi->authLogin($credentials);
        
        if (auth()->attempt($credentials)) {
                
            $user = auth()->user();
            $userCurrent = User::where('id', $user->id)->first();
                    
            if($tokenApi) {
                $userCurrent->update(['tokenApi' => $tokenApi]);
            }

            $userRole = $user->role()->first();
            $user->role = $userRole->role;
            $token = $user->createToken(env('AUTH_TOKEN'), [$userRole->role]);
            $user->token = $token->accessToken;

            return [
                'token' => $token, 
                'user' => $user
            ];
        }
    }
    
    public function authAlterarSenha($request) {
        unset($request['app']);
        
        $id = auth()->user()->id;
        $resp = User::where('id', $id)->first();
        
        $request['token'] = $resp->tokenApi;
        
        $response = $this->baseApi->authAlterarSenha($request);
        
        if(!$response) {
            return response()->json('Erro no servidor | CDIGO |', 500);
        }
        
        if(empty($resp)){
            return false;
        }

        $resp->update(['password' =>  Hash::make($request['password'])]);
        $resp->save();
        
        return response()->json('Senha Atualizada', 200);
    }

    public function getVendas($queryParams, $date){
        
        $userId = Auth::user()->id;
        
        if(isset($queryParams['date'])) {
            if($queryParams['date'] == 0){
                $dados = Venda::with('produto', 'cliente', 'vendedor')->where('vendedor_id', $userId)->orderBy('id_venda', 'desc')->get();
            } else {
                $date = $this->filterDate($queryParams['date']);
                $dados = Venda::with('produto', 'cliente', 'vendedor')->where('vendedor_id', $userId)->whereBetween('created_at', [$date['inicio'], $date['fim']])->orderBy('id_venda', 'desc')->get();
            }

        } else {
            $date = $this->dateMonth();
            $dados = Venda::with('produto', 'cliente', 'vendedor')->where('vendedor_id', $userId)->whereBetween('created_at', [$date['inicio'], $date['fim']])->orderBy('id_venda', 'desc')->get();
        }
        
        if(!isset($date)) {
            $date = null;
        }
        
        return $this->tools->calculoVenda($dados, $date);
    }
    
    public function finishSale($dados){
        unset($dados['app']);
        
        if (count($dados['itens']) == 0) {
            return ['message' => 'Venda não contem itens!', 'code' => 500];
        }

        $dadosVenda = Venda::where('id_venda', '=', $dados['id_venda'])->first();
        if (!$dadosVenda) {
            return ['message' => 'Falha ao procurar venda ', 'code' => 500];
        }
        
        $dadosVenda->fill($dados);
        
        if(!$dadosVenda->save()){
            return ['message' => 'Falha ao cadastrar venda', 'code' => 500];
        }
        
        if (!$this->movimentacaoEstoque($dados['itens'], $dados['entrega_id'])) {
            return ['message' => 'Falha na movimentação do estoque', 'code' => 500];
        }

        if (!$this->aPrazoVenda($dados)) {
            return ['message' => 'Falha ao cadastrar movimentação', 'code' => 500];
        }
        
        return ['message' => 'Venda realizada com sucesso!', 'code' => 200];
    }
    
    public function getAllItemAvailable($queryParams){
        
        $userId =  auth()->user()->id;
        
        $entrega = Entrega::where('id_entrega', $queryParams['id_entrega'])->where('entregador_id', $userId)->where('status', 'pendente')->first();
        
        $produtosDisponiveis = [];
        
        $produtos = $entrega->entregasItens()->get();
        
        foreach ($produtos as $key => $value) {
            if ($value->qtd_disponivel == 0) {
                unset($produtos[$key]);
            } else {
                $product = $value->produto()->first();
                $product->und = $value->qtd_disponivel;
                $product->preco = $value->preco_entrega;
                $product->unitario = $value->preco_entrega;
                $product->data_pedido = $value->created_at;
                
                array_push($produtosDisponiveis, $product);
            }

                
        }
        
        return $produtosDisponiveis;
    }
    
    public function getEntregasDisponiveis() {
        $userId =  auth()->user()->id;
        
        // $date = $this->dateToday()->whereBetween('created_at', [$date['inicio'], $date['fim']]);
        $dados = Entrega::with('entregador')->where('entregador_id', $userId)->where('status', 'pendente')->orderBy('id_entrega', 'desc')->get();
        
        if (!$dados) {
            return $this->messages->error;
        }

        foreach ($dados as $item) {
            $produtos = $item->entregasItens()->get();
            foreach ($produtos as $value) {
                $item->qtd_disponiveis += $value->qtd_disponivel;
            }   
        }
        
        return $dados;
    }
    
    public function getEntregasApp($queryParams) {
        $userId =  auth()->user()->id;
        
        if(isset($queryParams['date']) && !is_null($queryParams['date'])) {
            if($queryParams['date'] == 0){
                $dados = Entrega::with('entregador')->where('entregador_id', $userId)->orderBy('id_entrega', 'desc')->get();
            } else {
                $date = $this->filterDate($queryParams['date']);
                $dados = Entrega::with('entregador')->where('entregador_id', $userId)->whereBetween('created_at', [$date['inicio'], $date['fim']])->orderBy('id_entrega', 'desc')->get();
            }

            if (!$dados) {
                return $this->messages->error;
            }

        } else {
            $date = $this->dateMonth();
            $dados = Entrega::with('entregador')->where('entregador_id', $userId)->whereBetween('created_at', [$date['inicio'], $date['fim']])->orderBy('id_entrega', 'desc')->get();
            if (!$dados) {
                return $this->messages->error;
            }
        }
        
        foreach ($dados as $item) {
            $produtos = $item->entregasItens()->get();
            foreach ($produtos as $value) {
                $item->qtd_disponiveis += $value->qtd_disponivel;
            }   
        }
        
        return $dados;
    }
    
    private function aPrazoVenda($dados)
    {
        if(!isset($dados['prazo'])) {
            
            $dateNow = $this->dateNow();

            $movition = Movition::create([
                'venda_id' => $dados['id_venda'],
                'data' => $dateNow,
                'lucro' => $dados['lucro'],
                'valor' => $dados['debitar'],
                'descricao' => $dados['cliente'],
                'tipo' => 'entrada',
                'status' => $dados['caixa']
            ]);

            if(!$movition) {
                return false;
            }

            return true;
        }

        return true;
    }
    
    private function movimentacaoEstoque($dados, $idEntrega)
    {
        foreach ($dados as $item) {
            $dadosEstoque = EntregaItem::where('entrega_id', $idEntrega)->where('produto_id', $item['produto_id'])->first();
            if (!$dadosEstoque) {
                return false;
            }
            
            if ($dadosEstoque->qtd_disponivel > 0) {
                $dadosEstoque->decrement('qtd_disponivel', $item['qtd_venda']);
            } else {
                return false;
            }

        }

        return true;
    }

    public function createItemEntregador($dados)
    {
        $dadosProduto = Produto::where('id_produto', $dados['produto_id'])->first();
        $lucroProduto = $dados['preco_venda'] - $dadosProduto->unitario;
        $dados['lucro_venda'] = $lucroProduto * $dados['qtd_venda'];
        
        $result = ProdutoVenda::create($dados);
        if(!$result){
            return ['message' => 'Falha ao procesar dados!', 'code' => 500];
        }

        $dadosVenda = Venda::where('id_venda', '=', $dados['venda_id'])->first();
        if(!$dadosVenda){
            return ['message' => 'Falha ao procesar dados!', 'code' => 500];
        }

        $total = $result->preco_venda * $result->qtd_venda;

        $resultFinal = $dadosVenda->total_final? $dadosVenda->total_final + $total : 0 + $total;
        $resultLucro = $dadosVenda->lucro + $result->lucro_venda;
        $resultQtd   = $dadosVenda->qtd_produto + $result->qtd_venda;

        $dadosVenda->update(['total_final' => $resultFinal, 'lucro' => $resultLucro, 'qtd_produto' =>  $resultQtd]);
        return ['message' => 'Item cadastrado com sucesso!'];
    }
}