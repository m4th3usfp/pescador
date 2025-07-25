<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Fisherman;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    
    public function test_usuario_com_credenciais_validas_consegue_logar()
    {
        // Cria a cidade "Frutal"
        $cidade = City::factory()->create(['name' => 'Frutal']);
        // dd($cidade->name);
        // Cria um usuário vinculado à cidade
        $user = User::factory()->create([
            'name' => 'Matheus',
            'city' => $cidade->name,
            'city_id' => $cidade->id,
            'password' => Hash::make('fanuchy98'),
        ]);
        // dd($cidade, $user);

        // Envia o formulário de login (não precisa passar _token em testes)
        $response = $this->post('/login', [
            'name' => 'Matheus',
            'city' => 'Frutal', // Só se o login usa esse campo personalizado
            'password' => 'fanuchy98',
        ]);

        // Verifica se foi redirecionado corretamente
        $response->assertRedirect('/listagem');

        // Verifica se o usuário está autenticado
        $this->assertAuthenticatedAs($user);
    }


    public function test_usuario_com_credenciais_invalidas_nao_loga()
    {
        $cidade = City::factory()->create(['name' => 'Frutal']);

        $user = User::factory()->create([
            'name' => 'Matheus',
            'city' => $cidade->name,
            'city_id' => $cidade->id,
            'password' => Hash::make('fanuchy98'),
        ]);

        $wrong_user = [
            'name' => 'Rodrigo',
            'city' => 'Araras',
            'city_id' => '1',
            'password' => Hash::make('fanuchy98'),
        ];

        $response = $this->post('/login', [$wrong_user]);

        $response->assertRedirect();

        $response->assertSessionHasErrors();

        $this->assertGuest();

    }

    public function test_acessar_rota_cadastro()
    {
        $cidade = City::factory()->create(['name' => 'Frutal']);

        // dd($cidade);

        $user = User::factory()->create([
            'name' => 'Matheus',
            'city' => $cidade->name,
            'city_id' => $cidade->id,
            'password' => Hash::make('fanuchy98'), 
        ]);

        $this->actingAs($user);

        $response = $this->get('/Cadastro');

        $response->assertOk();

        $response->assertViewIs('Cadastro');

    }

    public function test_usuario_autenticado_faz_post()
    {
        // Cria a cidade e o usuário
        $cidade = City::factory()->create(['name' => 'Frutal']);

        $record_number = (int) Fisherman::max('record_number');

        $last_record_number = $record_number + 1;

        $user = User::factory()->create([
            'name' => 'Matheus',
            'city' => $cidade->name,
            'city_id' => $cidade->id,
            'password' => Hash::make('fanuchy98'),
        ]);

        // Autentica como esse usuário
        $this->actingAs($user);

        // Dados para criar um novo pescador
        $dadosPescador = [
            'record_number' => $last_record_number,
            'name' => 'João Pescador',
            'email' => 'joao@hotmail.com',
            'city_id' => $cidade->id,
        ];

        // Envia requisição POST
        $response = $this->post('/Cadastro', $dadosPescador);
        // Verifica se foi redirecionado corretamente (ajuste conforme o comportamento da sua aplicação)
        $response->assertRedirect('/listagem');

        // Verifica se o pescador foi criado no banco
        $this->assertDatabaseHas('fishermen', [
            'name' => 'João Pescador',
            'email' => 'joao@hotmail.com',
            'record_number' => $last_record_number
        ]);
    }

    public function test_usuario_autenticado_faz_put()
    {
        $cidade = City::factory()->create(['name' => 'Frutal']);
    
        $record_number = (int) Fisherman::max('record_number');
        $last_record_number = $record_number + 1;
    
        $user = User::factory()->create([
            'name' => 'Matheus',
            'city' => $cidade->name,
            'city_id' => $cidade->id,
            'password' => Hash::make('fanuchy98'),
        ]);
    
        $this->actingAs($user);
    
        $dadosPescador = [
            'record_number' => $last_record_number,
            'name' => 'João Pescador',
            'email' => 'joao@hotmail.com',
            'city' => 'Araras',
            'city_id' => $user->city_id,
        ];
    
        $this->post('/Cadastro', $dadosPescador);
    
        $getResponse = $this->get('/listagem');
        $getResponse->assertStatus(200);
        $getResponse->assertSee('João Pescador');
    
        $fisherman = Fisherman::where('record_number', $last_record_number)->first();
    
        $dados_editados = [
            'record_number' => $record_number,
            'name' => 'matheus Fanuchy',
            'email' => 'matheus@hotmail.com',
            'city' => 'Frutal',
            'city_id' => $user->city_id,
        ];
    
        $put_response = $this->put("/listagem/{$fisherman->id}", $dados_editados);
        $put_response->assertRedirect(('listagem'));

        $getAfterPut = $this->get('/listagem');
        $getAfterPut->assertSee('matheus Fanuchy');
    
        $this->assertDatabaseHas('fishermen', [
            'name' => 'matheus Fanuchy',
            'email' => 'matheus@hotmail.com',
            'city' => 'Frutal',
        ]);
    }

    public function test_usuario_autenticado_faz_delete()
    {
        $cidade = City::factory()->create(['name' => 'Uberlandia']);
        
        $record_number = (int) Fisherman::max('record_number');
        $last_record_number = $record_number + 1;

        $user = User::factory()->create([
            'name' => 'matheus',
            'city' => $cidade->name,
            'city_id' => $cidade->id,
            'password' => Hash::make('fanuchy98')
        ]);

        $this->actingAs($user);

        $dadosPescador = [
            'record_number' => $last_record_number,
            'name' => 'Rodrigo Fanuchy',
            'email' => 'rodrigo@hotmail.com',
            'city' => 'Araras',
            'city_id' => $user->city_id,
        ];

        $this->post('/Cadastro', $dadosPescador);
        
        $getResponse = $this->get('/listagem');

        $getResponse->assertStatus(200);

        $getResponse->assertSee('Rodrigo Fanuchy');

        $fisherman = Fisherman::where('record_number', $last_record_number)->first();
        $this->assertNotNull($fisherman);

        $deleteResponse = $this->delete("/listagem/{$fisherman->id}");
        $deleteResponse->assertRedirect('/listagem');

        $this->assertDatabaseMissing('fishermen', [
            'id' => $fisherman->id,
            'name' => 'Rodrigo Fanuchy'
        ]);
    }
    
}
