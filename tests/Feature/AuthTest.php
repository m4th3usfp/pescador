<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_usuario_com_credenciais_validas_consegue_logar()
    {
        // Cria a cidade "Frutal"
        $cidade = City::factory()->create(['name' => 'Frutal']);
    
        // Cria um usuário vinculado à cidade
        $user = User::factory()->create([
            'name' => 'Matheus',
            'city' => 'Frutal',
            'password' => Hash::make('fanuchy98'),
        ]);

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
}
