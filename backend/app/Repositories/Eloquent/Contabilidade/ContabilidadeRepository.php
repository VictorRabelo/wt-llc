<?php

namespace App\Repositories\Eloquent\Contabilidade;

use App\Enums\CodeStatusContabilidadeEnum;
use App\Models\Estoque;
use App\Models\EntregaItem;
use App\Models\Movition;
use App\Models\HistoricoContabilidade;
use App\Models\Produto;
use App\Models\Contabilidade;
use App\Repositories\Contracts\Contabilidade\ContabilidadeRepositoryInterface;
use App\Repositories\Eloquent\AbstractRepository;
use App\Resolvers\AppResolverInterface;
use App\Utils\Messages;
use App\Utils\Tools;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContabilidadeRepository extends AbstractRepository implements ContabilidadeRepositoryInterface
{
    /**
     * @var Contabilidade
     */
    protected $model = Contabilidade::class;

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
        if (isset($queryParams['aReceber'])) {
            return $this->aReceber();
        }

        if(isset($queryParams['date'])) {
            if($queryParams['date'] == 0){
                $dados = $this->model->with('produto', 'cliente', 'vendedor')->orderBy('id_Contabilidade', 'desc')->get();
            } else {
                $date = $this->filterDate($queryParams['date']);
                $dados = $this->model->with('produto', 'cliente', 'vendedor')->whereBetween('created_at', [$date['inicio'], $date['fim']])->orderBy('id_Contabilidade', 'desc')->get();
            }

            if (!$dados) {
                return $this->messages->error;
            }

        } else {
            $date = $this->dateMonth();
            $dados = $this->model->with('produto', 'cliente', 'vendedor')->whereBetween('created_at', [$date['inicio'], $date['fim']])->orderBy('id_Contabilidade', 'desc')->get();
            if (!$dados) {
                return $this->messages->error;
            }
        }
        
        if(!isset($date)) {
            $date = null;
        }
        
        return $this->tools->calculoContabilidade($dados, $date);
    }
    
    public function show($id){
        $dadosContabilidade = Contabilidade::where('id', '=', $id)->leftJoin('clientes','clientes.id_cliente', '=', 'contabilidades.cliente_id')->select('clientes.name as cliente', 'contabilidades.*')->first();
        if (!$dadosContabilidade) {
            return false;
        }
        
        $dadosProdutos = HistoricoContabilidade::where('contabilidade_id', '=', $id)->orderBy('created_at', 'desc')->get();
        if (!$dadosProdutos) {
            return false;
        }

        $dadosContabilidade->total_dolar = 0;
        $dadosContabilidade->total_real = 0;
        
        foreach ($dadosProdutos as $item) {
            
            $item->id_estoque = $item->produto->estoque()->first()->id_estoque;
            $item->total_real += $item->valor;
            $item->total_dolar += $item->valor_dolar;    
        }
        
        $dadosContabilidade->save();
        
        return ['dadosContabilidade' => $dadosContabilidade, 'dadosProdutos' => $dadosProdutos];
    }

    public function create($dados)
    {
        $dados['admin_id'] = $this->userLogado()->id;
        
        $date = $this->dateToday();
        
        $query = Contabilidade::where('admin_id', $dados['admin_id'])->where('status', null)->whereBetween('created_at', [$date['inicio'], $date['fim']])->first();
        
        if(is_null($query)){
            return $this->store($dados);
        }
        
        return ['message' => 'Já existe uma Contabilidade criada em aberto', 'code' => 500];
    }

    public function update($dados, $id)
    {
        $dadosContabilidade = Contabilidade::where('id', '=', $id)->leftJoin('clientes','clientes.id_cliente', '=', 'contabilidades.cliente_id')->select('clientes.name as cliente', 'contabilidades.*')->first();
        if (!$dadosContabilidade) {
            return ['message' => 'Contabilidade não encontrada!', 'code' => 404];
        }

        if(($dados['restante'] == 0 || $dados['restante'] < 0) && $dados['restante'] !== null) {
            $dados['status'] = 'pago';
        }

        $dadosContabilidade->fill($dados);
        if (!$dadosContabilidade->save()) {
            return ['message' => 'Falha ao debitar!', 'code' => 500];
        }

        if(isset($dados['debitar'])){
            return $this->debitar($dados, $id);
        }

        return ['message' => 'Contabilidade atualizada com sucesso!', 'code' => 200];
    }

    public function deleteContabilidade($id, $params)
    {
        $dados = $this->model->findOrFail($id);

        if (empty($dados)) {
            return ['message' => 'Falha na movimentação do estoque', 'code' => 500];

        }
        
        foreach ($dados->historico()->get() as $item) {

            $item->delete();
        }

        $dados->delete();

        return ['message' => 'Deletado com sucesso!', 'code' => 200];

    }

    public function finishContabilidade($dados)
    {
        if (isset($dados['app'])) {
            return $this->baseApp->finishSale($dados);
        }

        if (count($dados['itens']) == 0) {
            return ['message' => 'Contabilidade não contem itens!', 'code' => 500];
        }

        $dadosContabilidade = Contabilidade::where('id', '=', $dados['id'])->first();
        if (!$dadosContabilidade) {
            return ['message' => 'Falha ao procurar Contabilidade ', 'code' => 500];
        }
        
        $dadosContabilidade->fill($dados);
        
        if(!$dadosContabilidade->save()){
            return ['message' => 'Falha ao cadastrar Contabilidade', 'code' => 500];
        }
        
        if (!$this->movimentacaoEstoque($dados['itens'])) {
            return ['message' => 'Falha na movimentação do estoque', 'code' => 500];
        }

        if (!$this->aPrazoContabilidade($dados)) {
            return ['message' => 'Falha ao cadastrar movimentação', 'code' => 500];
        }

        return ['message' => 'Contabilidade realizada com sucesso!', 'code' => 200];
    }

    // Item 
    public function getItemById($id){
        $dados = HistoricoContabilidade::where('id', '=', $id)->first();
        if (!$dados) {
            return false;
        }
        
        return $dados;
    }
    
    public function createItem($dados){
        
        if(isset($dados['app'])) {
            return $this->baseApp->createItemEntregador($dados);
        }
        
        $result = HistoricoContabilidade::create($dados);
        if(!$result){
            return ['message' => 'Falha ao procesar dados!', 'code' => 500];
        }

        return ['message' => 'Item cadastrado com sucesso!'];  
    }

    public function updateItem($dados, $id){
        $dadosItem = HistoricoContabilidade::where('id', '=', $id)->first();
        if (!$dadosItem) {
            return false;
        }
        
        $dadosContabilidade = Contabilidade::where('id', '=', $dados['contabilidade_id'])->first();
        if (!$dadosContabilidade) {
            return false;
        }

        $dadosItem->update(['preco_Contabilidade' => $dados['preco_Contabilidade'], 'qtd_Contabilidade' => $dados['qtd_Contabilidade']]);
        if(!$dadosItem){
            return false;
        }
        
        return ['message' => 'Atualizado com sucesso!'];
    }
    
    public function deleteItem($id){
        $dados = ProdutoContabilidade::where('id', '=', $id)->first();
        if (!$dados) {
            return false;
        }
        
        if(!$dados->delete()) {
            return false;
        }

        return ['message' => 'Item deletado com sucesso!'];
    }

    private function aReceber() {

        $dados = $this->model->with('produto', 'cliente', 'vendedor')->where('status', 'pendente')->orderBy('id_Contabilidade', 'desc')->get();
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
            'Contabilidade_id' => $id,
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
    
    private function aPrazoContabilidade($dados)
    {
        if(!isset($dados['prazo'])) {
            
            $dateNow = $this->dateNow();

            $movition = Movition::create([
                'Contabilidade_id' => $dados['id_Contabilidade'],
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

            $dadosEstoque->decrement('und', $item['qtd_Contabilidade']);
            
            if(!$dadosEstoque->getIsHasUndAttribute()){
                $dadosProduto->update(['status' => 'vendido']);
            }
        }

        return true;
    }

}
