<?php
namespace App\Http\Controllers\Api;

use App\DTOs\CreateOrderDTO;
use App\DTOs\UpdateOrderDTO;
use App\Filters\OrderFilter;
use App\Traits\ApiResponse;
use Illuminate\Routing\Controller;
use App\Http\Requests\CreateOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Services\OrderService;
use Illuminate\Http\Request;
class OrderController extends Controller
{
    use ApiResponse;

    public function __construct(
        private OrderService $orderService
    ) {
        $this->middleware('auth:api');
    }

    public function index(OrderFilter $filter)
    {
        $orders = $this->orderService->getOrders($filter, auth()->id());
        return $this->success(OrderResource::collection($orders));
    }

    public function store(CreateOrderRequest $request)
    {
        try {
            $dto = CreateOrderDTO::fromRequest($request->validated(), auth()->id());
            $order = $this->orderService->createOrder($dto);

            return $this->created(new OrderResource($order), 'Order created successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function show(int $id)
    {
        try {
            $order = $this->orderService->getOrderById($id);

            if ($order->user_id !== auth()->id()) {
                return $this->forbidden();
            }

            return $this->success(new OrderResource($order));
        } catch (\Exception $e) {
            return $this->notFound();
        }
    }

    public function update(UpdateOrderRequest $request, int $id)
    {
        try {
            $order = $this->orderService->getOrderById($id);

            if ($order->user_id !== auth()->id()) {
                return $this->forbidden();
            }

            $dto = UpdateOrderDTO::fromRequest($request->validated());
            $updatedOrder = $this->orderService->updateOrder($order, $dto);

            return $this->updated(new OrderResource($updatedOrder));
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function destroy(int $id)
    {
        try {
            $order = $this->orderService->getOrderById($id);

            if ($order->user_id !== auth()->id()) {
                return $this->forbidden();
            }

            $this->orderService->deleteOrder($order);

            return $this->deleted();
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
