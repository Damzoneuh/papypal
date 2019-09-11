<?php


namespace backndev\paypal\Subscription;


use App\Entity\Items;

class Subscription
{
    public function setSubscriptionPayload(Items $item){
        $payload = [
            'product_id' => $item->getPaypalProduct(),
            'description' => $item->getType(),
            'status' => 'ACTIVE',
            'billing_cycles' => [
                'frequency' => [
                    'interval_unit' => 'MONTH',
                    'interval_count' => $item->getDuration()
                ]
            ],
            'tenure_type' => 'REGULAR',
            'sequence' => 2,
            'total_cycles' => 20,
            'pricing_schemes' => [
                'fixed_price' => [
                    'value' => $item->getPrice(),
                    'currency_code' => 'CHF'
                ]
            ],
            'payment_preference' => [
                'auto_bill_outstanding' => true,
                'setup_fee' => [
                    'value' => $item->getPrice(),
                    'currency_code' => 'CHF'
                ],
                'setup_fee_failure_action' => 'CONTINUE',
                'payment_failure_threshold' => 3
            ],
            'taxes' => [
                'percentage' => 7,
                'inclusive' => true
            ]
        ];
        return $payload;
    }

    public function setPlanHeaders($token){
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => $token
        ];
        return $headers;
    }
}