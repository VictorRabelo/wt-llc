<?php

namespace App\Utils;

use App\Models\Venda;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class Tools
{

    public function calcularSaldo($dados): float
    {
        $saldo = 0.00;
        foreach ($dados as $value) {
            $saldo = $saldo + $value->valor;
        }

        return $saldo;
    }

    public function calcularEntradaSaida($dados): float
    {
        $entrada = 0.00;
        $saida = 0.00;

        foreach ($dados as $value) {
            if ($value->tipo == 'entrada') {
                $entrada = $value->valor + $entrada;
            } else {
                $saida = $value->valor + $saida;
            }
        }

        $total = $entrada - $saida;

        return $total;
    }

    public function calculoVenda($dados, $date = null)
    {
        $totalVendas = Venda::select(DB::raw('sum(total_final) as total'))->get();

        $lucro = 0;
        $totalMensal = 0;
        $pago = 0;

        $dataSource = [];
        foreach ($dados as $item) {
            
            $lucro += $item->lucro;
            $totalMensal += $item->total_final;
            $pago += $item->pago;

            array_push($dataSource, $item);
        }
        
        
        return [
            'vendas'       => $dataSource,
            'totalMensal'  => $totalMensal,
            'totalVendas'  => $totalVendas[0]['total']??0,
            'lucro'        => $lucro,
            'pago'         => $pago,
            'data'         => is_null($date['inicio'])? date('Y-m-d'):$date['inicio'],
            'mediaLittle'  => $this->calculoLittleTrees($date)
        ];
    }

    public function calculoLittleTrees($date = null)
    {
        if(is_null($date)) {
            $date = ['inicio' => date('Y-m-01'.' '.'00:00'),'fim' => date('Y-m-t'.' '.'23:59')];
        }
        
        $sql = 'SELECT `produto_venda`.*, `produtos`.`id_produto`, `categorias`.`id_categoria` from `produto_venda` inner join `produtos` on `produtos`.`id_produto` = `produto_venda`.`produto_id` inner join `vendas` on `vendas`.`id_venda` = `produto_venda`.`venda_id` inner join `categorias` on `categorias`.`id_categoria` = `produtos`.`categoria_id` WHERE `categorias`.`id_categoria` = 4 AND `produto_venda`.`created_at` BETWEEN "'.$date['inicio'].'" AND "'.$date['fim'].'"';
        $products = DB::select($sql);
        
        if(empty($products)){
            return false;    
        }
        
        $mediaUnitaria = 0;
        $calculo = 0;
        $qtdVendaTotal = 0;
        $count = count($products);
        
        foreach ($products as $item) {
            $qtdVendaTotal += $item->qtd_venda;
            $calculo += $item->preco_venda * $item->qtd_venda;
        }

        $mediaTotal = $calculo / $qtdVendaTotal;
        
        return [ 'mediaTotal' => $mediaTotal,'qtdVendaTotal' => $qtdVendaTotal ];
    }

    public function somatoriaGeralVendasApp($dadosApp,$dadosApi)
    {
        $totalVendas = $dadosApp['totalVendas'] + $dadosApi['totalVendas'];
        $totalMensal = $dadosApp['totalMensal'] + $dadosApi['totalMensal'];

        $lucro = $dadosApp['lucro'] + $dadosApi['lucro'];
        $pago = $dadosApp['pago'] + $dadosApi['pago'];

        return [
            'totalMensal'  => $totalMensal,
            'totalVendas'  => $totalVendas[0]['total'],
            'lucro'        => $lucro,
            'pago'         => $pago,
            'data'         => isset($date['inicio'])? $date['inicio']:date('Y-m-d'),
            'mounth'       => isset($queryParams['date'])? $queryParams['date']:date('m'),
        ];
    }

    public function parse_file($file, $path, $file_old = "")
    {

        if (!empty($file_old) && Storage::disk('public')->exists($file_old)) { //deleta file old
            $this->_deletePhotoIfExists($file_old);
        }
        
        $file = preg_replace('#^data:image/[^;]+;base64,#', '', $file);
        $ext = $this->getExtensionFileName($file);
        $content = base64_decode($file);

        $file_name = md5(
            uniqid(
                microtime(),
                true
            )
        ) .'.'. $ext;

        $pathSave = "{$path}/{$file_name}";
        Storage::disk('public')->put($pathSave, $content);

        return $pathSave;
    }

    public function getExtensionFileName($img)
    {
        $extension = explode("/", $img);
        $ext = explode(";", $extension[1]);
        return $ext[0];
    }

    public function _deletePhotoIfExists($file_path): void
    {
        Storage::disk('public')->delete($file_path);
    }

    public function putFile($file, $path)
    {
        return Storage::disk('public')->put($path, $file);
    }

    public function getUrlFile($path)
    {
        if (Storage::disk('public')->exists($path)) {
            return Storage::url($path);
        }
        return null;
    }

    public function soNumero($str)
    {
        return preg_replace("/[^0-9]/", "", $str);
    }
    
    public function getPhoneFormattedAttribute($telefone): string
    {
        $phone = $telefone;

        $ac = substr($phone, 0, 2);
        $prefix = substr($phone, 2, 5);
        $suffix = substr($phone, 7);

        return "({$ac}) {$prefix}-{$suffix}";
    }
}
