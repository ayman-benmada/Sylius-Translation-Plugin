<?php

declare(strict_types=1);

namespace Abenmada\TranslationPlugin;

use LogicException;
use Sylius\Bundle\CoreBundle\Application\SyliusPluginTrait;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class TranslationPlugin extends Bundle
{
    use SyliusPluginTrait;

    /**
     * Returns the plugin's container extension.
     *
     * @throws LogicException
     */
    public function getContainerExtension(): ?ExtensionInterface
    {
        if ($this->containerExtension === null) {
            $this->containerExtension = false;
            $extension = $this->createContainerExtension();
            if ($extension !== null) {
                $this->containerExtension = $extension;
            }
        }

        return $this->containerExtension instanceof ExtensionInterface ? $this->containerExtension : null;
    }
}
