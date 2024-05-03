<?php

declare(strict_types=1);

namespace Abenmada\TranslationPlugin\Menu\Listener;

use Knp\Menu\ItemInterface;
use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;

final class AdminMenuListener
{
    public function invoke(MenuBuilderEvent $event): void
    {
        $menu = $event->getMenu();

        $this->addTranslationChild($menu);
    }

    private function addTranslationChild(ItemInterface $menu): void
    {
        $subMenu = $menu
            ->addChild('translation')
            ->setLabel('abenmada_translation_plugin.ui.translations');

        $this->addOverviewTranslationSubItem($subMenu);
        $this->addGridTranslationSubItem($subMenu);
    }

    private function addOverviewTranslationSubItem(ItemInterface $subMenu): void
    {
        $subMenu
            ->addChild('abenmada_translation_plugin_overview', ['route' => 'abenmada_translation_plugin_overview'])
            ->setLabel('abenmada_translation_plugin.ui.overview')
            ->setLabelAttribute('icon', 'chart pie');
    }

    private function addGridTranslationSubItem(ItemInterface $subMenu): void
    {
        $subMenu
            ->addChild('abenmada_translation_plugin_index', ['route' => 'abenmada_translation_plugin_index'])
            ->setLabel('abenmada_translation_plugin.ui.translations')
            ->setLabelAttribute('icon', 'language');
    }
}
