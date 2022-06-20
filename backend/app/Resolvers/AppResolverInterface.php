<?php

namespace App\Resolvers;

interface AppResolverInterface
{
    public function authLogin($credentials);
    public function authAlterarSenha($request);

    public function getVendas($queryParams, $date);
    public function finishSale($dados);
    
    public function createItemEntregador($dados);
    
    public function getAllItemAvailable($queryParams);
    public function getEntregasApp($queryParams);
    public function getEntregasDisponiveis();
   
}
