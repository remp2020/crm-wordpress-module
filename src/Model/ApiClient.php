<?php

namespace Crm\WordpressModule\Model;

use Crm\ApplicationModule\Config\ApplicationConfig;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Nette\Http\Response;
use Nette\Utils\Json;

/**
 * Class ApiClient
 */
class ApiClient
{
    const AUTH = "356a7713-673b-40f0-948a-2d33439c455e";

    const TOKEN_CHECK_URL = "/api/tokena";

    private $client;

    public function __construct(ApplicationConfig $config)
    {
        $this->client = new Client([
            'base_uri' => $config->get('cms_url'),
        ]);
    }

    public function userInfo(string $token)
    {
        try {
            $response = $this->client->get(self::TOKEN_CHECK_URL, [
                'query' => [
                    'token' => $token,
                    'auth' => self::AUTH,
                ],
            ]);
        } catch (ClientException $e) {
            if ($e->getResponse()->getStatusCode() === Response::S404_NOT_FOUND) {
                return null;
            }
            throw $e;
        }

        $body = $response->getBody()->getContents();
        if (empty($body)) {
            return null;
        }

        $user = Json::decode($body);
        return $user;
    }
}
