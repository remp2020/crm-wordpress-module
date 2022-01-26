<?php

namespace Crm\WordpressModule\Seeders;

use Crm\ApplicationModule\Builder\ConfigBuilder;
use Crm\ApplicationModule\Config\ApplicationConfig;
use Crm\ApplicationModule\Config\Repository\ConfigCategoriesRepository;
use Crm\ApplicationModule\Config\Repository\ConfigsRepository;
use Crm\ApplicationModule\Seeders\ConfigsTrait;
use Crm\ApplicationModule\Seeders\ISeeder;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigsSeeder implements ISeeder
{
    use ConfigsTrait;

    private $configCategoriesRepository;

    private $configsRepository;

    private $configBuilder;

    public function __construct(
        ConfigCategoriesRepository $configCategoriesRepository,
        ConfigsRepository $configsRepository,
        ConfigBuilder $configBuilder
    ) {
        $this->configCategoriesRepository = $configCategoriesRepository;
        $this->configsRepository = $configsRepository;
        $this->configBuilder = $configBuilder;
    }

    public function seed(OutputInterface $output)
    {
        $category = $this->getCategory($output, 'application.integrations.config.category', 'fa fa-wrench', 600);
        $sorting = 200;

        $this->addConfig(
            $output,
            $category,
            'cms_url',
            ApplicationConfig::TYPE_STRING,
            'wordpress.config.cms_url.name',
            'wordpress.config.cms_url.description',
            null,
            $sorting++
        );

        $this->addConfig(
            $output,
            $category,
            'cms_auth_token',
            ApplicationConfig::TYPE_STRING,
            'wordpress.config.cms_auth_token.name',
            'wordpress.config.cms_auth_token.description',
            null,
            $sorting++
        );
    }
}
