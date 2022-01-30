<?php

namespace Crm\WordpressModule\Repository;

use Crm\ApplicationModule\Repository;
use Nette\Database\Table\ActiveRow;

class WordpressUsersRepository extends Repository
{
    protected $tableName = 'wordpress_users';

    final public function add(
        ActiveRow $user,
        int $wordpressId,
        string $email,
        string $login,
        \DateTime $registeredAt,
        ?string $nicename = null,
        ?string $displayName = null,
        ?string $url = null,
        ?string $firstName = null,
        ?string $lastName = null
    ) {
        return $this->insert([
            'user_id' => $user->id,
            'wordpress_id' => $wordpressId,
            'email' => $email,
            'login' => $login,
            'registered_at' => $registeredAt,
            'nicename' => $nicename,
            'url' => $url,
            'display_name' => $displayName,
            'first_name' => $firstName,
            'last_name' => $lastName,
        ]);
    }

    final public function findByWordpressId($wordpressId)
    {
        return $this->getTable()->where(['wordpress_id' => $wordpressId])->fetch();
    }
}
