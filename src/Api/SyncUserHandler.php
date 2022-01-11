<?php

namespace Crm\WordpressModule\Api;

use Crm\ApiModule\Api\ApiHandler;
use Crm\ApiModule\Api\JsonResponse;
use Crm\ApiModule\Api\JsonValidationTrait;
use Crm\ApiModule\Authorization\ApiAuthorizationInterface;
use Crm\UsersModule\Auth\UserManager;
use Crm\UsersModule\Repository\UsersRepository;
use Crm\WordpressModule\Repository\WordpressUsersRepository;
use Nette\Database\Table\ActiveRow;
use Nette\Http\Response;
use Nette\Utils\DateTime;

class SyncUserHandler extends ApiHandler
{
    use JsonValidationTrait;

    private $userManager;

    private $usersRepository;

    private $wordpressUsersRepository;

    private $extIdReferencing = false;

    public function __construct(
        UserManager $userManager,
        UsersRepository $usersRepository,
        WordpressUsersRepository $wordpressUsersRepository
    ) {
        $this->userManager = $userManager;
        $this->usersRepository = $usersRepository;
        $this->wordpressUsersRepository = $wordpressUsersRepository;
    }

    public function params(): array
    {
        return [];
    }

    public function handle(ApiAuthorizationInterface $authorization)
    {
        $payload = $this->validateInput(__DIR__ . '/sync-user.schema.json');
        if ($payload->hasErrorResponse()) {
            return $payload->getErrorResponse();
        }

        $json = $payload->getParsedObject();

        $wpUser = $this->wordpressUsersRepository->findByWordpressId($json->wordpress_id);
        $user = $this->userManager->loadUserByEmail($json->email);

        if ($wpUser) {
            if (!$user) {
                // changing email to user that doesn't exist yet, no conflict found
                $this->usersRepository->update($wpUser->user, [
                    'email' => $json->email,
                ]);
            } elseif ($user->id !== $wpUser->user->id) {
                // changing email to user that exists; cannot proceed as there might be conflict
                //   * user can have link to another Wordpress user (obvious conflict)
                //   * user doesn't have link to any wordpress user (seems good, but can cause account hijack)
                $response = new JsonResponse([
                    'status' => 'error',
                    'message' => 'User with following email already exists, cannot synchronize: ' . $json->email,
                    'code' => 'email_conflict',
                ]);
                $response->setHttpCode(Response::S409_CONFLICT);
                return $response;
            }

            $this->wordpressUsersRepository->update($wpUser, [
                'wordpress_id' => $json->wordpress_id,
                'email' => $json->email,
                'login' => $json->user_login,
                'registered_at' => DateTime::from($json->registered_at),
                'nicename' => $json->user_nicename,
                'url' => $json->user_url,
                'display_name' => $json->display_name,
                'first_name' => $json->first_name,
                'last_name' => $json->last_name,
            ]);

            return $this->respond($wpUser);
        }

        if ($user) {
            $response = new JsonResponse([
                'status' => 'error',
                'message' => 'User with following email already exists, cannot synchronize: ' . $json->email,
                'code' => 'email_conflict',
            ]);
            $response->setHttpCode(Response::S409_CONFLICT);
            return $response;
        }

        $user = $this->userManager->addNewUser(
            $json->email,
            false,
            'wordpress',
            null,
            false
        );

        $updateData = [
            'first_name' => $json->first_name,
            'last_name' => $json->last_name,
        ];
        if ($this->extIdReferencing) {
            $updateData['ext_id'] = $json->wordpress_id;
        }
        $this->usersRepository->update($user, $updateData);

        $wpUser = $this->wordpressUsersRepository->add(
            $user,
            $json->wordpress_id,
            $json->email,
            $json->user_login,
            DateTime::from($json->registered_at),
            $json->user_nicename,
            $json->display_name,
            $json->user_url,
            $json->first_name,
            $json->last_name
        );

        return $this->respond($wpUser);
    }

    public function setExtIdReferencing($allowed = true)
    {
        $this->extIdReferencing = $allowed;
    }

    private function respond(ActiveRow $wpUser)
    {
        $response = new JsonResponse([
            'user_id' => $wpUser->user_id,
            'wordpress_id' => $wpUser->wordpress_id,
            'email' => $wpUser->email,
            'login' => $wpUser->login,
            'registered_at' => $wpUser->registered_at->format(DATE_RFC3339),
            'nicename' => $wpUser->nicename,
            'url' => $wpUser->url,
            'display_name' => $wpUser->display_name,
            'first_name' => $wpUser->first_name,
            'last_name' => $wpUser->last_name,
        ]);
        $response->setHttpCode(Response::S200_OK);
        return $response;
    }
}
