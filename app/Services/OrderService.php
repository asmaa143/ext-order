<?php

namespace App\Services;


use App\DTOs\CreateOrderDTO;
use App\DTOs\UpdateOrderDTO;
use App\Enums\OrderStatusEnum;
use App\Filters\OrderFilter;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function createOrder(CreateOrderDTO $dto): Order
    {
        try {
            DB::beginTransaction();

            $order = Order::create([
                'user_id' => $dto->userId,
                'customer_name' => $dto->customerName,
                'customer_email' => $dto->customerEmail,
                'customer_phone' => $dto->customerPhone,
                'status' => OrderStatusEnum::PENDING,
                'total_amount' => 0,
                'notes' => $dto->notes,
            ]);

            $totalAmount = 0;
            foreach ($dto->items as $itemData) {
                $item = OrderItem::create([
                    'order_id' => $order->id,
                    'product_name' => $itemData['product_name'],
                    'quantity' => $itemData['quantity'],
                    'price' => $itemData['price'],
                ]);

                $totalAmount += $item->subtotal;
            }

            $order->update(['total_amount' => $totalAmount]);

            DB::commit();
            return $order->load('items');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function updateOrder(Order $order, UpdateOrderDTO $dto): Order
    {
        if (!$order->canBeUpdated()) {
            throw new \Exception('Order cannot be updated in its current status');
        }

        try {
            DB::beginTransaction();

            $updateData = array_filter([
                'customer_name' => $dto->customerName,
                'customer_email' => $dto->customerEmail,
                'customer_phone' => $dto->customerPhone,
                'notes' => $dto->notes,
            ], fn($value) => $value !== null);

            if (!empty($updateData)) {
                $order->update($updateData);
            }

            if ($dto->items !== null) {
                $order->items()->delete();

                $totalAmount = 0;
                foreach ($dto->items as $itemData) {
                    $item = OrderItem::create([
                        'order_id' => $order->id,
                        'product_name' => $itemData['product_name'],
                        'quantity' => $itemData['quantity'],
                        'price' => $itemData['price'],
                    ]);

                    $totalAmount += $item->subtotal;
                }

                $order->update(['total_amount' => $totalAmount]);
            }

            if ($dto->status && OrderStatusEnum::isValid($dto->status)) {
                $order->updateStatus(OrderStatusEnum::from($dto->status));
            }

            DB::commit();
            return $order->fresh(['items']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function deleteOrder(Order $order): bool
    {
        if ($order->payments()->exists()) {
            throw new \Exception('Cannot delete order with payments');
        }

        try {
            DB::beginTransaction();
            $order->items()->delete();
            $deleted = $order->delete();
            DB::commit();
            return $deleted;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getOrders(OrderFilter $filter, ?int $userId = null)
    {
        $query = Order::with(['items', 'payments']);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $query = $filter->apply($query);

        $perPage = request()->get('per_page', 15);
        return $query->paginate($perPage);
    }

    public function getOrderById(int $orderId): Order
    {
        return Order::with(['items', 'payments'])->findOrFail($orderId);
    }

    public function confirmOrder(Order $order): Order
    {
        $order->updateStatus(OrderStatusEnum::CONFIRMED);
        return $order;
    }

    public function cancelOrder(Order $order): Order
    {
        if ($order->payments()->where('status', 'successful')->exists()) {
            throw new \Exception('Cannot cancel order with successful payments');
        }

        $order->updateStatus(OrderStatusEnum::CANCELLED);
        return $order;
    }
}
