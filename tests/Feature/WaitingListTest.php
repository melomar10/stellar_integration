<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\WaitingList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WaitingListTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test para agregar un cliente a la lista de espera y verificar el envío de correo
     */
    public function test_add_client_to_waiting_list_sends_email(): void
    {
        // Crear un cliente de prueba
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

        // Mock de la respuesta de Mailchimp Transactional API
        Http::fake([
            'mandrillapp.com/api/1.0/messages/send-template.json' => Http::response([
                [
                    'email' => 'odalisdabreu@gmail.com',
                    'status' => 'sent',
                    '_id' => 'test-message-id-123',
                ]
            ], 200)
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

        // Verificar que se hizo la petición a Mailchimp con el correo correcto
        Http::assertSent(function ($request) {
            $body = $request->body();
            $data = json_decode($body, true);
            
            return isset($data['message']['to'][0]['email']) 
                && $data['message']['to'][0]['email'] === 'odalisdabreu@gmail.com'
                && isset($data['message']['to'][0]['name'])
                && $data['message']['to'][0]['name'] === 'Dariel'
                && isset($data['template_name'])
                && $data['template_name'] === 'Bienvenida a EU - Domipagos';
        });
    }

    /**
     * Test para verificar que no se puede agregar el mismo cliente dos veces
     */
    public function test_cannot_add_same_client_twice_to_waiting_list(): void
    {
        // Crear un cliente de prueba
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

        // Mock de la respuesta de Mailchimp (aunque no debería llegar aquí)
        Http::fake([
            'mandrillapp.com/api/1.0/messages/send-template.json' => Http::response([], 200)
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

        // Verificar que no se envió el correo (solo debería haber un registro)
        Http::assertNothingSent();
    }

    /**
     * Test para agregar cliente desde flow
     */
    public function test_add_client_to_waiting_list_from_flow(): void
    {
        // Crear un cliente de prueba
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

        // Mock de la respuesta de Mailchimp Transactional API
        Http::fake([
            'mandrillapp.com/api/1.0/messages/send-template.json' => Http::response([
                [
                    'email' => 'odalisdabreu@gmail.com',
                    'status' => 'sent',
                    '_id' => 'test-message-id-123',
                ]
            ], 200)
        ]);

        // Flow JSON string
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

        // Verificar que se hizo la petición a Mailchimp con el correo correcto
        Http::assertSent(function ($request) {
            $body = $request->body();
            $data = json_decode($body, true);
            
            return isset($data['message']['to'][0]['email']) 
                && $data['message']['to'][0]['email'] === 'odalisdabreu@gmail.com';
        });
    }
}

