<?php

namespace ServerGrove\Bundle\TranslationEditorBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class TranslatorPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if ($container->getParameter('server_grove_translation_editor.override_translator')) {
            $container->findDefinition('translator.default')->setClass('ServerGrove\Bundle\TranslationEditorBundle\Translation\Translator');
            $container->findDefinition('translator.default')->addMethodCall('setStorage', [$container->findDefinition('server_grove_translation_editor.storage')]);
        }
    }
}
