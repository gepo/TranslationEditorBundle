<?php

namespace ServerGrove\Bundle\TranslationEditorBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
// while this commit https://github.com/doctrine/DoctrineMongoDBBundle/commit/c3d037c37234bf8c50c63554529d3103950a5d36#diff-9191a5abce1610d4c2a1d9e81787a9da
// not released
//use Doctrine\Bundle\MongoDBBundle\DependencyInjection\Compiler\DoctrineMongoDBMappingsPass;

use ServerGrove\Bundle\TranslationEditorBundle\DependencyInjection\Compiler\TranslatorPass;
use ServerGrove\Bundle\TranslationEditorBundle\DependencyInjection\Compiler\DoctrineMongoDBMappingsPass;

class ServerGroveTranslationEditorBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new TranslatorPass());

        $modelDir = realpath(__DIR__.'/Resources/config/doctrine/model');
        $mappings = array(
            $modelDir => 'ServerGrove\Bundle\TranslationEditorBundle\Model',
        );

        $ormCompilerClass = 'Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass';
        if (class_exists($ormCompilerClass)) {
            $container->addCompilerPass(
                DoctrineOrmMappingsPass::createXmlMappingDriver(
                    $mappings,
                    array('server_grove_translation_editor.storage.manager_name'),
                    'server_grove_translation_editor.storage.orm.enabled',
                    array('ServerGroveBundleTranslationEditorBundle' => 'ServerGrove\Bundle\TranslationEditorBundle\Model')
            ));
        }

        $mongoCompilerClass = 'Doctrine\Bundle\MongoDBBundle\DependencyInjection\Compiler\DoctrineMongoDBMappingsPass';
        if (class_exists($mongoCompilerClass)) {
            $container->addCompilerPass(
                DoctrineMongoDBMappingsPass::createXmlMappingDriver(
                    $mappings,
                    array('server_grove_translation_editor.storage.manager_name'),
                    'server_grove_translation_editor.storage.mongodb.enabled',
                    array('ServerGroveBundleTranslationEditorBundle' => 'ServerGrove\Bundle\TranslationEditorBundle\Model')
            ));
        }
    }

}
