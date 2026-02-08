<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $orderService;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->orderService = new OrderService();
        $this->user = User::factory()->create();
    }

    public function test_can_create_order_with_items()
    {
        $orderData = [
            'customer_name' => 'Test Customer',
            'customer_email' => 'test@example.com',
            'items' => [
                ['product_name' => 'Product 1', 'quantity' => 2, 'price' => 50],
            ],
        ];

        $order = $this->orderService->createOrder($orderData, $this->user->id);

        $this->assertNotNull($order);
        $this->assertEquals('Test Customer', $order->customer_name);
        $this->assertEquals(100, $order->total_amount);
        $this->assertCount(1, $order->items);
    }

    public function test_total_amount_is_calculated_from_items()
    {
        $orderData = [
            'customer_name' => 'Test',
            'customer_email' => 'test@example.com',
            'items' => [
                ['product_name' => 'A', 'quantity' => 2, 'price' => 10],
                ['product_name' => 'B', 'quantity' => 1, 'price' => 30],
            ],
        ];

        $order = $this->orderService->createOrder($orderData, $this->user->id);

        $this->assertEquals(50, $order->total_amount);
    }
}
