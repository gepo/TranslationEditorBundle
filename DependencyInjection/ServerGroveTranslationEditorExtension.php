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

        $storageType = $config['storage']['type'];

        if (!in_array($storageType, ['mongodb', 'orm'])) {
            throw new \LogicException('ServerGroveTranslationEditorBundle supports only `mongodb` and `orm` storage types');
        }

        $loader->load($storageType . '.xml');

        $container->setAlias($this->getAlias() . '.storage', 'server_grove_translation_editor.storage.' . $config['storage']['type']);
        $container->setParameter('server_grove_translation_editor.storage.' . $config['storage']['type'] . '.enabled', true);
        $container->setParameter($this->getAlias() . '.root_dir', $config['root_dir']);
        $container->setParameter($this->getAlias() . '.override_translator', $config['override_translator']);

        $container->setParameter($this->getAlias() . '.storage.manager_name', $config['storage']['manager']);
        $this->{'set'.ucfirst($storageType).'Manager'}($container, $config);
    }

    private function setMongodbManager(ContainerBuilder $container, array $config)
    {
        $container->setAlias($this->getAlias() . '.storage.manager', 'doctrine_mongodb.odm.'.$config['storage']['manager'].'_document_manager');
    }

    private function setOrmManager(ContainerBuilder $container, array $config)
    {
        $container->setAlias($this->getAlias() . '.storage.manager', 'doctrine.orm.'.$config['storage']['manager'].'_entity_manager');
    }
}
