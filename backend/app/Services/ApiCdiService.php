<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

use App\Resolvers\ApiCdiResolverInterface;

class ApiCdiService implements ApiCdiResolverInterface
{
    private $baseApi = 'https://api.casadoimportadogo.com/api/v1';

    public function authLogin($credentials){
        $response = Http::post($this->baseApi.'/oauth/login', [
            'login' => $credentials['login'],
            'password' => $credentials['password']
        ]);
        
        return $response->json()['token']['accessToken'];
    }
    
    public function authAlterarSenha($request) {
        $response = Http::withToken($request['token'])->post($this->baseApi.'/oauth/alter-password', [
            'password' => $request['password']
        ]);
        
        return $response->json();
    }
    
    public function postUser($request) {
        $response = Http::post($this->baseApi.'/users', [
            'email' => $request['email'],
            'login' => $request['login'],
            'name' => $request['name'],
            'password' => $request['password'],
            'role' => $request['role'],
            'api' => true
        ]);
        
        return $response->json();
    }
    
    public function postDespesaEntrega($request) {
        $response = Http::post($this->baseApi.'/despesas-entrega', [
            'entregador_login' => $request['entregador_login'],
            'valor' => $request['valor'],
            'descricao' => $request['descricao'],
            'api' => true
        ]);
        
        return $response->json();
    }
}