<?php

use Phinx\Migration\AbstractMigration;

class WordpressUsers extends AbstractMigration
{
    public function change()
    {
        $this->table('wordpress_users')
            ->addColumn('user_id', 'integer', ['null' => false])
            ->addColumn('wordpress_id', 'integer', ['null' => false])
            ->addColumn('email', 'string', ['null' => false])
            ->addColumn('login', 'string', ['null' => false])
            ->addColumn('registered_at', 'datetime', ['null' => false])
            ->addColumn('nicename', 'string', ['null' => true])
            ->addColumn('url', 'string', ['null' => true])
            ->addColumn('display_name', 'string', ['null' => true])
            ->addColumn('first_name', 'string', ['null' => true])
            ->addColumn('last_name', 'string', ['null' => true])
            ->addForeignKey('user_id', 'users')
            ->addIndex('wordpress_id')
            ->create();
    }
}
