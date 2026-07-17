<?php

namespace App\Services;

use ZerosDev\TriPay\Client;
use ZerosDev\TriPay\Payment;

class TriPayServiceOld
{
    public Client $client;

    public function __construct()
    {
        $this->client = new Client(
            env('TRIPAY_MERCHANT_CODE'),
            env('TRIPAY_API_KEY'),
            env('TRIPAY_PRIVATE_KEY'),
            env('TRIPAY_ENV', 'development'),
            []
            // env('TRIPAY_GUZZLE_OPTIONS',[])
        );

    }

    private function result($res)
    {
        $json = json_decode($res->getBody()->getContents(), true);

        return $json;
    }

    public function getPaymentChannel()
    {
        $res = $this->client->get('merchant/payment-channels');
        dd($this->result($res));
    }

    public function checkPayment()
    {
        $payment = new Payment($this->client);
        $result = $payment->instruction('BRIVA');
        dd($this->result($result));
    }
}
