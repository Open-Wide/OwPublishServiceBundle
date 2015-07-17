<?php

namespace OpenWide\Publish\ServiceBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class OpenWidePublishServiceExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        $loader->load('services.yml');
        $loader->load('default_settings.yml');

        $container->setParameter('open_wide_service.root.location_id', $config['root']['location_id']);
        $container->setParameter('open_wide_service.root.max_per_block', $config['root']['max_per_block']);
        $container->setParameter('open_wide_service.paginate.max_per_page', $config['paginate']['max_per_page']);
    }
}
