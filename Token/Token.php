<?php


namespace backndev\paypal\Token;


use Symfony\Component\HttpClient\HttpClient;

class Token
{
    /**
     * @param $client
     * @param $secret
     * @param $url
     * @return string
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public static function getNewToken($client, $secret, $url) : string {
        $credential = $client . ':' . $secret;
        $client = HttpClient::create();
        $response = $client->request('POST', $url,[
            'headers' => [
                'Accept' => 'application/json',
                'Accept-Language' => 'en_US'
            ],
            'auth_basic' => $credential,
            'body' => [
                'grant_type' => 'client_credentials'
            ]
        ]);
        $data = json_decode($response->getContent());
        $formedToken = $data->token_type . ' ' . $data->access_token;
        return $formedToken;
    }

}