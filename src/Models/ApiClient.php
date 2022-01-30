<?php

namespace Crm\WordpressModule\Model;

use Crm\ApplicationModule\Config\ApplicationConfig;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\RequestOptions;
use Nette\Utils\Json;
use Tracy\Debugger;
use Tracy\ILogger;

class ApiClient
{
    const AUTH_URL = "/api/v1/remp/auth";

    private $client;

    public function __construct(ApplicationConfig $config)
    {
        $url = $config->get('cms_url');
        $token = $config->get('cms_auth_token');

        if (!empty($url) && !empty($token)) {
            $this->client = new Client([
                'base_uri' => $url,
                RequestOptions::HEADERS => [
                    'Authorization' => 'Bearer ' . $token,
                ],
            ]);
        }
    }

    public function setClient(ClientInterface $client): void
    {
        $this->client = $client;
    }

    public function credentialsAuthenticate(string $email, string $password)
    {
        return $this->request(self::AUTH_URL, [
            RequestOptions::FORM_PARAMS => [
                'login' => $email,
                'password' => $password,
            ]
        ]);
    }

    public function tokenAuthenticate(string $token)
    {
        return $this->request(self::AUTH_URL, [
            RequestOptions::FORM_PARAMS => [
                'token' => $token,
            ]
        ]);
    }

    private function request($url, $options)
    {
        try {
            $response = $this->client->post($url, $options);
        } catch (ClientException|ServerException $e) {
            Debugger::log('Unable to validate Wordpress credentials: ' . $e->getMessage());
            Debugger::log($e->getResponse()->getBody(), ILogger::DEBUG);
            return null;
        } catch (\Exception $e) {
            Debugger::log('Unable to validate Wordpress credentials: ' . $e->getMessage());
            return null;
        }

        $body = $response->getBody()->getContents();
        if (empty($body)) {
            return null;
        }

        $user = Json::decode($body);
        return $user;
    }
}
