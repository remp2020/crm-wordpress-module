<?php

namespace Crm\WordpressModule\DI;

use Contributte\Translation\DI\TranslationProviderInterface;
use Nette\DI\CompilerExtension;

final class WordpressModuleExtension extends CompilerExtension implements TranslationProviderInterface
{
    private $defaults = [
        'extIdReferencing' => false,
        'authenticator' => [
            'passwordReset' => false,
        ],
    ];

    public function loadConfiguration()
    {
        $this->validateConfig($this->defaults);

        // load services from config and register them to Nette\DI Container
        $this->compiler->loadDefinitionsFromConfig(
            $this->loadFromFile(__DIR__.'/../config/config.neon')['services']
        );

        $builder = $this->getContainerBuilder();

        $builder->getDefinition('syncUserHandler')
            ->addSetup('setExtIdReferencing', [$this->config['extIdReferencing']]);
        $builder->getDefinition('wordpressAuthenticator')
            ->addSetup('setExtIdReferencing', [$this->config['extIdReferencing']])
            ->addSetup('setPasswordReset', [$this->config['authenticator']['passwordReset']]);
    }

    /**
     * Return array of directories, that contain resources for translator.
     * @return string[]
     */
    public function getTranslationResources(): array
    {
        return [__DIR__ . '/../lang/'];
    }
}
