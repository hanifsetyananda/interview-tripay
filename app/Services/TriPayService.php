<?php

namespace App\Services\Payment;

use ZerosDev\TriPay\Client;
use ZerosDev\TriPay\Transaction;
use ZerosDev\TriPay\Support\Constant;
use ZerosDev\TriPay\Support\Helper;

class TriPayService
{
    protected Client $client;

    public function __construct()
    {
        $this->client = new Client(
            env('TRIPAY_MERCHANT_CODE'),
            env('TRIPAY_API_KEY'),
            env('TRIPAY_PRIVATE_KEY'),
            env('TRIPAY_ENV',"development"),
            env('TRIPAY_guzzle_options')
        );
        
    }


}