<?php

namespace App\Repositories\Eloquent\User;

use App\Models\Role;
use App\Models\User;
use App\Repositories\Contracts\User\UserRepositoryInterface;
use App\Repositories\Eloquent\AbstractRepository;
use App\Resolvers\ApiCdiResolverInterface;
use App\Utils\Messages;
use Illuminate\Support\Facades\Hash;

class UserRepository extends AbstractRepository implements UserRepositoryInterface
{
    /**
     * @var User
    */
    protected $model = User::class;

    /**
     * @var Messages
     */
    protected $messages = Messages::class;
    
    /**
     * @var ApiCdiResolverInterface
     */
    protected $baseApi = ApiCdiResolverInterface::class;
    
    public function index()
    {
        return $this->model->orderBy('name', 'asc')->get();
    }
    
    public function create($dados)
    {
        if(!isset($dados['api'])){
            $resp = $this->baseApi->postUser($dados);
        }
        
        $dados['password'] = Hash::make($dados['password']);

        $user = $this->store($dados);

        if ($user) {
            $role = new Role(['role' => $dados['role']]);
            $user->role()->save($role);
            
            return true;
        }

        return false;
    }

    
    public function updateUser($dados, $id){
        
        $resp = $this->model->findOrFail($id);

        if (empty($resp)) {
            return false;
        }
        
        $dados['password'] = Hash::make($dados['password']);
        
        $resp->fill($dados);

        if (!$resp->save()) {
            return ['message' => 'Falha ao atualizar dados!', 'code' => 500];
        }

        return $resp;
    }

    public function getById($id)
    {
        $dados = $this->show($id);
        if (!$dados) {
            return false;
        }

        $dados->role = $dados->role()->first();

        return $dados;
    }
}