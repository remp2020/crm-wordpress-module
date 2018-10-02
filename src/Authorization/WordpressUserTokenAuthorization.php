<?php

namespace Crm\WordpressModule\Authorization;

use Crm\ApiModule\Authorization\TokenParser;
use Crm\WordpressModule\Model\ApiClient;
use Crm\UsersModule\Auth\LoggedUserTokenAuthorization;
use Crm\UsersModule\Repository\AccessTokensRepository;
use League\Event\Emitter;
use Nette\Security\IAuthorizator;

class WordpressUserTokenAuthorization extends LoggedUserTokenAuthorization
{
    private $wordpressApiClient;

    public function __construct(
        AccessTokensRepository $accessTokensRepository,
        Emitter $emitter,
        ApiClient $wordpressApiClient
    ) {
        parent::__construct($accessTokensRepository, $emitter);

        $this->wordpressApiClient = $wordpressApiClient;
    }

    public function authorized($resource = IAuthorizator::ALL)
    {
        if (isset($_GET['source']) && $_GET['source'] === 'wordpress') {
            $tokenParser = new TokenParser();
            if (!$tokenParser->isOk()) {
                $this->errorMessage = $tokenParser->errorMessage();
                return false;
            }

            $user = $this->wordpressApiClient->userInfo($tokenParser->getToken());

            if (!$user) {
                $this->errorMessage = "Token doesn't exists";
                return false;
            }

            $this->authorizedData['token'] = $user;
            return true;
        }

        // if source is not wordpress, continue with LoggedUserTokenAuthorization
        return parent::authorized($resource);
    }
}
