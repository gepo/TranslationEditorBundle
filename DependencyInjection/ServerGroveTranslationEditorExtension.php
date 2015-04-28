<?php

namespace ServerGrove\Bundle\TranslationEditorBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;

class ServerGroveTranslationEditorExtension extends \Symfony\Component\HttpKernel\DependencyInjection\Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        $loader->load('services.xml');

        $configuration = new Configuration();
        $config        = $this->processConfiguration($configuration, $configs);

        $loader->load(sprintf('%s.xml', $config['storage']['type']));

        $container->setAlias($this->getAlias() . '.storage', 'server_grove_translation_editor.storage.' . $config['storage']['type']);
        $container->setAlias($this->getAlias() . '.storage.manager', $config['storage']['manager']);

        $container->setParameter($this->getAlias() . '.root_dir', $config['root_dir']);
        $container->setParameter($this->getAlias() . '.override_translator', $config['override_translator']);
    }
}
