<?php

namespace Crm\WordpressModule\Authorization;

use Crm\ApiModule\Authorization\ApiAuthorizationInterface;
use Crm\ApiModule\Authorization\TokenParser;
use Crm\WordpressModule\Model\ApiClient;
use Nette\Security\IAuthorizator;

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

    public function authorized($resource = IAuthorizator::ALL)
    {
        $tokenParser = new TokenParser();
        if (!$tokenParser->isOk()) {
            $this->errorMessage = $tokenParser->errorMessage();
            return false;
        }

        $token = $this->wordpressApiClient->userInfo($tokenParser->getToken());

        if (!$token || !isset($token->user)) {
            $this->errorMessage = "Token doesn't exists";
            return false;
        }

        // this should be updated after Wordpress API and external users pivot table are ready
        $this->authorizedData['token'] = new \stdClass();
        $this->authorizedData['token']->user = $token->user;
        $this->authorizedData['token']->source = 'wordpress';

        if (isset($token->author)) {
            $this->authorizedData['token']->sourceData['author'] = $token->author;
        }

        // TODO: Emit event if user exists in CRM? $token->user must be instanceof \Nette\Database\Table\ActiveRow
//        $this->emitter->emit(new UserLastAccessEvent(
//            $token->user,
//            new \DateTime(),
//            isset($_GET['source']) ? 'api+' . $_GET['source'] : null,
//            Request::getUserAgent()
//        ));

        return true;
    }

    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    public function getAuthorizedData()
    {
        return $this->authorizedData;
    }
}
