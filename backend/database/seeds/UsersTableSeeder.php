<?php
use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::create([
            'name' => 'Jorcelino',
            'login' => 'admin',
            'email' => 'jorcelino@admin.com',
            'password' => Hash::make('2022'),
            
        ])->each(function($user) {

            if(!$user->role()->first()){
                $user->role()->save(factory(App\Models\Role::class)->make());
            }

        });
        
        User::create([
            'name' => 'Developer',
            'login' => 'developer',
            'email' => 'dev@admin.com',
            'password' => Hash::make('2022'),
            
        ])->each(function($user) {

            if(!$user->role()->first()){
                $user->role()->save(factory(App\Models\Role::class)->make());
            }

        });
    }
}
