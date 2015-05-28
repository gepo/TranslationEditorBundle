<?php

namespace ServerGrove\Bundle\TranslationEditorBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class TranslatorPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if ($container->getParameter('server_grove_translation_editor.override_translator')) {
            $container->setAlias('translator', 'server_grove_translation_editor.translator');
            $container->setAlias('translator.default', 'server_grove_translation_editor.translator');
            $container->setParameter('translator.class', 'ServerGrove\Bundle\TranslationEditorBundle\Translation\Translator');
        }
    }
}
