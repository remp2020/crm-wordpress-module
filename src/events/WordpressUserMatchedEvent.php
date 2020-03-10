<?php

namespace Crm\WordpressModule\Events;

use League\Event\AbstractEvent;

class WordpressUserMatchedEvent extends AbstractEvent
{
    private $user;

    private $wpUser;

    public function __construct($user, $wpUser)
    {
        $this->user = $user;
        $this->wpUser = $wpUser;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function getWpUser()
    {
        return $this->wpUser;
    }
}
