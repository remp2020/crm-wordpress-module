<?php

namespace Crm\WordpressModule;

use Crm\ApiModule\Api\ApiRoutersContainerInterface;
use Crm\ApiModule\Router\ApiIdentifier;
use Crm\ApiModule\Router\ApiRoute;
use Crm\ApplicationModule\CrmModule;
use Crm\WordpressModule\Api\WordpressUserInfoApiHandler;
use Crm\WordpressModule\Authorization\WordpressUserTokenAuthorization;

class WordpressModule extends CrmModule
{
    public function registerApiCalls(ApiRoutersContainerInterface $apiRoutersContainer)
    {
        $apiRoutersContainer->attachRouter(
            new ApiRoute(new ApiIdentifier('1', 'user', 'info'), WordpressUserInfoApiHandler::class, WordpressUserTokenAuthorization::class)
        );
    }
}
