<?php

namespace Feature;
use App\Models\User;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class PaymentProcessingTest extends TestCase
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

    public function test_can_process_credit_card_payment()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'confirmed',
            'total_amount' => 100.00,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/payments', [
            'order_id' => $order->id,
            'payment_method' => 'credit_card',
            'amount' => 100.00,
            'payment_details' => [
                'card_number' => '4242424242424242',
                'expiry_month' => '12',
                'expiry_year' => '2025',
                'cvv' => '123',
            ],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'amount' => 100.00,
        ]);
    }

    public function test_cannot_pay_pending_order()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'pending',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/payments', [
            'order_id' => $order->id,
            'payment_method' => 'credit_card',
            'amount' => $order->total_amount,
            'payment_details' => [
                'card_number' => '4242424242424242',
                'expiry_month' => '12',
                'expiry_year' => '2025',
                'cvv' => '123',
            ],
        ]);

        $response->assertStatus(422);
    }

    public function test_payment_amount_must_match_order_total()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'confirmed',
            'total_amount' => 100.00,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/payments', [
            'order_id' => $order->id,
            'payment_method' => 'credit_card',
            'amount' => 50.00, // Wrong amount
            'payment_details' => [
                'card_number' => '4242424242424242',
                'expiry_month' => '12',
                'expiry_year' => '2025',
                'cvv' => '123',
            ],
        ]);

        $response->assertStatus(422);
    }

    public function test_successful_payment_updates_order_status()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'confirmed',
            'total_amount' => 100.00,
        ]);

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/payments', [
            'order_id' => $order->id,
            'payment_method' => 'credit_card',
            'amount' => 100.00,
            'payment_details' => [
                'card_number' => '4242424242424242',
                'expiry_month' => '12',
                'expiry_year' => '2025',
                'cvv' => '123',
            ],
        ]);

        $order->refresh();
        $this->assertEquals('paid', $order->status->value);
    }
}
