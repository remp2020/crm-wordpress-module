<?php

namespace Crm\WordpressModule\Tests;

use Crm\ApplicationModule\Tests\DatabaseTestCase;
use Crm\UsersModule\Repository\AccessTokensRepository;
use Crm\UsersModule\Repository\UsersRepository;
use Crm\WordpressModule\Authenticator\WordpressAuthenticator;
use Crm\WordpressModule\Model\ApiClient;
use Crm\WordpressModule\Repository\WordpressUsersRepository;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Nette\Database\Table\IRow;
use Nette\Security\AuthenticationException;
use Nette\Security\Passwords;

class WordpressUserAuthenticatorTest extends DatabaseTestCase
{
    /** @var WordpressAuthenticator */
    private $wordpressAuthenticator;

    /** @var UsersRepository */
    private $usersRepository;

    /** @var WordpressUsersRepository */
    private $wordpressUsersRepository;

    private $successUserJson = <<<JSON
{
    "data": {
        "ID": "123",
        "user_login": "admin.admin",
        "user_nicename": "admin-admin",
        "user_email": "admin@example.com",
        "user_url": "",
        "user_registered": "2017-01-01 01:12:23",
        "user_status": "0",
        "display_name": "Example Admin",
        "first_name": "Example",
        "last_name": "Admin"
    },
    "ID": 123,
    "roles": [
        "administrator"
    ]
}
JSON;

    private $invalidCredentialsJson = <<<JSON
{
    "message": "Unable to authenticate user"
}
JSON;


    public function requiredRepositories(): array
    {
        return [
            UsersRepository::class,
            AccessTokensRepository::class,
            WordpressUsersRepository::class,
        ];
    }

    public function requiredSeeders(): array
    {
        return [];
    }

    public function setUp(): void
    {
        $this->refreshContainer();
        parent::setUp();

        $this->wordpressAuthenticator = $this->inject(WordpressAuthenticator::class);
        $this->usersRepository = $this->inject(UsersRepository::class);
        $this->wordpressUsersRepository = $this->inject(WordpressUsersRepository::class);
    }

    public function testValidCredentialsNoCrmUserNoExtIdReferencing()
    {
        $this->wordpressAuthenticator->setCredentials([
            'username' => 'admin@example.com',
            'password' => 'secret',
        ]);

        $this->injectHttpHandler(new MockHandler([
            new Response(200, [], $this->successUserJson)
        ]));

        $user = $this->wordpressAuthenticator->authenticate();
        $this->assertEquals('admin@example.com', $user->email);
        foreach ($user->related('wordpress_users') as $wordpressUser) {
            $this->assertEquals(123, $wordpressUser->wordpress_id);
        }
    }

    public function testValidCredentialsNoCrmUserWithExtIdReferencing()
    {
        $this->wordpressAuthenticator->setExtIdReferencing(true);
        $this->wordpressAuthenticator->setCredentials([
            'username' => 'admin@example.com',
            'password' => 'secret',
        ]);

        $this->injectHttpHandler(new MockHandler([
            new Response(200, [], $this->successUserJson)
        ]));

        $user = $this->wordpressAuthenticator->authenticate();
        $this->assertEquals('admin@example.com', $user->email);
        foreach ($user->related('wordpress_users') as $wordpressUser) {
            $this->assertEquals(123, $wordpressUser->wordpress_id);
        }
        $this->assertEquals(123, $user->ext_id);
    }

    public function testValidCredentialsExistingCrmUserAllowPasswordChange()
    {
        // intentionally setting different password than one in WP
        $this->loadUser('admin@example.com', 'top_secret', 123);
        $this->wordpressAuthenticator->setPasswordReset();
        $this->wordpressAuthenticator->setCredentials([
            'username' => 'admin@example.com',
            'password' => 'secret',
        ]);

        $this->injectHttpHandler(new MockHandler([
            new Response(200, [], $this->successUserJson)
        ]));

        $user = $this->wordpressAuthenticator->authenticate();
        $this->assertEquals('admin@example.com', $user->email);

        // user should now have external ID of matched wordpress user
        foreach ($user->related('wordpress_users') as $wordpressUser) {
            $this->assertEquals(123, $wordpressUser->wordpress_id);
        }

        // user should now have password from Wordpress
        $this->assertTrue(Passwords::verify('secret', $user->password));
    }

    public function testValidCredentialsExistingCrmUserNoPasswordChange()
    {
        // intentionally setting different password than one in WP
        $this->loadUser('admin@example.com', 'top_secret', 123);

        $this->wordpressAuthenticator->setCredentials([
            'username' => 'admin@example.com',
            'password' => 'secret',
        ]);

        $this->injectHttpHandler(new MockHandler([
            new Response(200, [], $this->successUserJson)
        ]));

        $user = $this->wordpressAuthenticator->authenticate();
        $this->assertEquals('admin@example.com', $user->email);

        // user should now have external ID of matched wordpress user
        foreach ($user->related('wordpress_users') as $wordpressUser) {
            $this->assertEquals(123, $wordpressUser->wordpress_id);
        }

        // user should still have his old password (change of password for existing users is not allowed by default)
        $this->assertTrue(Passwords::verify('top_secret', $user->password));
    }

    public function testInvalidCredentialsNoCrmUser()
    {
        $this->expectException(AuthenticationException::class);

        $this->wordpressAuthenticator->setCredentials([
            'username' => 'admin@example.com',
            'password' => 'secret',
        ]);

        $this->injectHttpHandler(new MockHandler([
            new Response(401, [], $this->invalidCredentialsJson)
        ]));

        $this->wordpressAuthenticator->authenticate();
    }

    public function testInvalidCredentialsExistingCrmUser()
    {
        // intentionally setting different password than one in WP
        $this->loadUser('admin@example.com', 'top_secret', 123);
        $this->expectException(AuthenticationException::class);

        $this->wordpressAuthenticator->setCredentials([
            'username' => 'admin@example.com',
            'password' => 'secret',
        ]);

        $this->injectHttpHandler(new MockHandler([
            new Response(401, [], $this->invalidCredentialsJson)
        ]));

        $this->wordpressAuthenticator->authenticate();
    }

    public function testExternalIdConflict()
    {
        $this->expectException(AuthenticationException::class);

        // intentionally setting different password than one in WP
        // there should be a conflict and auth exception since email is the same, but external ID different
        $this->loadUser('admin@example.com', 'top_secret', 456);

        $this->wordpressAuthenticator->setCredentials([
            'username' => 'admin@example.com',
            'password' => 'secret',
        ]);

        $this->injectHttpHandler(new MockHandler([
            new Response(200, [], $this->successUserJson)
        ]));

        $this->wordpressAuthenticator->authenticate();
    }

    private function injectHttpHandler(callable $handler): void
    {
        $container = [];
        $history = Middleware::history($container);
        $handler = HandlerStack::create($handler);
        $handler->push($history);
        $client = new Client(['handler' => $handler]);
        $this->inject(ApiClient::class)->setClient($client);
    }

    private function loadUser($email, $password, $wordpressId, $role = UsersRepository::ROLE_USER, $active = true) : IRow
    {
        $user = $this->usersRepository->getByEmail($email);
        if (!$user) {
            $user = $this->usersRepository->add($email, $password, $role, (int)$active);
        }
        $this->wordpressUsersRepository->add($user, $wordpressId, $email, $email, new \DateTime());
        return $user;
    }
}
