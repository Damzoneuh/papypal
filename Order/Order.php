<?php


namespace backndev\paypal\Order;


use Symfony\Component\HttpClient\HttpClient;

class Order
{
    protected $currency;

    public function __construct()
    {
        $this->currency = 'CHF';
    }

    public function setOrderPayload(float $amount): ?array {
        $payload = [];
        $payload['intent'] = "AUTHORIZE";
        $payload['purchase_units'] = [
            ['amount' =>
                [
                    'currency_code' => "CHF",
                    'value' => $amount
                    ]
            ]
        ];
        return $payload;
    }

}

