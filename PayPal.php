<?php


namespace backndev\paypal;


use App\Entity\Items;
use App\Entity\User;
use backndev\paypal\Order\Order;
use backndev\paypal\Subscription\Subscription;
use backndev\paypal\Token\Token;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpKernel\Bundle\Bundle;



class PayPal extends Bundle
{
    protected $_uri;
    protected $_apiKey;
    protected $_client;
    protected $_secret;
    protected $_contentType;
    protected $_payload;

    /**
     * PayPal constructor.
     * @param $client
     * @param $secret
     * @param $uri
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function __construct($client, $secret, $uri)
    {
        $this->_uri = $uri;
        $this->_client = $client;
        $this->_secret = $secret;
        $this->_contentType = 'application/json';
        $this->_apiKey = self::getToken();
    }

    public function setPayload(array $payload) : self {
        $this->_payload = $payload;
        return $this;
    }

    public function getPayLoad(): array {
        return $this->_payload;
    }

    /**
     * @param float $amount
     * @return string|null
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function createOrder(float $amount){
        $order = new Order();
        self::setPayload($order->setOrderPayload($amount));
        $client = HttpClient::create();
        $response = $client->request('POST', $this->_uri . '/v2/checkout/orders',
            [
                'headers' => [
                    'Authorization' => $this->_apiKey,
                ],
                'json' => $this->getPayLoad()
            ]);
        $data = json_decode($response->getContent());
        $capture = self::getOrder($data->id);
       // $pay = self::setCapture($capture->id);
        return $capture;
    }

    /**
     * @param $id
     * @return string
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function getOrder($id){
        $client = HttpClient::create();
        $response = $client->request('GET', $this->_uri . '/v2/checkout/orders/' . $id,
            [
                'headers' => [
                    'Authorization' => $this->_apiKey
                ]
            ]);
        return $response->getContent();
    }

    /**
     * @param string $id
     * @return string
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function setCapture(string $id){
        $client = HttpClient::create();
        $response = $client->request('POST', $this->_uri . '/v2/checkout/orders/' . $id . '/capture',
                [
                    'headers' => [
                        'Content-Type' => $this->_contentType,
                        'Authorization' => $this->_apiKey,
                        'PayPal-Request-Id' => $id
                    ]
                ]
            );
        return $response->getContent();
    }

    public function setSubscription(Items $item){
        $sub = new Subscription();
        $payload = $sub->setPlanPayload($item);
        $headers = $sub->setPlanHeaders($this->_apiKey);
        $client = HttpClient::create();
        $response = $client->request('POST', $this->_uri . '/v1/billing/plans', [
            'headers' => $headers,
            'body' => json_encode($payload)
        ]);
        return $response->getContent();
    }

    /**
     * @param Items $item
     * @param User $user
     * @param string $plan
     * @return string
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function approuveSubscription(Items $item, User $user, string $plan){
        $sub = new Subscription();
        $subscribePayload = $sub->setSubscriptionPayLoad($plan, $item, $user);
        $headers = $sub->setPlanHeaders($this->_apiKey);
        $client = HttpClient::create();
        $response = $client->request('POST', $this->_uri . '/v1/billing/subscriptions', [
            'headers' => $headers,
            'json' => $subscribePayload
        ]);
        return $response->getContent();
    }

    /**
     * @param $id
     * @return bool
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function activateSubscription($id){
        $client = HttpClient::create();
        $sub = new Subscription();
        $headers = $sub->setPlanHeaders($this->_apiKey);
        $client->request('POST', $this->_uri . '/v1/billing/subscriptions/'. $id . '/activate', [
            'headers' => $headers,
            'json' => ['reason' => 'First subscription']
        ]);
        return true;
    }

    /**
     * @param $id
     * @return string
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function getSubscription($id){
        $client = HttpClient::create();
        $sub = new Subscription();
        $headers = $sub->setPlanHeaders($this->_apiKey);
        $lastYear = new \DateTime('-10 year');
        $now =  new \DateTime('now');
        $query = '?start_time=' . $lastYear->format('Y-m-d') . 'T' . $lastYear->format('H:i:s') . 'Z&end_time=' .  $now->format('Y-m-d'). 'T' . $now->format('H:i:s') . 'Z';
        $details = $client->request('GET', $this->_uri . '/v1/billing/subscriptions/' . $id . '/transactions' . $query, [
            'headers' => $headers
        ]);
        return $details->getContent();
    }

    /**
     * @param $id
     * @return bool
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function cancelSubscription($id){
        $client = HttpClient::create();
        $sub = new Subscription();
        $headers = $sub->setPlanHeaders($this->_apiKey);
        $client->request('POST', $this->_uri . '/v1/billing/subscriptions/'. $id . '/cancel', [
            'headers' => $headers,
            'json' => ['reason' => 'admin cancel subscription']
        ]);
        return true;
    }

    /**
     * @param $id
     * @param $value
     * @return bool
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function refund($id, $value){
        $headers = [
            'content-type' => 'application/json',
            'Authorisation' => self::getToken()
        ];
        $client = HttpClient::create();
        $response = $client->request('POST', $this->_uri . '/v2/payments/captures/' . $id  . '/refund', [
            'headers' => $headers,
            'json' => [
                'amount' => [
                    'value' => $value,
                    'currency_code' => 'CHF'
                ]
            ]
        ]);
        return $response->getContent();
    }

    /**
     * @return string
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function getToken(){
        $url = $this->_uri . '/v1/oauth2/token';
       // dump(Token::getNewToken($this->_client, $this->_secret, $url)); die();
        return Token::getNewToken($this->_client, $this->_secret, $url);
    }
}