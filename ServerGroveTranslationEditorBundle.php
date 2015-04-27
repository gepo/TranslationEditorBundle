<?php

namespace ServerGrove\Bundle\TranslationEditorBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use ServerGrove\Bundle\TranslationEditorBundle\DependencyInjection\Compiler\TranslatorPass;

class ServerGroveTranslationEditorBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new TranslatorPass());
    }

}
