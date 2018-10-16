<?php

namespace Crm\WordpressModule\Model;

use Crm\UsersModule\Repository\UsersRepository;

/**
 * Class ApiClient
 * @todo Dummy implementation, should connect to wordpress API / database
 */
class ApiClient
{
    private $usersRepository;

    public function __construct(
        UsersRepository $usersRepository
    ) {
        $this->usersRepository = $usersRepository;
    }

    public function userInfo(string $token)
    {
        switch ($token) {
            case 'testingWordpressToken':
                return (object)[
                    'user' => (object)[
                        'id' => 'wp.11111',
                        'email' => 'admin@admin.sk',
                        'first_name' => 'CRM and WP',
                        'last_name' => 'Author Admin',
                        'roles' => ['admin', 'author', 'editor']
                    ],
                    'author' => (object)[
                        'id' => 'wp.11111',
                        'email' => 'admin@admin.sk',
                        'first_name' => 'CRM and WP',
                        'last_name' => 'Author Admin',
                        'roles' => ['admin', 'author', 'editor']
                    ],
                ];
            default:
                return false;
        }
    }
}
