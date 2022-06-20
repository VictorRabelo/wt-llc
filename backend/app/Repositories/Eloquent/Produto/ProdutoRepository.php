<?php

namespace App\Repositories\Eloquent\Produto;

use App\Models\Produto;
use App\Repositories\Contracts\Produto\ProdutoRepositoryInterface;
use App\Repositories\Eloquent\AbstractRepository;
use App\Utils\Messages;
use App\Utils\Tools;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProdutoRepository extends AbstractRepository implements ProdutoRepositoryInterface
{
    /**
     * @var Produto
     */
    protected $model = Produto::class;

    /**
     * @var Tools
     */
    protected $tools = Tools::class;
    
    /**
     * @var Messages
     */
    protected $messages = Messages::class;

    public function estoque()
    {
        $dados = $this->model->where('status', 'ok')->get();

        if (!$dados) {
            return $this->messages->error;
        }

        return [
            'response' => $dados,
            'numero' => $dados->count(),
        ];
    }

    public function perfumeMasculino()
    {
        $perfumes = DB::table('estoques')->join('produtos', 'produtos.id_produto', '=', 'estoques.produto_id')->join('categorias', 'categorias.id_categoria', '=', 'produtos.categoria_id')->join('valores', 'valores.id_valor', '=', 'produtos.valor_id')->select('categorias.*', 'produtos.*', 'estoques.*', 'valores.*')->where('categorias.categoria', '=', 'Perfume')->where('categorias.subcategoria', '=', 'Masculino')->get();

        $perfumesArray = [];

        foreach ($perfumes as $value) {
            $file = storage_path('app/public/' . $value->path);
            $result = file_get_contents($file);
            $value->path = base64_encode($result);

            array_push($perfumesArray, $value);
        }

        return [
            'response' => $perfumesArray
        ];
    }

    public function perfumeFeminino()
    {
        $perfumes = DB::table('estoques')->join('produtos', 'produtos.id_produto', '=', 'estoques.produto_id')->join('categorias', 'categorias.id_categoria', '=', 'produtos.categoria_id')->join('valores', 'valores.id_valor', '=', 'produtos.valor_id')->select('categorias.*', 'produtos.*', 'estoques.*', 'valores.*')->where('categorias.categoria', '=', 'Perfume')->where('categorias.subcategoria', '=', 'Feminino')->get();

        $perfumesArray = [];

        foreach ($perfumes as $value) {
            $file = storage_path('app/public/' . $value->path);
            $result = file_get_contents($file);
            $value->path = base64_encode($result);

            array_push($perfumesArray, $value);
        }

        return [
            'response' => $perfumesArray
        ];
    }

    public function pago()
    {
        $dados = $this->model->where('status', 'pago')->get();

        if (!$dados) {
            return $this->messages->error;
        }

        return [
            'response' => $dados,
            'numero' => $dados->count(),
        ];
    }

    public function enviados()
    {
        $dados = $this->model->where('status', 'pendente')->get();

        if (!$dados) {
            return $this->messages->error;
        }

        return [
            'response' => $dados,
            'numero' => $dados->count(),
        ];
    }

    public function vendidos()
    {
        $dados = $this->model->where('status', 'vendido')->get();

        if (!$dados) {
            return $this->messages->error;
        }

        return [
            'response' => $dados,
            'numero' => $dados->count(),
        ];
    }

    public function storeDolarFeminino($dados)
    {
        $produtos = DB::table('estoques')->join('produtos', 'produtos.id_produto', '=', 'estoques.produto_id')->join('categorias', 'categorias.id_categoria', '=', 'produtos.categoria_id')->join('valores', 'valores.id_valor', '=', 'produtos.valor_id')->select('categorias.*', 'produtos.*', 'estoques.*', 'valores.*')->where('categorias.categoria', '=', 'Perfume')->where('categorias.subcategoria', '=', 'Feminino')->get();

        foreach ($produtos as $value) {

            $result = $value->valor_site * $dados['dolar'];
            $prod = $this->model->where('id_produto', $value->id_produto)->first();

            $prod->valor()->update([
                'dolar' => $dados['dolar'],
                'total_site' => $result,
            ]);
        }
    }

    public function storeDolarMasculino($dados)
    {
        $produtos = DB::table('estoques')->join('produtos', 'produtos.id_produto', '=', 'estoques.produto_id')->join('categorias', 'categorias.id_categoria', '=', 'produtos.categoria_id')->join('valores', 'valores.id_valor', '=', 'produtos.valor_id')->select('categorias.*', 'produtos.*', 'estoques.*', 'valores.*')->where('categorias.categoria', '=', 'Perfume')->where('categorias.subcategoria', '=', 'Masculino')->get();

        foreach ($produtos as $value) {

            $result = $value->valor_site * $dados['dolar'];
            $prod = $this->model->where('id_produto', $value->id_produto)->first();

            $prod->valor()->update([
                'dolar' => $dados['dolar'],
                'total_site' => $result,
            ]);
        }
    }

    public function updateProduto($dados, $id) {
        $dadosProduto = Produto::findOrFail($id);
        if (empty($dadosProduto)) {
            return ['message' => 'Produto não encontrado.', 'code' => 404];
        }

        if (isset($dados['fileInvoice'])) {
            if($dadosProduto->invoice_path) {
                $dados['invoice_path'] = $this->tools->parse_file($dados['fileInvoice'], $dadosProduto->name.$dadosProduto->id_produto, $dadosProduto->invoice_path);
            } else {
                $dados['invoice_path'] = $this->tools->parse_file($dados['fileInvoice'], $dadosProduto->name.$dadosProduto->id_produto);
            }
        }

        $dadosProduto->fill($dados);
        if (!$dadosProduto->save()) {
            return ['message' => 'Falha ao atualizar dados!', 'code' => 500];
        }

        return ['message' => 'Invoice anexada com sucesso.', 'code' => 404];

    }

    public function create(Request $request)
    {
        if ($request->hasFile('invoice')) {

            $fileInvoice = $request->file('invoice');

            if (file_exists($fileInvoice) && !empty($fileInvoice)) {
                $file_name = $fileInvoice->getClientOriginalName();
                $produto = $this->model->where('id_produto', $request->id)->first();
                if ($produto) {
                    $produto->update([
                        'invoice' => $file_name,
                        'invoice_pah' => $fileInvoice->store($produto->name)
                    ]);

                    return true;

                } else {
                    return ['message' => 'Produto não encontrado.', 'code' => 404];
                }
            }
        }
    }
}
