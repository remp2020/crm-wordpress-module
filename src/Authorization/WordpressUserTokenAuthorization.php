<?php

namespace Crm\WordpressModule\Authorization;

use Crm\ApiModule\Authorization\ApiAuthorizationInterface;
use Crm\ApiModule\Authorization\TokenParser;
use Crm\WordpressModule\Model\ApiClient;
use Nette\Security\Authorizator;
use Nette\Utils\ArrayHash;

class WordpressUserTokenAuthorization implements ApiAuthorizationInterface
{
    protected $wordpressApiClient;

    protected $errorMessage = false;

    protected $authorizedData = [];

    public function __construct(
        ApiClient $wordpressApiClient
    ) {
        $this->wordpressApiClient = $wordpressApiClient;
    }

    public function authorized($resource = Authorizator::ALL): bool
    {
        $tokenParser = new TokenParser();
        if (!$tokenParser->isOk()) {
            $this->errorMessage = $tokenParser->errorMessage();
            return false;
        }

        $token = $this->wordpressApiClient->tokenAuthenticate($tokenParser->getToken());

        if (!$token || !isset($token->data)) {
            $this->errorMessage = "Token doesn't exists";
            return false;
        }

        // this should be updated after Wordpress API and external users pivot table are ready
        $this->authorizedData['token'] = ArrayHash::from([
            'user' => [
                'id' => $token->ID,
                'email' => $token->data->user_email,
                'first_name' => $token->data->first_name,
                'last_name' => $token->data->last_name,
            ],
            'authSource' => 'wordpress',
            'wordpress' => [],
        ]);

        $this->authorizedData['token']->wordpress->roles = $token->roles;

        return true;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function getAuthorizedData()
    {
        return $this->authorizedData;
    }
}
