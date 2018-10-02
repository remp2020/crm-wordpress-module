<?php

namespace Crm\WordpressModule\Model;

class ApiClient
{
    // dummy implementation, should connect to wordpress API / database
    public function userInfo(string $token)
    {
        switch ($token) {
            case 'testingWordpressToken':
                return (object)[
                    'user' => (object)[
                        'id' => '99999',
                        'email' => 'user@email.sk',
                        'first_name' => 'Test',
                        'last_name' => 'User',
                    ],
                    'author' => (object)[
                        'id' => 'wp.99999',
                        'email' => 'author@email.sk',
                        'first_name' => 'Test',
                        'last_name' => 'Author',
                        'roles' => ['author', 'webeditor']
                    ],
                ];
            case 'testingWordpressToken_UserOnly':
                return (object)[
                    'user' => (object)[
                        'id' => '99999',
                        'email' => 'user@email.sk',
                        'first_name' => 'Test',
                        'last_name' => 'User',
                    ]
                ];
            case 'testingWordpressToken_AuthorOnly':
                return (object)[
                    'author' => (object)[
                        'id' => 'wp.99999',
                        'email' => 'author@email.sk',
                        'first_name' => 'Test',
                        'last_name' => 'Author',
                        'roles' => ['author', 'webeditor']
                    ]
                ];
            default:
                return false;
        }
    }
}
