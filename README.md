# 🚀 Laravel Order & Payment Management API

A professional, extensible Laravel API for managing orders and payment processing with multiple payment gateways using the Strategy Pattern.

[![Laravel](https://img.shields.io/badge/Laravel-12.x-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.3%2B-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

## 📋 Table of Contents

- [Features](#features)
- [System Requirements](#system-requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Payment Gateway Extensibility](#payment-gateway-extensibility)
- [API Documentation](#api-documentation)
- [Testing](#testing)
- [Architecture](#architecture)
- [Contributing](#contributing)

---

## ✨ Features

- ✅ **Order Management**: Full CRUD operations with status tracking
- ✅ **Multiple Payment Gateways**: Credit Card (Stripe), PayPal, Bank Transfer, Cash on Delivery
- ✅ **Strategy Pattern**: Easily add new payment gateways without modifying core code
- ✅ **Smart Gateway Selection**: Automatic gateway recommendation based on user history and order amount
- ✅ **JWT Authentication**: Secure API with JSON Web Tokens
- ✅ **RESTful API**: Clean, standardized endpoints
- ✅ **Request Validation**: Form Requests with custom error messages
- ✅ **DTOs (Data Transfer Objects)**: Type-safe data handling
- ✅ **Advanced Filtering**: Query filters for orders and payments
- ✅ **Comprehensive Testing**: Feature and Unit tests included

---

## 🔧 System Requirements

- PHP >= 8.2
- Composer
- MySQL 8.0
- Redis (optional, for caching)
---

## 📦 Installation

### Option 1: Standard Installation

```bash
# 1. Clone the repository
git clone https://github.com/your-repo/order-payment-api.git
cd order-payment-api

# 2. Install dependencies
composer install

# 3. Copy environment file
cp .env.example .env

# 4. Generate application key
php artisan key:generate

# 5. Generate JWT secret
php artisan jwt:secret

# 6. Configure database in .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=order_payment_db
DB_USERNAME=root
DB_PASSWORD=

# 7. Run migrations
php artisan migrate

# 8. Seed database (optional)
php artisan db:seed

# 9. Start the server
php artisan serve
```

### Option 2: Docker Installation

```bash
# 1. Clone the repository
git clone https://github.com/asmaa143/ext-order.git
cd ext-order

# 2. Copy environment file
cp .env.example .env


# 3. Install dependencies
 composer install

# 4. Generate keys
 php artisan key:generate
 php artisan jwt:secret

# 5. Run migrations
 php artisan migrate --seed
```

---

## ⚙️ Configuration

### Payment Gateway Configuration

All payment gateways are configured via the `.env` file:

```env
# =====================================================
# PAYMENT GATEWAY CONFIGURATION
# =====================================================

# Default Gateway
DEFAULT_PAYMENT_GATEWAY=credit_card

# Stripe (Credit Card)
STRIPE_ENABLED=true
STRIPE_MODE=test
STRIPE_SECRET_KEY=sk_test_51xxxxxxxxxxxxx
STRIPE_PUBLIC_KEY=pk_test_51xxxxxxxxxxxxx
STRIPE_WEBHOOK_SECRET=whsec_xxxxxxxxxxxxxx

# PayPal
PAYPAL_ENABLED=true
PAYPAL_MODE=sandbox
PAYPAL_CLIENT_ID=AXxxxxxxxxxxxxx
PAYPAL_SECRET=EJxxxxxxxxxxxxx

# Bank Transfer
BANK_TRANSFER_ENABLED=true
BANK_TRANSFER_PROCESSING_TIME=3-5 business days

# Cash on Delivery
COD_ENABLED=true
COD_MAX_AMOUNT=5000
```

### Getting API Keys

#### Stripe
1. Go to [Stripe Dashboard](https://dashboard.stripe.com/apikeys)
2. Copy your **Secret Key** and **Public Key**
3. For webhooks: [Stripe Webhooks](https://dashboard.stripe.com/webhooks)

#### PayPal
1. Go to [PayPal Developer](https://developer.paypal.com/)
2. Create an app
3. Copy **Client ID** and **Secret**

---

## 🔌 Payment Gateway Extensibility

### Architecture Overview

The system uses the **Strategy Pattern** to make adding new payment gateways incredibly easy:

```
┌─────────────────────────────────────────┐
│     PaymentGatewayInterface             │
│  - process(Payment): array              │
│  - refund(Payment): array               │
│  - verify(transactionId): array         │
└─────────────────────────────────────────┘
                    ▲
                    │ implements
        ┌───────────┴───────────┐
        │                       │
┌───────────────┐    ┌──────────────────┐
│ CreditCard    │    │   PayPal         │
│ Payment       │    │   Payment        │
└───────────────┘    └──────────────────┘
```

### Adding a New Payment Gateway

Adding a new gateway requires **4 simple steps**:

#### Step 1: Create Gateway Class

Create a new file: `app/Services/PaymentGateways/NewGatewayPayment.php`

```php
<?php

namespace App\Services\PaymentGateways;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;

class NewGatewayPayment implements PaymentGatewayInterface
{
    private string $apiKey;

    public function __construct()
    {
        // Load config from .env
        $this->apiKey = config('payment.gateways.new_gateway.api_key');
        
        if (!$this->apiKey) {
            throw new \Exception('New Gateway not configured');
        }
    }

    public function process(Payment $payment): array
    {
        try {
            // Your payment processing logic here
            // Example: Call API, validate, etc.
            
            $transactionId = 'NEW-' . uniqid();
            
            Log::info('Payment processed via New Gateway', [
                'payment_id' => $payment->id,
                'transaction_id' => $transactionId,
            ]);

            return [
                'success' => true,
                'transaction_id' => $transactionId,
                'gateway_response' => [
                    'status' => 'completed',
                ],
            ];
        } catch (\Exception $e) {
            Log::error('New Gateway error', ['error' => $e->getMessage()]);
            
            return [
                'success' => false,
                'transaction_id' => null,
                'gateway_response' => ['error' => $e->getMessage()],
            ];
        }
    }

    public function refund(Payment $payment): array
    {
        // Your refund logic
        $refundId = 'REFUND-' . uniqid();

        return [
            'success' => true,
            'refund_id' => $refundId,
            'gateway_response' => ['status' => 'refunded'],
        ];
    }

    public function verify(string $transactionId): array
    {
        // Your verification logic
        return [
            'success' => true,
            'status' => 'verified',
            'gateway_response' => ['id' => $transactionId],
        ];
    }
}
```

#### Step 2: Register in Service Provider

Edit: `app/Providers/PaymentServiceProvider.php`

```php
public function register(): void
{
    // Existing gateways
    $this->app->bind('payment.credit_card', CreditCardPayment::class);
    $this->app->bind('payment.paypal', PayPalPayment::class);
    $this->app->bind('payment.bank_transfer', BankTransferPayment::class);
    $this->app->bind('payment.cash_on_delivery', CashOnDeliveryPayment::class);
    
    // ✨ Add your new gateway
    $this->app->bind('payment.new_gateway', NewGatewayPayment::class);
}
```

#### Step 3: Add to Enum

Edit: `app/Enums/PaymentMethodEnum.php`

```php
enum PaymentMethodEnum: string
{
    use EnumHelpers;

    case CREDIT_CARD = 'credit_card';
    case PAYPAL = 'paypal';
    case BANK_TRANSFER = 'bank_transfer';
    case CASH_ON_DELIVERY = 'cash_on_delivery';
    
    // ✨ Add your new gateway
    case NEW_GATEWAY = 'new_gateway';

    public function label(): string
    {
        return match ($this) {
            self::CREDIT_CARD => 'Credit Card',
            self::PAYPAL => 'PayPal',
            self::BANK_TRANSFER => 'Bank Transfer',
            self::CASH_ON_DELIVERY => 'Cash on Delivery',
            
            // ✨ Add label
            self::NEW_GATEWAY => 'New Gateway',
        };
    }
}
```

#### Step 4: Add Configuration

Edit: `config/payment.php`

```php
return [
    // ... existing config

    'new_gateway' => [
        'enabled' => env('NEW_GATEWAY_ENABLED', true),
        'api_key' => env('NEW_GATEWAY_API_KEY'),
        'api_secret' => env('NEW_GATEWAY_SECRET'),
        'mode' => env('NEW_GATEWAY_MODE', 'sandbox'),
    ],
];
```

Add to `.env`:

```env
NEW_GATEWAY_ENABLED=true
NEW_GATEWAY_API_KEY=your_api_key
NEW_GATEWAY_SECRET=your_secret
NEW_GATEWAY_MODE=sandbox
```

### ✅ Done! Your New Gateway is Ready

**That's it!** No changes to:
- Controllers
- Models
- Routes
- Business Logic

The system will **automatically**:
- ✅ Detect your new gateway
- ✅ Include it in available gateways
- ✅ Process payments through it
- ✅ Handle refunds and verification



## 📚 API Documentation

Full API documentation is available at:

**[Postman Documentation](https://documenter.getpostman.com/view/47778376/2sBXc8qQCn)**

### Quick Start Examples

#### 1. Register User
```bash
POST /api/auth/register
Content-Type: application/json

{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "SecurePass123!",
    "password_confirmation": "SecurePass123!"
}
```

#### 2. Login
```bash
POST /api/auth/login
Content-Type: application/json

{
    "email": "john@example.com",
    "password": "SecurePass123!"
}
```

#### 3. Create Order
```bash
POST /api/orders
Authorization: Bearer {token}
Content-Type: application/json

{
    "customer_name": "Jane Smith",
    "customer_email": "jane@example.com",
    "customer_phone": "+1234567890",
    "items": [
        {
            "product_name": "Laptop",
            "quantity": 1,
            "price": 999.99
        }
    ]
}
```

#### 4. Get Available Payment Gateways
```bash
GET /api/payment-gateways/available?amount=999.99
Authorization: Bearer {token}
```

#### 5. Process Payment
```bash
POST /api/payments
Authorization: Bearer {token}
Content-Type: application/json

{
    "order_id": 1,
    "payment_method": "credit_card",
    "amount": 999.99,
    "payment_details": {
        "card_number": "4242424242424242",
        "expiry_month": "12",
        "expiry_year": "2025",
        "cvv": "123"
    }
}
```

---

## 🧪 Testing

### Run All Tests
```bash
php artisan test
```

### Run Specific Test Suite
```bash
# Feature tests
php artisan test --testsuite=Feature

# Unit tests
php artisan test --testsuite=Unit

# Specific test file
php artisan test tests/Feature/OrderManagementTest.php
```

### Test Coverage
```bash
php artisan test --coverage
```

---

## 🏗️ Architecture

### Design Patterns Used

1. **Strategy Pattern**: Payment gateways
2. **Repository Pattern**: Service layer
3. **DTO Pattern**: Type-safe data transfer
4. **Filter Pattern**: Query filtering
5. **Trait Pattern**: Code reusability (ApiResponse, EnumHelpers)

### Project Structure

```
app/
├── Contracts/
│   └── PaymentGatewayInterface.php
├── DTOs/
│   ├── CreateOrderDTO.php
│   ├── UpdateOrderDTO.php
│   └── ProcessPaymentDTO.php
├── Enums/
│   ├── OrderStatusEnum.php
│   ├── PaymentStatusEnum.php
│   └── PaymentMethodEnum.php
├── Filters/
│   ├── QueryFilter.php (Base)
│   ├── OrderFilter.php
│   └── PaymentFilter.php
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       ├── AuthController.php
│   │       ├── OrderController.php
│   │       └── PaymentController.php
│   ├── Requests/
│   │   ├── CreateOrderRequest.php
│   │   ├── UpdateOrderRequest.php
│   │   └── ProcessPaymentRequest.php
│   └── Resources/
│       ├── OrderResource.php
│       └── PaymentResource.php
├── Models/
│   ├── User.php
│   ├── Order.php
│   ├── OrderItem.php
│   └── Payment.php
├── Services/
│   ├── AuthService.php
│   ├── OrderService.php
│   ├── PaymentService.php
│   ├── GatewaySwitcher.php
│   └── PaymentGateways/
│       ├── CreditCardPayment.php
│       ├── PayPalPayment.php
│       ├── BankTransferPayment.php
│       └── CashOnDeliveryPayment.php
└── Traits/
    ├── ApiResponse.php
    ├── EnumHelpers.php
    └── Filterable.php
```

---
