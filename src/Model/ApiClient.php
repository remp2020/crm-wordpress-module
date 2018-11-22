<?php

namespace Crm\WordpressModule\Model;

use Crm\ApplicationModule\Config\ApplicationConfig;
use GuzzleHttp\Client;
use Nette\Utils\Json;

/**
 * Class ApiClient
 */
class ApiClient
{
    const AUTH = "356a7713-673b-40f0-948a-2d33439c455e";

    const TOKEN_CHECK_URL = "api/tokena/?id=ID&token=TOKEN";

    private $client;

    public function __construct(ApplicationConfig $config)
    {
        $this->client = new Client([
            'base_uri' => $config->get('cms_url'),
        ]);
    }

    public function userInfo(string $token)
    {
        $response = $this->client->get(self::TOKEN_CHECK_URL, [
            'query' => [
                'token' => $token,
                'auth' => self::AUTH,
            ],
        ]);
        $user = Json::decode($response->getBody()->getContents());

        return $user;
    }
}
