<?php

declare(strict_types=1);

namespace Abenmada\TranslationPlugin\DataGrid;

use Abenmada\TranslationPlugin\Entity\Channel\ChannelTranslation;
use Abenmada\TranslationPlugin\Repository\ChannelTranslationRepository;
use function array_merge;
use function count;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ObjectManager;
use Lexik\Bundle\TranslationBundle\Document\TransUnit as TransUnitDocument;
use Lexik\Bundle\TranslationBundle\Entity\Translation;
use Lexik\Bundle\TranslationBundle\Entity\TransUnit;
use Lexik\Bundle\TranslationBundle\Manager\FileManagerInterface;
use Lexik\Bundle\TranslationBundle\Manager\LocaleManagerInterface;
use Lexik\Bundle\TranslationBundle\Manager\TransUnitManagerInterface;
use Lexik\Bundle\TranslationBundle\Storage\StorageInterface;
use Lexik\Bundle\TranslationBundle\Util\DataGrid\DataGridRequestHandler as BaseDataGridRequestHandler;
use Safe\Exceptions\StringsException;
use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DataGridRequestHandler extends BaseDataGridRequestHandler
{
    public function __construct(
        private readonly ChannelTranslationRepository $channelTranslationRepository,
        private readonly FactoryInterface $channelTranslationFactory,
        private readonly ObjectManager $channelTranslationManager,
        TransUnitManagerInterface $transUnitManager,
        FileManagerInterface $fileManager,
        StorageInterface $storage,
        LocaleManagerInterface $localeManager
    ) {
        parent::__construct($transUnitManager, $fileManager, $storage, $localeManager);
    }

    public function getPageByChannelAndLocales(Request $request, Channel $channel, array $locales): array
    {
        $parameters = $this->fixParameters($request->query->all());

        $transUnits = $this->storage->getTransUnitList(
            $locales,
            (int) $request->query->get('rows', 20),
            (int) $request->query->get('page', 1),
            $parameters
        );

        for ($i = 0; $i < count($transUnits); ++$i) {
            $channelTranslations = $this->channelTranslationRepository->findTransUnitsByChannelAndLocalesAndKey($channel, $locales, $transUnits[$i]['key']);

            for ($j = 0; $j < count($transUnits[$i]['translations']); ++$j) {
                $translation = $transUnits[$i]['translations'][$j];
                $translation['channel_translation'] = '';

                foreach ($channelTranslations as $channelTranslation) {
                    if ($transUnits[$i]['translations'][$j]['locale'] === $channelTranslation['locale']) {
                        $translation['channel_translation'] = $channelTranslation['content'];
                        break;
                    }
                }

                $transUnits[$i]['translations'][$j] = $translation;
            }
        }

        $count = $this->storage->countTransUnits($locales, $parameters);

        return [$transUnits, $count];
    }

    /**
     * @throws NonUniqueResultException|StringsException
     */
    public function updateFromRequestAndChannelAndLocales(int $id, Request $request, Channel $channel, array $locales): array
    {
        /** @var TransUnitDocument|TransUnit|null $transUnit */
        $transUnit = $this->storage->getTransUnitById($id);

        if ($transUnit === null) {
            throw new NotFoundHttpException(sprintf('No TransUnit found for "%s"', $id));
        }

        $translationsContent = [];
        $translationsChannelContent = [];
        $translations = [];

        foreach ($locales as $locale) {
            $globalTranslationContent = $request->request->get($locale);
            $channelTranslationContent = $request->request->get('_' . $locale);

            $translationsContent[$locale] = $globalTranslationContent;
            $translationsChannelContent[$locale] = $channelTranslationContent;

            $translations[$locale] = $globalTranslationContent;
            $translations['_' . $locale] = $channelTranslationContent;

            // If the global translation does not exist, create it before creating a channel translation
            if ($globalTranslationContent !== '' && $globalTranslationContent !== null) {
                continue;
            }

            $translationsContent[$locale] = $channelTranslationContent;
            $translations[$locale] = $channelTranslationContent;
        }

        $this->transUnitManager->updateTranslationsContent($transUnit, $translationsContent);

        if ($transUnit instanceof TransUnitDocument) {
            $transUnit->convertMongoTimestamp();
        }

        $this->storage->flush();

        foreach ($locales as $locale) {
            $content = $translationsChannelContent[$locale];

            /** @var ?Translation $translation */
            $translation = $transUnit->getTranslation($locale);

            if ($translation === null) {
                continue;
            }

            $channelTranslation = $this->channelTranslationRepository->findByChannelAndTranslation($channel, $translation);

            if ($channelTranslation === null) {
                if ($content === '' || $content === null) {
                    continue;
                }

                /** @var ChannelTranslation $channelTranslation */
                $channelTranslation = $this->channelTranslationFactory->createNew();

                $channelTranslation->setTranslation($translation);
                $channelTranslation->setContent((string) $content);
                $channelTranslation->setChannel($channel);

                $this->channelTranslationManager->persist($channelTranslation);
            } elseif ($content === '' || $content === null) {
                $this->channelTranslationRepository->remove($channelTranslation);
            } else {
                $channelTranslation->setContent((string) $content);
            }
        }

        $this->channelTranslationManager->flush();

        return array_merge(
            $translations,
            [
                '_domain' => $transUnit->getDomain(),
                '_id' => $transUnit->getId(),
                '_key' => $transUnit->getKey(),
            ],
        );
    }
}
