<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\WaitingList;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WaitingListTest extends TestCase
{
    use DatabaseTransactions; // Revierte todos los cambios automáticamente después de cada test

    /**
     * Test completo para el endpoint api/waiting-list/add
     * Verifica que se agrega un cliente a la lista de espera y se envía el correo
     */
    public function test_api_waiting_list_add_success(): void
    {
        // Crear un cliente temporal (se revierte automáticamente)
        $client = Client::create([
            'name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'phone' => '18093901572',
            'uuid' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
            'status' => true,
            'has_account' => false,
            'country' => 'DO',
        ]);

        // Mock de las respuestas de Mailchimp Marketing API
        // 1. Crear campaña
        // 2. Asignar contenido con template
        // 3. Enviar test email
        Http::fake([
            '*.api.mailchimp.com/3.0/campaigns' => Http::sequence()
                ->push(['id' => 'campaign-123', 'type' => 'regular'], 200) // Crear campaña
                ->push(['html' => '<html>...</html>'], 200), // Asignar contenido
            '*.api.mailchimp.com/3.0/campaigns/*/actions/test' => Http::response([], 204), // Enviar test
        ]);

        // Datos para la petición
        $requestData = [
            'client_id' => $client->id,
            'client_name' => 'Dariel',
            'client_last_name' => 'Abreu',
            'client_phone' => '18093901572',
            'client_email' => 'odalisdabreu@gmail.com',
        ];

        // Realizar la petición POST
        $response = $this->postJson('/api/waiting-list/add', $requestData);

        // Verificar que la respuesta sea exitosa
        $response->assertStatus(201)
            ->assertJson([
                'ok' => true,
                'message' => 'Cliente agregado a la lista de espera exitosamente',
            ])
            ->assertJsonStructure([
                'ok',
                'message',
                'data' => [
                    'id',
                    'client_id',
                    'created_at',
                    'updated_at',
                ]
            ]);

        // Verificar que se creó el registro en la waiting list
        $this->assertDatabaseHas('waiting_lists', [
            'client_id' => $client->id,
        ]);

        // Verificar que se actualizaron los datos del cliente
        $client->refresh();
        $this->assertEquals('Dariel', $client->name);
        $this->assertEquals('Abreu', $client->last_name);
        $this->assertEquals('odalisdabreu@gmail.com', $client->email);
        $this->assertEquals('18093901572', $client->phone);

        // Verificar que se hizo la petición a Mailchimp para crear la campaña
        Http::assertSent(function ($request) {
            return $request->url() && str_contains($request->url(), '/campaigns') 
                && $request->method() === 'POST';
        });
    }

    /**
     * Test para agregar cliente desde flow
     */
    public function test_api_waiting_list_add_with_flow(): void
    {
        // Crear un cliente temporal
        $client = Client::create([
            'name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'phone' => '18093901572',
            'uuid' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
            'status' => true,
            'has_account' => false,
            'country' => 'DO',
        ]);

        // Mock de las respuestas de Mailchimp Marketing API
        Http::fake([
            '*.api.mailchimp.com/3.0/campaigns' => Http::sequence()
                ->push(['id' => 'campaign-123', 'type' => 'regular'], 200)
                ->push(['html' => '<html>...</html>'], 200),
            '*.api.mailchimp.com/3.0/campaigns/*/actions/test' => Http::response([], 204),
        ]);

        // Flow JSON string con los datos del usuario
        $flowJson = json_encode([
            'screen_0_Nombre_0' => 'Dariel',
            'screen_0_Apellido_1' => 'Abreu',
            'screen_0_Correo_Electrnico_2' => 'odalisdabreu@gmail.com',
            'screen_0_Tlefono_3' => '18093901572',
        ]);

        // Datos para la petición con flow
        $requestData = [
            'flow' => $flowJson,
            'client_id' => $client->id,
        ];

        // Realizar la petición POST
        $response = $this->postJson('/api/waiting-list/add', $requestData);

        // Verificar que la respuesta sea exitosa
        $response->assertStatus(201)
            ->assertJson([
                'ok' => true,
                'message' => 'Cliente agregado a la lista de espera exitosamente',
            ]);

        // Verificar que se actualizaron los datos del cliente desde el flow
        $client->refresh();
        $this->assertEquals('Dariel', $client->name);
        $this->assertEquals('Abreu', $client->last_name);
        $this->assertEquals('odalisdabreu@gmail.com', $client->email);
    }

    /**
     * Test para validar que no se puede agregar un cliente que no existe
     */
    public function test_api_waiting_list_add_client_not_exists(): void
    {
        // Datos para la petición con un client_id que no existe
        $requestData = [
            'client_id' => 99999, // ID que no existe
            'client_name' => 'Dariel',
            'client_last_name' => 'Abreu',
            'client_phone' => '18093901572',
            'client_email' => 'odalisdabreu@gmail.com',
        ];

        // Realizar la petición POST
        $response = $this->postJson('/api/waiting-list/add', $requestData);

        // Verificar que se retorna un error de validación
        $response->assertStatus(422)
            ->assertJson([
                'ok' => false,
                'message' => 'Error de validación',
            ])
            ->assertJsonValidationErrors(['client_id']);
    }

    /**
     * Test para validar que no se puede agregar el mismo cliente dos veces
     */
    public function test_api_waiting_list_add_duplicate_client(): void
    {
        // Crear un cliente temporal
        $client = Client::create([
            'name' => 'Dariel',
            'last_name' => 'Abreu',
            'email' => 'odalisdabreu@gmail.com',
            'phone' => '18093901572',
            'uuid' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
            'status' => true,
            'has_account' => false,
            'country' => 'DO',
        ]);

        // Crear un registro en la waiting list
        WaitingList::create([
            'client_id' => $client->id,
        ]);

        // Mock de Mailchimp (aunque no debería llegar aquí)
        Http::fake([
            '*.api.mailchimp.com/3.0/*' => Http::response([], 200),
        ]);

        // Datos para la petición
        $requestData = [
            'client_id' => $client->id,
            'client_name' => 'Dariel',
            'client_last_name' => 'Abreu',
            'client_phone' => '18093901572',
            'client_email' => 'odalisdabreu@gmail.com',
        ];

        // Intentar agregar el mismo cliente nuevamente
        $response = $this->postJson('/api/waiting-list/add', $requestData);

        // Verificar que se retorna un error
        $response->assertStatus(400)
            ->assertJson([
                'ok' => false,
                'message' => 'El cliente ya se ha agregado a la lista de espera',
            ]);

        // Verificar que no se envió el correo
        Http::assertNothingSent();
    }

    /**
     * Test para validar campos requeridos
     */
    public function test_api_waiting_list_add_validation_errors(): void
    {
        // Petición sin campos requeridos
        $response = $this->postJson('/api/waiting-list/add', []);

        // Verificar que se retorna un error de validación
        $response->assertStatus(422)
            ->assertJson([
                'ok' => false,
                'message' => 'Error de validación',
            ])
            ->assertJsonValidationErrors(['client_id', 'client_name']);
    }

    /**
     * Test para validar formato de email
     */
    public function test_api_waiting_list_add_invalid_email(): void
    {
        // Crear un cliente temporal
        $client = Client::create([
            'name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'phone' => '18093901572',
            'uuid' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
            'status' => true,
            'has_account' => false,
            'country' => 'DO',
        ]);

        // Datos con email inválido
        $requestData = [
            'client_id' => $client->id,
            'client_name' => 'Dariel',
            'client_last_name' => 'Abreu',
            'client_phone' => '18093901572',
            'client_email' => 'email-invalido', // Email inválido
        ];

        // Realizar la petición POST
        $response = $this->postJson('/api/waiting-list/add', $requestData);

        // Verificar que se retorna un error de validación
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['client_email']);
    }

    /**
     * Test para verificar que el flow inválido se maneja correctamente
     */
    public function test_api_waiting_list_add_invalid_flow(): void
    {
        // Crear un cliente temporal
        $client = Client::create([
            'name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'phone' => '18093901572',
            'uuid' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
            'status' => true,
            'has_account' => false,
            'country' => 'DO',
        ]);

        // Flow JSON inválido
        $invalidFlowJson = '{"invalid": json}'; // JSON mal formado

        // Datos para la petición con flow inválido
        $requestData = [
            'flow' => $invalidFlowJson,
            'client_id' => $client->id,
        ];

        // Realizar la petición POST
        // El endpoint debería manejar el error del flow
        $response = $this->postJson('/api/waiting-list/add', $requestData);

        // El endpoint puede fallar o retornar error dependiendo de cómo maneje el flow inválido
        // Verificamos que al menos no retorna 201 (éxito)
        $this->assertNotEquals(201, $response->status());
    }

    /**
     * Test para verificar que se envía el correo a odalisdabreu@gmail.com
     */
    public function test_api_waiting_list_add_sends_email_to_odalisdabreu(): void
    {
        // Crear un cliente temporal
        $client = Client::create([
            'name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'phone' => '18093901572',
            'uuid' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
            'status' => true,
            'has_account' => false,
            'country' => 'DO',
        ]);

        // Mock de las respuestas de Mailchimp Marketing API
        Http::fake([
            '*.api.mailchimp.com/3.0/campaigns' => Http::sequence()
                ->push(['id' => 'campaign-123', 'type' => 'regular'], 200)
                ->push(['html' => '<html>...</html>'], 200),
            '*.api.mailchimp.com/3.0/campaigns/*/actions/test' => Http::response([], 204),
        ]);

        // Datos para la petición con el email odalisdabreu@gmail.com
        $requestData = [
            'client_id' => $client->id,
            'client_name' => 'Dariel',
            'client_last_name' => 'Abreu',
            'client_phone' => '18093901572',
            'client_email' => 'odalisdabreu@gmail.com',
        ];

        // Realizar la petición POST
        $response = $this->postJson('/api/waiting-list/add', $requestData);

        // Verificar que la respuesta sea exitosa
        $response->assertStatus(201);

        // Verificar que se hizo la petición a Mailchimp para enviar el test email
        Http::assertSent(function ($request) {
            $url = $request->url();
            $body = $request->body();
            $data = json_decode($body, true);
            
            // Verificar que es la petición de test email y contiene el correo correcto
            return str_contains($url, '/actions/test') 
                && isset($data['test_emails']) 
                && in_array('odalisdabreu@gmail.com', $data['test_emails']);
        });
    }
}
