<?php

namespace Crm\WordpressModule\DI;

use Nette\DI\Compiler;
use Nette\DI\CompilerExtension;

final class WordpressModuleExtension extends CompilerExtension
{
    public function loadConfiguration()
    {
        // load services from config and register them to Nette\DI Container
        Compiler::loadDefinitions(
            $this->getContainerBuilder(),
            $this->loadFromFile(__DIR__.'/../config/config.neon')['services']
        );
    }
}
