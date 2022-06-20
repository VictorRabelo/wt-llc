<?php

namespace App\Repositories\Eloquent\Venda;

use App\Enums\CodeStatusVendaEnum;
use App\Models\Estoque;
use App\Models\EntregaItem;
use App\Models\Movition;
use App\Models\ProdutoVenda;
use App\Models\Produto;
use App\Models\Venda;
use App\Repositories\Contracts\Venda\VendaRepositoryInterface;
use App\Repositories\Eloquent\AbstractRepository;
use App\Resolvers\AppResolverInterface;
use App\Utils\Messages;
use App\Utils\Tools;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VendaRepository extends AbstractRepository implements VendaRepositoryInterface
{
    /**
     * @var Venda
     */
    protected $model = Venda::class;

    /**
     * @var Tools
     */
    protected $tools = Tools::class;

    /**
     * @var AppResolverInterface
     */
    protected $baseApp = AppResolverInterface::class;

    /**
     * @var Messages
     */
    protected $messages = Messages::class;

    public function index($queryParams)
    {
        if (isset($queryParams['app'])) {
            return $this->baseApp->getVendas($queryParams, isset($queryParams['date'])?$queryParams['date']:false);
        }

        if (isset($queryParams['aReceber'])) {
            return $this->aReceber();
        }

        if(isset($queryParams['date'])) {
            if($queryParams['date'] == 0){
                $dados = $this->model->with('produto', 'cliente', 'vendedor')->orderBy('id_venda', 'desc')->get();
            } else {
                $date = $this->filterDate($queryParams['date']);
                $dados = $this->model->with('produto', 'cliente', 'vendedor')->whereBetween('created_at', [$date['inicio'], $date['fim']])->orderBy('id_venda', 'desc')->get();
            }

            if (!$dados) {
                return $this->messages->error;
            }

        } else {
            $date = $this->dateMonth();
            $dados = $this->model->with('produto', 'cliente', 'vendedor')->whereBetween('created_at', [$date['inicio'], $date['fim']])->orderBy('id_venda', 'desc')->get();
            if (!$dados) {
                return $this->messages->error;
            }
        }
        
        if(!isset($date)) {
            $date = null;
        }
        
        return $this->tools->calculoVenda($dados, $date);
    }
    
    public function show($id){
        $dadosVenda = Venda::where('id_venda', '=', $id)->leftJoin('clientes','clientes.id_cliente', '=', 'vendas.cliente_id')->select('clientes.name as cliente', 'vendas.*')->first();
        if (!$dadosVenda) {
            return false;
        }
        
        $dadosProdutos = ProdutoVenda::with('produto')->where('venda_id', '=', $id)->orderBy('created_at', 'desc')->get();
        if (!$dadosProdutos) {
            return false;
        }

        $dadosVenda->total_final = 0;
        $dadosVenda->lucro = 0;
        $dadosVenda->qtd_produto = 0;
        
        foreach ($dadosProdutos as $item) {
            
            $item->id_estoque = $item->produto->estoque()->first()->id_estoque;
            $item->total_venda = $item->preco_venda * $item->qtd_venda;
            
            $dadosVenda->total_final += $item->total_venda;
            $dadosVenda->qtd_produto += $item->qtd_venda;
            $dadosVenda->lucro += $item->lucro_venda * $item->qtd_venda;
            
        }
        
        $dadosVenda->save();
        
        return ['dadosVenda' => $dadosVenda, 'dadosProdutos' => $dadosProdutos];
    }

    public function create($dados)
    {
        $dados['vendedor_id'] = $this->userLogado()->id;
        
        $date = $this->dateToday();
        
        $query = Venda::where('vendedor_id', $dados['vendedor_id'])->where('status', null)->whereBetween('created_at', [$date['inicio'], $date['fim']])->first();
        
        if(is_null($query)){
            return $this->store($dados);
        }
        
        return ['message' => 'Já existe uma venda criada em aberto', 'code' => 500];
    }

    public function update($dados, $id)
    {
        $dadosVenda = Venda::where('id_venda', '=', $id)->leftJoin('clientes','clientes.id_cliente', '=', 'vendas.cliente_id')->select('clientes.name as cliente', 'vendas.*')->first();
        if (!$dadosVenda) {
            return ['message' => 'Venda não encontrada!', 'code' => 404];
        }

        if(($dados['restante'] == 0 || $dados['restante'] < 0) && $dados['restante'] !== null) {
            $dados['status'] = 'pago';
        }

        $dadosVenda->fill($dados);
        if (!$dadosVenda->save()) {
            return ['message' => 'Falha ao debitar!', 'code' => 500];
        }

        if(isset($dados['debitar'])){
            return $this->debitar($dados, $id);
        }

        return ['message' => 'Venda atualizada com sucesso!', 'code' => 200];
    }

    public function deleteVenda($id, $params)
    {
        $dados = $this->model->findOrFail($id);

        if (empty($dados)) {
            return ['message' => 'Falha na movimentação do estoque', 'code' => 500];

        }
        
        $entrega = $dados->entrega()->first();
        
        foreach ($dados->vendaItens()->get() as $item) {
            $dadoProduto = $item->produto()->first();
            $dadoEstoque = $dadoProduto->estoque()->first();

            if (!$dadoEstoque) {
                return ['message' => 'Falha na movimentação do estoque', 'code' => 500];
            }
            
            if($dados->status !== null && isset($params['extornarProduto']) && $params['extornarProduto']){
                
                if(is_null($dados->entrega_id)){
                    
                    $dadoEstoque->increment('und', $item->qtd_venda);
                    
                } else {
                    
                    $entregaItem =  EntregaItem::where('entrega_id', $entrega->id_entrega)->where('produto_id', $item['produto_id'])->first();
                    $entregaItem->increment('qtd_disponivel', $item->qtd_venda);
                }
                
                if ($dadoEstoque->und > 0) {
                    $dadoProduto->update(['status' => 'ok']);
                }
            }

            $item->delete();
        }

        $dados->delete();

        return ['message' => 'Deletado com sucesso!', 'code' => 200];

    }

    public function finishVenda($dados)
    {
        if (isset($dados['app'])) {
            return $this->baseApp->finishSale($dados);
        }

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
        
        if (!$this->movimentacaoEstoque($dados['itens'])) {
            return ['message' => 'Falha na movimentação do estoque', 'code' => 500];
        }

        if (!$this->aPrazoVenda($dados)) {
            return ['message' => 'Falha ao cadastrar movimentação', 'code' => 500];
        }

        return ['message' => 'Venda realizada com sucesso!', 'code' => 200];
    }

    // Item 
    public function getItemById($id){
        $dados = ProdutoVenda::where('id', '=', $id)->first();
        if (!$dados) {
            return false;
        }
        
        $produto = $dados->produto()->first();
        $dados->produto = $produto;
        $dados->produto->estoque = $produto->estoque()->first();
        
        return $dados;
    }
    
    public function showItemApp($id){
        $dados = ProdutoVenda::where('id', '=', $id)->first();
        if (!$dados) {
            return false;
        }
        
        $dados->produto = $dados->produto()->first();
        $dados->venda = $dados->venda()->first();
        
        $entregaItem = EntregaItem::where('entrega_id', $dados->venda->entrega_id)->where('produto_id', $dados->produto->id_produto)->first();
        
        if (!$entregaItem) {
            return false;
        }

        $dados->produto->und = $entregaItem->qtd_disponivel;
        $dados->produto->preco = $entregaItem->preco_entrega;
        $dados->produto->unitario = $entregaItem->preco_entrega;
        
        return $dados;
    }

    public function createItem($dados){
        
        if(isset($dados['app'])) {
            return $this->baseApp->createItemEntregador($dados);
        }
        
        $result = ProdutoVenda::create($dados);
        if(!$result){
            return ['message' => 'Falha ao procesar dados!', 'code' => 500];
        }

        return ['message' => 'Item cadastrado com sucesso!'];  
    }

    public function updateItem($dados, $id){
        $dadosItem = ProdutoVenda::where('id', '=', $id)->first();
        if (!$dadosItem) {
            return false;
        }
        
        $dadosVenda = Venda::where('id_venda', '=', $dados['venda_id'])->first();
        if (!$dadosVenda) {
            return false;
        }

        $dadosItem->update(['preco_venda' => $dados['preco_venda'], 'qtd_venda' => $dados['qtd_venda']]);
        if(!$dadosItem){
            return false;
        }
        
        return ['message' => 'Atualizado com sucesso!'];
    }
    
    public function deleteItem($id){
        $dados = ProdutoVenda::where('id', '=', $id)->first();
        if (!$dados) {
            return false;
        }
        
        if(!$dados->delete()) {
            return false;
        }

        return ['message' => 'Item deletado com sucesso!'];
    }

    private function aReceber() {

        $dados = $this->model->with('produto', 'cliente', 'vendedor')->where('status', 'pendente')->orderBy('id_venda', 'desc')->get();
        if (!$dados) {
            return $this->messages->error;
        }

        $restante = 0;
        $pago = 0;
        $totalFinal = 0;

        $dataSource = [];
        foreach ($dados as $item) {
            $item->nameCliente = $item->cliente->name;
            $item->telefoneCliente = $item->cliente->telefone;
            $restante += $item->restante;
            $pago += $item->pago;
            $totalFinal += $item->total_final;

            array_push($dataSource, $item);
        }
        
        return [
            'dadosReceber'  => $dataSource,
            'saldoReceber'  => $totalFinal,
            'saldoPago'     => $pago,
            'totalRestante' => $restante,
        ];
    }

    private function debitar($dados, $id)
    {
        $dateNow = $this->dateNow();

        $movition = Movition::create([
            'venda_id' => $id,
            'data' => $dateNow,
            'valor' => $dados['creditar'],
            'descricao' => $dados['cliente'],
            'tipo' => 'entrada',
            'status' => $dados['caixa']
        ]);

        if(!$movition) {
            return ['message' => 'Falha criar movimentação!', 'code' => 500];
        }

        return ['message' => 'Debitado com sucesso!', 'code' => 200];
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

    private function movimentacaoEstoque($dados)
    {
        foreach ($dados as $item) {
            $dadosEstoque = Estoque::where('id_estoque', $item['id_estoque'])->where('produto_id', $item['produto_id'])->first();
            if (!$dadosEstoque) {
                return false;
            }
            
            $dadosProduto = $dadosEstoque->produto;
            if (!$dadosProduto) {
                return false;
            }

            if(!$dadosEstoque->getIsHasUndAttribute()){
                $dadosProduto->update(['status' => 'vendido']);
                return false;
            }

            $dadosEstoque->decrement('und', $item['qtd_venda']);
            
            if(!$dadosEstoque->getIsHasUndAttribute()){
                $dadosProduto->update(['status' => 'vendido']);
            }
        }

        return true;
    }

}
