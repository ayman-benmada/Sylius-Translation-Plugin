<?php

declare(strict_types=1);

namespace Abenmada\TranslationPlugin\Loader;

use Abenmada\TranslationPlugin\Repository\ChannelTranslationRepository;
use Lexik\Bundle\TranslationBundle\Storage\StorageInterface;
use Lexik\Bundle\TranslationBundle\Translation\Loader\DatabaseLoader as BaseDatabaseLoader;
use Symfony\Component\Translation\MessageCatalogue;

class DatabaseLoader extends BaseDatabaseLoader
{
    public function __construct(
        private ChannelTranslationRepository $channelTranslationRepository,
        protected StorageInterface $storage
    ) {
        parent::__construct($storage);
    }

    public function load($resource, $locale, $domain = 'messages'): MessageCatalogue
    {
        $catalogue = parent::load($resource, $locale, $domain);

        $channelTranslations = $this->channelTranslationRepository->findAllByLocaleAndDomain($locale, $domain);

        foreach ($channelTranslations as $channelTranslation) {
            $channel = $channelTranslation->getChannel();
            $transUnit = $channelTranslation->getTranslation()->getTransUnit();

            $catalogue->set($transUnit->getKey(), $channelTranslation->getContent(), $domain . '-' . $channel->getCode());
        }

        return $catalogue;
    }
}
