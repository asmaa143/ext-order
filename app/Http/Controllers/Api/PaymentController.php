<?php
namespace App\Http\Controllers\Api;

use App\DTOs\ProcessPaymentDTO;
use App\Filters\PaymentFilter;
use App\Traits\ApiResponse;
use Illuminate\Routing\Controller;
use App\Http\Requests\ProcessPaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Services\PaymentService;
use Illuminate\Http\Request;
class PaymentController extends Controller
{
    use ApiResponse;
    public function __construct(
        private PaymentService $paymentService
    ) {

    }

    public function index(PaymentFilter $filter)
    {
        $payments = $this->paymentService->getPayments($filter);
        return $this->success(PaymentResource::collection($payments));
    }

    public function store(ProcessPaymentRequest $request)
    {
        try {
            $dto = ProcessPaymentDTO::fromRequest($request->validated());
            $payment = $this->paymentService->processPayment($dto);

            return $this->created(new PaymentResource($payment), 'Payment processed successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    public function show(int $id)
    {
        try {
            $payment = $this->paymentService->getPaymentById($id);
            return $this->success(new PaymentResource($payment));
        } catch (\Exception $e) {
            return $this->notFound();
        }
    }

    public function orderPayments(int $orderId)
    {
        try {
            $payments = $this->paymentService->getOrderPayments($orderId);
            return $this->success(PaymentResource::collection($payments));
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
