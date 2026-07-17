# TriPay Service Guide

Panduan penggunaan `App\Services\TriPayService`, client internal TriPay berbasis Laravel HTTP Client.

Service ini mencakup payment instruction, merchant, closed payment, open payment, e-wallet, signature, callback validation, serta logging respons. Service ini tidak bergantung pada SDK TriPay pihak ketiga.

## Daftar Isi

- [Persiapan](#persiapan)
- [Quick Start](#quick-start)
- [Bentuk Respons](#bentuk-respons)
- [Payment dan Merchant](#payment-dan-merchant)
- [Closed Payment](#closed-payment)
- [Open Payment](#open-payment)
- [E-wallet](#e-wallet)
- [Callback](#callback)
- [Error Handling](#error-handling)
- [Logging](#logging)
- [API Reference](#api-reference)
- [Troubleshooting](#troubleshooting)

## Persiapan

### 1. Isi credential

Tambahkan credential sandbox ke `.env`:

```dotenv
TRIPAY_MERCHANT_CODE=merchant_code_sandbox
TRIPAY_API_KEY=api_key_sandbox
TRIPAY_PRIVATE_KEY=private_key_sandbox
TRIPAY_ENV=development
TRIPAY_TIMEOUT=10
```

Environment yang didukung:

| Nilai | Base URL | Keterangan |
|---|---|---|
| `development` | `https://tripay.co.id/api-sandbox` | Sandbox |
| `sandbox` | `https://tripay.co.id/api-sandbox` | Sandbox |
| `production` | `https://tripay.co.id/api` | Transaksi nyata |

Untuk production, ganti credential dan environment:

```dotenv
TRIPAY_ENV=production
```

> Jangan menyimpan credential asli di source code atau memasukkannya ke log.

Jika konfigurasi pernah di-cache, muat ulang dengan:

```bash
php artisan config:clear
```

Konfigurasi service berada di `config/services.php` pada key `services.tripay`.

### 2. Inject service

Gunakan constructor atau method injection. Laravel akan membuat instance secara otomatis.

```php
<?php

namespace App\Http\Controllers;

use App\Services\TriPayService;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    public function channels(TriPayService $triPay): JsonResponse
    {
        return response()->json($triPay->paymentChannels());
    }
}
```

Tidak perlu menambahkan binding ke service provider karena `TriPayService` adalah concrete class tanpa interface.

## Quick Start

### Ambil channel pembayaran

```php
$channels = $triPay->paymentChannels();
```

### Buat closed payment

```php
$merchantReference = 'INV-'.now()->format('YmdHis');

$transaction = $triPay->createTransaction([
    'method' => 'BRIVA',
    'merchant_ref' => $merchantReference,
    'amount' => 100000,
    'customer_name' => 'Budi Santoso',
    'customer_email' => 'budi@example.com',
    'customer_phone' => '081234567890',
    'order_items' => [
        [
            'sku' => 'PRODUCT-001',
            'name' => 'Produk Pertama',
            'price' => 100000,
            'quantity' => 1,
        ],
    ],
    'callback_url' => url('/tripay/callback'),
    'return_url' => route('home'),
    'expired_time' => now()->addHour()->timestamp,
]);

$tripayReference = $transaction['data']['reference'];
```

`signature` tidak perlu dikirim. Service membuat signature closed payment secara otomatis dari:

```text
merchant_code + merchant_ref + amount
```

Pastikan total `price × quantity` seluruh `order_items` sama dengan `amount`.

## Bentuk Respons

Semua method endpoint mengembalikan associative array hasil JSON TriPay:

```php
[
    'success' => true,
    'message' => 'Success',
    'data' => [
        // Data dari TriPay.
    ],
]
```

Gunakan pengecekan defensif sebelum mengakses data:

```php
$result = $triPay->paymentChannels();

if (! ($result['success'] ?? false)) {
    abort(502, $result['message'] ?? 'TriPay request failed.');
}

$channels = $result['data'] ?? [];
```

Respons HTTP `4xx` atau `5xx` akan melempar `RequestException`, bukan dikembalikan sebagai array.

## Payment dan Merchant

### Instruksi pembayaran

Hanya `code` yang wajib:

```php
$instructions = $triPay->paymentInstruction('BRIVA');
```

Dengan seluruh parameter:

```php
$instructions = $triPay->paymentInstruction(
    code: 'BRIVA',
    payCode: '1234567890',
    amount: 100000,
    allowHtml: true,
);
```

| Parameter | Tipe | Wajib | Keterangan |
|---|---|---:|---|
| `code` | `string` | Ya | Kode channel, misalnya `BRIVA` |
| `payCode` | `?string` | Tidak | Kode bayar/nomor VA untuk disisipkan ke instruksi |
| `amount` | `?int` | Tidak | Nominal untuk disisipkan ke instruksi |
| `allowHtml` | `bool` | Tidak | Mengizinkan HTML, default `true` |

### Daftar channel aktif

```php
$channels = $triPay->paymentChannels();
```

Gunakan data dari endpoint ini sebagai sumber channel yang tersedia untuk merchant. Jangan hard-code channel yang belum tentu aktif.

### Kalkulator biaya

Semua channel:

```php
$fees = $triPay->calculateFee(amount: 100000);
```

Channel tertentu:

```php
$fees = $triPay->calculateFee(
    amount: 100000,
    code: 'BRIVA',
);
```

### Daftar transaksi merchant

Tanpa filter:

```php
$transactions = $triPay->merchantTransactions();
```

Dengan filter:

```php
$transactions = $triPay->merchantTransactions([
    'page' => 1,
    'per_page' => 20,
    'sort' => 'desc',
    'reference' => 'T0001000000000000006',
    'merchant_ref' => 'INV-001',
    'method' => 'BRIVA',
    'status' => 'PAID',
]);
```

`per_page` maksimum `50`. Nilai `sort` adalah `asc` atau `desc`.

## Closed Payment

Closed payment memiliki nominal tetap dan kode bayar hanya digunakan untuk satu transaksi.

### Buat transaksi

```php
$result = $triPay->createTransaction([
    'method' => 'BRIVA',
    'merchant_ref' => 'INV-001',
    'amount' => 150000,
    'customer_name' => 'Budi Santoso',
    'customer_email' => 'budi@example.com',
    'customer_phone' => '081234567890',
    'order_items' => [
        [
            'sku' => 'ITEM-001',
            'name' => 'Produk A',
            'price' => 50000,
            'quantity' => 1,
            'product_url' => 'https://example.com/products/item-001',
            'image_url' => 'https://example.com/images/item-001.jpg',
        ],
        [
            'sku' => 'ITEM-002',
            'name' => 'Produk B',
            'price' => 50000,
            'quantity' => 2,
        ],
    ],
    'callback_url' => url('/tripay/callback'),
    'return_url' => route('home'),
    'expired_time' => now()->addHour()->timestamp,
]);
```

Payload utama:

| Field | Tipe | Wajib | Keterangan |
|---|---|---:|---|
| `method` | `string` | Ya | Kode channel pembayaran |
| `merchant_ref` | `string` | Ya | ID transaksi unik dari aplikasi |
| `amount` | `int` | Ya | Total transaksi, integer positif |
| `customer_name` | `string` | Ya | Nama pelanggan |
| `customer_email` | `string` | Ya | Email pelanggan |
| `customer_phone` | `string` | Tergantung channel | Nomor pelanggan |
| `order_items` | `array` | Ya | Item transaksi |
| `callback_url` | `string` | Tidak | URL callback khusus transaksi |
| `return_url` | `string` | Tidak | URL tujuan setelah pembayaran |
| `expired_time` | `int` | Tidak | Unix timestamp kedaluwarsa |
| `signature` | `string` | Tidak | Dibuat otomatis jika tidak dikirim |

Setiap item wajib memiliki `name`, `price`, dan `quantity`. `sku`, `product_url`, serta `image_url` bersifat opsional.

Simpan minimal data berikut ke database aplikasi:

```php
$tripayReference = $result['data']['reference'];
$merchantReference = $result['data']['merchant_ref'];
$status = $result['data']['status'];
```

`reference` adalah ID milik TriPay. `merchant_ref` adalah ID transaksi milik aplikasi.

### Detail transaksi

```php
$transaction = $triPay->transactionDetail(
    reference: 'T0001000000000000006',
);
```

Parameter harus menggunakan `reference` TriPay, bukan `merchant_ref`.

### Cek status transaksi

```php
$status = $triPay->checkTransactionStatus(
    reference: 'T0001000000000000006',
);
```

Gunakan `transactionDetail()` jika membutuhkan seluruh data transaksi. Gunakan callback sebagai sumber utama perubahan status dan status check sebagai rekonsiliasi.

### Membuat signature secara manual

Biasanya tidak diperlukan karena `createTransaction()` menambahkannya otomatis.

```php
$signature = $triPay->closedPaymentSignature(
    merchantReference: 'INV-001',
    amount: 150000,
);
```

## Open Payment

> Seluruh endpoint open payment hanya tersedia ketika `TRIPAY_ENV=production`.

Open payment menghasilkan kode bayar yang dapat digunakan berkali-kali dan nominalnya ditentukan pelanggan.

### Buat open payment

```php
$openPayment = $triPay->createOpenPayment([
    'method' => 'QRIS',
    'merchant_ref' => 'OPEN-001',
    'customer_name' => 'Budi Santoso',
]);

$uuid = $openPayment['data']['uuid'];
```

`method` dan `merchant_ref` wajib pada service ini. `signature` dibuat otomatis dari:

```text
merchant_code + method + merchant_ref
```

### Detail open payment

```php
$detail = $triPay->openPaymentDetail(
    uuid: 'uuid-dari-tripay',
);
```

### Daftar pembayaran masuk

```php
$transactions = $triPay->openPaymentTransactions(
    uuid: 'uuid-dari-tripay',
    filters: [
        'reference' => 'T0001000000000000006',
        'merchant_ref' => 'INV-001',
        'start_date' => '2026-07-01 00:00:00',
        'end_date' => '2026-07-31 23:59:59',
        'per_page' => 25,
    ],
);
```

`per_page` maksimum `100`.

### Membuat signature secara manual

```php
$signature = $triPay->openPaymentSignature(
    method: 'QRIS',
    merchantReference: 'OPEN-001',
);
```

## E-wallet

> Seluruh endpoint e-wallet hanya tersedia ketika `TRIPAY_ENV=production`.

Dokumentasi TriPay saat service ini dibuat mencantumkan `DANA` sebagai nilai `wallet_type`.

### Hubungkan akun

```php
$result = $triPay->linkEwallet(
    walletType: 'DANA',
    mobilePhone: '081234567890',
);

$authorizationUrl = $result['data']['authorization_url'];
```

Arahkan pengguna ke `authorization_url` untuk menyelesaikan otorisasi.

### Putuskan akun

```php
$result = $triPay->unlinkEwallet(
    walletType: 'DANA',
    mobilePhone: '081234567890',
);
```

### Detail akun

```php
$wallet = $triPay->ewalletDetail(
    walletType: 'DANA',
    mobilePhone: '081234567890',
);
```

### Membuat signature secara manual

```php
$signature = $triPay->ewalletSignature(
    walletType: 'DANA',
    mobilePhone: '081234567890',
);
```

## Callback

Callback memberi tahu aplikasi ketika status transaksi berubah. Jangan menerima status callback sebelum signature berhasil diverifikasi.

### 1. Buat controller

```bash
php artisan make:controller TriPayCallbackController --invokable --no-interaction
```

Implementasi controller:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\TriPayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use UnexpectedValueException;

class TriPayCallbackController extends Controller
{
    public function __invoke(Request $request, TriPayService $triPay): JsonResponse
    {
        try {
            $callback = $triPay->validateCallback(
                rawBody: $request->getContent(),
                signature: $request->header('X-Callback-Signature'),
                event: $request->header('X-Callback-Event'),
            );
        } catch (InvalidArgumentException|UnexpectedValueException) {
            return response()->json(['success' => false], 400);
        }

        $payment = Payment::query()
            ->where('tripay_reference', $callback['reference'])
            ->where('merchant_reference', $callback['merchant_ref'])
            ->firstOrFail();

        if ($payment->status !== $callback['status']) {
            $payment->update([
                'status' => $callback['status'],
                'paid_at' => $callback['paid_at'] ?? null,
            ]);
        }

        return response()->json(['success' => true]);
    }
}
```

`Payment` dan nama kolom pada contoh harus disesuaikan dengan model transaksi aplikasi.

### 2. Daftarkan route

Di `routes/web.php`:

```php
use App\Http\Controllers\TriPayCallbackController;
use Illuminate\Support\Facades\Route;

Route::post('/tripay/callback', TriPayCallbackController::class)
    ->name('tripay.callback');
```

Karena callback berasal dari server TriPay, kecualikan path tersebut dari CSRF di `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->validateCsrfTokens(except: [
        'tripay/callback',
    ]);

    $middleware->web(append: [
        HandleInertiaRequests::class,
        AddLinkHeadersForPreloadedAssets::class,
    ]);
})
```

Pengecualian CSRF tidak membuat callback bebas validasi. `validateCallback()` tetap wajib dipanggil.

### 3. Aturan callback

- Kirim `$request->getContent()` sebagai raw body. Jangan decode lalu encode ulang sebelum validasi.
- Header `X-Callback-Event` harus bernilai `payment_status`.
- Header `X-Callback-Signature` diverifikasi dengan HMAC-SHA256 dan `hash_equals`.
- Proses callback secara idempotent karena callback dapat dikirim ulang.
- Cocokkan `reference` dan `merchant_ref` dengan transaksi lokal.
- Tangani status `PAID`, `FAILED`, `EXPIRED`, dan `REFUND` sesuai kebutuhan bisnis.
- Balas `{"success": true}` hanya setelah callback berhasil diproses.

Contoh payload callback terverifikasi:

```php
[
    'reference' => 'T0001000000000000006',
    'merchant_ref' => 'INV-001',
    'payment_method' => 'BRI Virtual Account',
    'payment_method_code' => 'BRIVA',
    'total_amount' => 100000,
    'fee_merchant' => 4250,
    'fee_customer' => 0,
    'total_fee' => 4250,
    'amount_received' => 95750,
    'is_closed_payment' => 1,
    'status' => 'PAID',
    'paid_at' => 1784246400,
    'note' => null,
]
```

## Error Handling

Service dapat melempar exception berikut:

| Exception | Penyebab |
|---|---|
| `ConnectionException` | Timeout, DNS, atau koneksi ke TriPay gagal |
| `RequestException` | TriPay mengembalikan HTTP `4xx` atau `5xx` |
| `InvalidArgumentException` | Config/payload tidak valid atau endpoint production dipanggil dari sandbox |
| `UnexpectedValueException` | Respons atau callback bukan JSON object yang valid |

Contoh penanganan di controller:

```php
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use InvalidArgumentException;
use UnexpectedValueException;

try {
    $result = $triPay->paymentChannels();
} catch (ConnectionException $exception) {
    report($exception);

    return response()->json([
        'message' => 'Tidak dapat terhubung ke TriPay.',
    ], 503);
} catch (RequestException $exception) {
    report($exception);

    return response()->json([
        'message' => 'TriPay menolak permintaan.',
    ], 502);
} catch (InvalidArgumentException|UnexpectedValueException $exception) {
    report($exception);

    return response()->json([
        'message' => $exception->getMessage(),
    ], 422);
}
```

Jangan menampilkan detail exception koneksi atau credential kepada pengguna akhir.

## Logging

Setiap respons TriPay otomatis dicatat ke Laravel log:

```text
storage/logs/laravel.log
```

Level yang digunakan:

| Kondisi | Level |
|---|---|
| HTTP berhasil dan `success` bukan `false` | `info` |
| HTTP gagal atau `success=false` | `warning` |
| Koneksi gagal | `error` |

Context log berisi:

```text
method, endpoint, status, success, message, response
```

Field sensitif disimpan sebagai `[REDACTED]`, termasuk credential, signature, identitas pelanggan, nomor telepon, kode bayar, URL pembayaran, dan QR data.

Pantau log secara real-time dengan:

```bash
php artisan pail
```

## API Reference

| Method | Endpoint | Environment | Return |
|---|---|---|---|
| `__construct()` | Lokal | Semua | `void` |
| `paymentInstruction(string $code, ?string $payCode = null, ?int $amount = null, bool $allowHtml = true)` | `GET /payment/instruction` | Semua | `array` |
| `paymentChannels()` | `GET /merchant/payment-channel` | Semua | `array` |
| `calculateFee(int $amount, ?string $code = null)` | `GET /merchant/fee-calculator` | Semua | `array` |
| `merchantTransactions(array $filters = [])` | `GET /merchant/transactions` | Semua | `array` |
| `linkEwallet(string $walletType, string $mobilePhone)` | `POST /ewallet/link` | Production | `array` |
| `unlinkEwallet(string $walletType, string $mobilePhone)` | `POST /ewallet/unlink` | Production | `array` |
| `ewalletDetail(string $walletType, string $mobilePhone)` | `GET /ewallet/detail` | Production | `array` |
| `createTransaction(array $payload)` | `POST /transaction/create` | Semua | `array` |
| `transactionDetail(string $reference)` | `GET /transaction/detail` | Semua | `array` |
| `checkTransactionStatus(string $reference)` | `GET /transaction/check-status` | Semua | `array` |
| `createOpenPayment(array $payload)` | `POST /open-payment/create` | Production | `array` |
| `openPaymentDetail(string $uuid)` | `GET /open-payment/{uuid}/detail` | Production | `array` |
| `openPaymentTransactions(string $uuid, array $filters = [])` | `GET /open-payment/{uuid}/transactions` | Production | `array` |
| `closedPaymentSignature(string $merchantReference, int $amount)` | Lokal | Semua | `string` |
| `openPaymentSignature(string $method, string $merchantReference)` | Lokal | Semua | `string` |
| `ewalletSignature(string $walletType, string $mobilePhone)` | Lokal | Semua | `string` |
| `validateCallback(string $rawBody, ?string $signature, ?string $event)` | Lokal | Semua | `array` |

## Troubleshooting

### `Missing TriPay configuration`

Penyebab: credential kosong atau config cache masih memakai nilai lama.

```bash
php artisan config:clear
```

Pastikan tiga credential berikut terisi:

```text
TRIPAY_MERCHANT_CODE
TRIPAY_API_KEY
TRIPAY_PRIVATE_KEY
```

`TRIPAY_ENV` memiliki default `development`, sedangkan `TRIPAY_TIMEOUT` memiliki default `10`.

### Endpoint hanya tersedia di production

E-wallet dan open payment tidak memiliki endpoint sandbox dalam dokumentasi TriPay. Service akan melempar `InvalidArgumentException` jika dipanggil ketika environment bukan `production`.

### HTTP `401` atau `403`

Periksa API key sesuai environment dan pastikan channel/merchant sudah aktif di dashboard TriPay.

### Signature tidak valid

Periksa pasangan merchant code dan private key. Closed payment juga mengharuskan nilai `merchant_ref` dan `amount` yang ditandatangani sama persis dengan payload.

### Callback signature tidak valid

Gunakan raw body dari `$request->getContent()`. Jangan menggunakan `$request->all()` atau `json_encode()` ulang karena byte body dapat berubah.

### Request timeout

Naikkan timeout secara wajar:

```dotenv
TRIPAY_TIMEOUT=20
```

Lalu jalankan `php artisan config:clear`. Jangan menambahkan retry otomatis untuk pembuatan transaksi tanpa strategi idempotensi karena dapat membuat transaksi ganda.
