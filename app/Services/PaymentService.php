<?php

namespace App\Services;

use App\Contracts\PaymentGatewayInterface;
use App\DTOs\ProcessPaymentDTO;
use App\Enums\OrderStatusEnum;
use App\Enums\PaymentStatusEnum;
use App\Filters\PaymentFilter;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentService
{

    public function __construct(
        private ?GatewaySwitcher $gatewaySwitcher = null
    ) {}
    public function processPayment(ProcessPaymentDTO $dto): Payment
    {
        try {
            DB::beginTransaction();

            $order = Order::findOrFail($dto->orderId);

            if (!$order->canBePaid()) {
                throw new \Exception(
                    "Order must be confirmed. Current: {$order->status->value}"
                );
            }

            if (bccomp($dto->amount, $order->total_amount, 2) !== 0) {
                throw new \Exception(
                    "Amount mismatch: {$dto->amount} != {$order->total_amount}"
                );
            }

            $payment = Payment::create([
                'order_id' => $dto->orderId,
                'payment_method' => $dto->paymentMethod,
                'amount' => $dto->amount,
                'status' => PaymentStatusEnum::PENDING,
                'payment_details' => $dto->paymentDetails,
            ]);

            $gateway = $this->getPaymentGateway($payment->payment_method);
            $payment->update(['status' => PaymentStatusEnum::PROCESSING]);

            $result = $gateway->process($payment);

            if ($result['success']) {
                $payment->markAsSuccessful(
                    $result['transaction_id'],
                    $result['gateway_response'] ?? []
                );

                $order->updateStatus(OrderStatusEnum::PAID);

                Log::info('Payment processed', [
                    'payment_id' => $payment->id,
                    'transaction_id' => $result['transaction_id'],
                ]);
            } else {
                $payment->markAsFailed($result['gateway_response'] ?? []);
                throw new \Exception(
                    $result['gateway_response']['error'] ?? 'Payment failed'
                );
            }

            DB::commit();
            return $payment->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    private function getPaymentGateway($paymentMethod): PaymentGatewayInterface
    {
        $serviceKey = $paymentMethod->serviceKey();

        if (!App::bound($serviceKey)) {
            throw new \Exception("Gateway '{$paymentMethod->value}' not configured");
        }

        return App::make($serviceKey);
    }

    public function refundPayment(Payment $payment): Payment
    {
        try {
            DB::beginTransaction();

            if (!$payment->isSuccessful()) {
                throw new \Exception('Only successful payments can be refunded');
            }

            if ($payment->status === PaymentStatusEnum::REFUNDED) {
                throw new \Exception('Already refunded');
            }

            $gateway = $this->getPaymentGateway($payment->payment_method);
            $result = $gateway->refund($payment);

            if ($result['success']) {
                $payment->update([
                    'status' => PaymentStatusEnum::REFUNDED,
                    'gateway_response' => array_merge(
                        $payment->gateway_response ?? [],
                        ['refund' => $result['gateway_response']]
                    ),
                ]);

                Log::info('Payment refunded', ['payment_id' => $payment->id]);
            } else {
                throw new \Exception($result['gateway_response']['error'] ?? 'Refund failed');
            }

            DB::commit();
            return $payment->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Refund error', ['payment_id' => $payment->id]);
            throw $e;
        }
    }

    public function getPayments(PaymentFilter $filter)
    {
        $query = Payment::with(['order', 'order.items']);
        $query = $filter->apply($query);

        $perPage = request()->get('per_page', 15);
        return $query->paginate($perPage);
    }

    public function getPaymentById(int $paymentId): Payment
    {
        return Payment::with(['order', 'order.items'])->findOrFail($paymentId);
    }

    public function getOrderPayments(int $orderId)
    {
        return Payment::where('order_id', $orderId)
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
