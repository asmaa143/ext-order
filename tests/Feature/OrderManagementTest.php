<?php

namespace Feature;
use App\Models\User;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class OrderManagementTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->token = auth()->login($this->user);
    }

    public function test_user_can_create_order()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/orders', [
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
            'customer_phone' => '+1234567890',
            'items' => [
                [
                    'product_name' => 'Product 1',
                    'quantity' => 2,
                    'price' => 99.99,
                ],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('orders', [
            'customer_email' => 'john@example.com',
        ]);
    }

    public function test_order_total_is_calculated_correctly()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/orders', [
            'customer_name' => 'Jane Doe',
            'customer_email' => 'jane@example.com',
            'items' => [
                ['product_name' => 'Item 1', 'quantity' => 2, 'price' => 50.00],
                ['product_name' => 'Item 2', 'quantity' => 1, 'price' => 25.00],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.total_amount', 125.00);
    }

    public function test_user_can_update_pending_order()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'pending',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson("/api/orders/{$order->id}", [
            'status' => 'confirmed',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'confirmed',
        ]);
    }

    public function test_cannot_delete_order_with_payments()
    {
        $order = Order::factory()->hasPayments(1)->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->deleteJson("/api/orders/{$order->id}");

        $response->assertStatus(500);
    }

    public function test_user_can_list_their_orders()
    {
        Order::factory()->count(5)->create(['user_id' => $this->user->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/orders');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'customer', 'items', 'total_amount', 'status']
            ]
        ]);
    }
}
