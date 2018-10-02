<?php

namespace Crm\WordpressModule\Api;

use Crm\ApiModule\Api\ApiHandler;
use Crm\ApiModule\Api\JsonResponse;
use Crm\ApiModule\Authorization\ApiAuthorizationInterface;
use Nette\Http\Response;

class WordpressUserInfoApiHandler extends ApiHandler
{
    public function params()
    {
        return [];
    }

    public function handle(ApiAuthorizationInterface $authorization)
    {
        $data = $authorization->getAuthorizedData();
        if (!isset($data['token'])) {
            $response = new JsonResponse(['status' => 'error', 'message' => 'Cannot authorize user']);
            $response->setHttpCode(Response::S403_FORBIDDEN);
            return $response;
        }

        $result = [];

        if (isset($data['token']->user)) {
            $user = $data['token']->user;
            $result['user'] = [
                'id' => $user->id,
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
            ];
        }

        if (isset($data['token']->author)) {
            $author = $data['token']->author;
            $result['author'] = [
                'id' => $author->id,
                'email' => $author->email,
                'first_name' => $author->first_name,
                'last_name' => $author->last_name,
            ];
        }

        if (empty($result)) {
            $response = new JsonResponse(['status' => 'error', 'message' => 'Internal error']);
            $response->setHttpCode(Response::S500_INTERNAL_SERVER_ERROR);
            return $response;
        }

        $result['status'] = 'ok';
        $response = new JsonResponse($result);
        $response->setHttpCode(Response::S200_OK);
        return $response;
    }
}
