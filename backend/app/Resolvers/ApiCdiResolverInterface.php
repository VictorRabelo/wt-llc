<?php

namespace App\Resolvers;

interface ApiCdiResolverInterface
{
    public function authLogin($credentials);
    public function authAlterarSenha($request);
    public function postUser($request);
    public function postDespesaEntrega($request);
}
