<?php

use App\User;

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

class UserTest extends TestCase
{
    use DatabaseTransactions;//para dar uma especie de rollback quando inserir algo, de forma automatica.

    protected $data = [];
    protected $api_token = [];
    //para funcionar a autenticação devemos descomentar no bootstrap/app.php
    // $app->routeMiddleware([
    //     'auth' => App\Http\Middleware\Authenticate::class,
    // ]);
    //e
    //$app->register(App\Providers\AuthServiceProvider::class);


    public function __construct(?string $name = null, array $data = [], string $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->data = [
            'name' => 'Name 1'.date('Ymdis').' '.rand(1, 100),
            'email' => 'example2@test.com',//vai retornar status code 422 se validação falhar no controller
            'password' => '12345',
            'password_confirmation' => '12345',
        ];
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testStore()
    {
        $this->api_token = ['api_token' => \App\User::where('api_token', '<>', '')->first()->api_token];
        //dentro do diretório bootstrap/app.php descomente para ativar o ORM e as Facades
        //$app->withFacades();
        //$app->withEloquent();
        //configurar o banco no aruqivo phpunit.xml na raiz se precisar

        $this->post('/api/user', $this->data, $this->api_token);//como vai enviar para cadastro deve ser um POST, a url deve ser /api/user
//        echo $this->response->content(); dessa forma se um email ja existir ele vai dizer que ja existe.

        //primeiro teste é saber se a pagina existe, o retorno é ok
        $this->assertResponseOk();

        //para pegar o conteudo retornado da pagina é o response content
        $response = (array) json_decode($this->response->content());//preciso converter a respostar para facilitar o teste.

        $this->assertArrayHasKey('name', $response);
        $this->assertArrayHasKey('email', $response);
        $this->assertArrayHasKey('id', $response);

        //depois de verificar se esse é o retorno eu quero receber o id.
        $this->seeInDatabase('users', [
           'name' => $this->data['name'],
           'email' => $this->data['email'],
        ]);
    }

    public function testAuthenticate()
    {
        $this->api_token = ['api_token' => \App\User::where('api_token', '<>', '')->first()->api_token];
        $this->post('/api/user', $this->data, $this->api_token);

        $this->assertResponseOk();

        //como os dados não ficam no banco eu preciso testar o login aqui mesmo

        $this->post('/api/authenticate', $this->data);

        $this->assertResponseOk();

        $response = (array) json_decode($this->response->content());

        $this->assertArrayHasKey('api_token', $response);
    }

    public function testShow()
    {
        $this->api_token = ['api_token' => \App\User::where('api_token', '<>', '')->first()->api_token];
        $user = User::first();

        $this->get('/api/user/'.$user->id, $this->api_token);

        $this->assertResponseOk();

        //para pegar o conteudo retornado da pagina é o response content
        $response = (array) json_decode($this->response->content());

        $this->assertArrayHasKey('name', $response);
        $this->assertArrayHasKey('email', $response);
        $this->assertArrayHasKey('id', $response);
    }

    public function testUpdateWithPassword()
    {
        $user = User::first();

        $this->api_token = ['api_token' => \App\User::where('api_token', '<>', '')->first()->api_token];

        $data = array(
            'name' => 'Name Changed'.date('Ymdis').' '.rand(1, 100),
            'email' => 'example'.date('Ymdis').'_'.rand(1, 100).'@test.com',
            'now_password' => '12345',
            'password' => '123456',
            'password_confirmation' => '123456',
        );

        $this->put('/api/user/'.$user->id, $data, $this->api_token);

        $this->assertResponseOk();

        $response = (array) json_decode($this->response->content());

        $this->assertArrayHasKey('name', $response);
        $this->assertArrayHasKey('email', $response);
        $this->assertArrayHasKey('id', $response);

        $this->notSeeInDatabase('users', array(//quando estamos atualizando esperamos que nao seja encontrado no banco o dado antigo, antes da atualização.
            'name' => $user->name,
            'email' => $user->email,
            'id' => $user->id,
        ));
    }

    public function testUpdateNotPassword()
    {
        $user = User::first();
        $this->api_token = ['api_token' => \App\User::where('api_token', '<>', '')->first()->api_token];

        $data = array(
            'name' => 'Name Changed'.date('Ymdis').' '.rand(1, 100),
            'email' => 'example'.date('Ymdis').'_'.rand(1, 100).'@test.com',
        );

        $this->put('/api/user/'.$user->id, $data, $this->api_token);

        $this->assertResponseOk();

        $response = (array) json_decode($this->response->content());

        $this->assertArrayHasKey('name', $response);
        $this->assertArrayHasKey('email', $response);
        $this->assertArrayHasKey('id', $response);

        $this->notSeeInDatabase('users', array(//quando estamos atualizando esperamos que nao seja encontrado no banco o dado antigo, antes da atualização.
            'name' => $user->name,
            'email' => $user->email,
            'id' => $user->id,
        ));
    }

    public function testIndex()
    {
        $this->api_token = ['api_token' => \App\User::where('api_token', '<>', '')->first()->api_token];
        $this->get('/api/users', $this->api_token);

        $this->assertResponseOk();

        //para ver se esta vindo da estrutura que eu quero, que é um objeto json
        $this->seeJsonStructure([
            '*' => [
                'id',
                'name',
                'email'
            ]
        ]);
    }

    public function testDestroy()
    {
        $user = User::first();
        $this->api_token = ['api_token' => \App\User::where('api_token', '<>', '')->first()->api_token];

        $this->delete('/api/user/'.$user->id, $this->api_token);

        $this->assertResponseOk();

        $this->assertEquals('Removido com sucesso!', $this->response->content());

        $this->notSeeInDatabase('users', array(//quando estamos deletando esperamos que nao seja encontrado no banco o dado antigo, antes da exclusão.
            'name' => $user->name,
            'email' => $user->email,
            'id' => $user->id,
        ));
    }
}
