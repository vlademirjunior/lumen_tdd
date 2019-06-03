<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function index()
    {
        return User::all();
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|max:255',
            'email' => 'required|email|unique:users|max:255',
            'password' => 'sometimes|required|max:255|confirmed',
            'now_password' => 'sometimes|required|max:255|different:password'
        ]);

        $user = new User($request->all());
        $user->password = Hash::make($request->password);
        $user->api_token = Str::random(80);

        $user->save();

        return $user;
    }

    public function authenticate(Request $request)
    {
        $data = $request->only('email', 'password');

        //agora preciso pegar o token que esta no banco de dados
        $user = User::whereEmail($data['email'])
            ->first();

        if (Hash::check($data['password'], $user->password)) {
            //gerar um token novo a cada login
            $user->api_token = Str::random(80);
            $user->update();

            return [
                'api_token' => $user->api_token
            ];

        } else {
            return response('Usuário ou senha inválido!', 401);
        }
    }

    public function show($id)
    {
        return User::find($id);
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'name' => 'required|max:255',
            'email' => "required|email|unique:users,email,{$request->segment(3)},id|max:255",
            'password' => 'sometimes|required|max:255|confirmed',
            'now_password' => 'sometimes|required|max:255|different:password',
        ]);

        $user = User::find($id);

        $user->name = $request->name;
        $user->email = $request->email;

        $user->password = $request->has('now_password') ?
            Hash::make($request->password) :
            $user->password;//se veio a senha antiga é por que está querendo trocar a senha, senão eu fico com a mesma senha que estava.

        $user->update();

        return $user;
    }

    public function destroy($id)
    {
        if (User::destroy($id)) {
            return response('Removido com sucesso!', 200);
        }

        return response('Erro ao remover!', 401);
    }
}
