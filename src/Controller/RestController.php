<?php

declare(strict_types=1);

namespace Abenmada\TranslationPlugin\Controller;

use Abenmada\TranslationPlugin\DataGrid\DataGridFormatter;
use Abenmada\TranslationPlugin\DataGrid\DataGridRequestHandler;
use Abenmada\TranslationPlugin\Entity\Channel\ChannelTranslation;
use Abenmada\TranslationPlugin\Repository\ChannelTranslationRepository;
use Abenmada\TranslationPlugin\Service\ChannelServiceInterface;
use Doctrine\ORM\NonUniqueResultException;
use Lexik\Bundle\TranslationBundle\Controller\RestController as BaseRestController;
use Lexik\Bundle\TranslationBundle\Entity\Translation;
use Lexik\Bundle\TranslationBundle\Entity\TransUnit;
use Lexik\Bundle\TranslationBundle\Manager\TransUnitManagerInterface;
use Lexik\Bundle\TranslationBundle\Storage\StorageInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class RestController extends BaseRestController
{
    public function __construct(
        private readonly ChannelServiceInterface $channelService,
        private readonly ChannelTranslationRepository $channelTranslationRepository,
        protected DataGridRequestHandler $dataGridRequestHandler,
        protected DataGridFormatter $dataGridFormatter,
        protected StorageInterface $translationStorage,
        protected TransUnitManagerInterface $transUnitManager
    ) {
        parent::__construct($this->dataGridRequestHandler, $this->dataGridFormatter, $this->translationStorage, $this->transUnitManager);
    }

    public function listAction(Request $request): JsonResponse
    {
        $channel = $this->channelService->findByRequestOrContext();

        $locales = $channel->getLocales()->map(static fn (LocaleInterface $locale) => $locale->getCode())->toArray();

        [$transUnits, $count] = $this->dataGridRequestHandler->getPageByChannelAndLocales($request, $channel, $locales);

        return $this->dataGridFormatter->createListResponseByLocales($transUnits, $count, $locales);
    }

    /**
     * @param int|string $id
     *
     * @throws NonUniqueResultException
     */
    public function updateAction(Request $request, $id): JsonResponse
    {
        $this->checkCsrf();

        $channel = $this->channelService->findByRequestOrContext();

        $locales = $channel->getLocales()->map(static fn (LocaleInterface $locale) => $locale->getCode())->toArray();

        $response = $this->dataGridRequestHandler->updateFromRequestAndChannelAndLocales((int) $id, $request, $channel, $locales);

        return new JsonResponse($response);
    }

    /**
     * @param int $id
     */
    public function deleteAction($id): JsonResponse
    {
        $this->checkCsrf();

        /** @var ?TransUnit $transUnit */
        $transUnit = $this->translationStorage->getTransUnitById($id);

        if ($transUnit === null) {
            throw $this->createNotFoundException(sprintf('No TransUnit found for id "%s".', $id));
        }

        /** @var Translation $translation */
        foreach ($transUnit->getTranslations() as $translation) {
            $channelTranslations = $this->channelTranslationRepository->findAllByTranslation($translation);

            foreach ($channelTranslations as $channelTranslation) {
                $this->channelTranslationRepository->remove($channelTranslation);
            }
        }

        $deleted = $this->transUnitManager->delete($transUnit); // @phpstan-ignore-line

        return new JsonResponse(['deleted' => $deleted], $deleted ? 200 : 400);
    }

    /**
     * @param int    $id
     * @param string $locale
     */
    public function deleteTranslationAction($id, $locale): JsonResponse
    {
        $this->checkCsrf();

        /** @var ?TransUnit $transUnit */
        $transUnit = $this->translationStorage->getTransUnitById($id);

        if ($transUnit === null) {
            throw $this->createNotFoundException(sprintf('No TransUnit found for id "%s".', $id));
        }

        /** @var ?Translation $translation */
        $translation = $transUnit->getTranslation($locale);

        if ($translation !== null) {
            $channelTranslations = $this->channelTranslationRepository->findAllByTranslation($translation);
            foreach ($channelTranslations as $channelTranslation) {
                $this->channelTranslationRepository->remove($channelTranslation);
            }
        }

        $deleted = $this->transUnitManager->deleteTranslation($transUnit, $locale); // @phpstan-ignore-line

        return new JsonResponse(['deleted' => true], $deleted ? 200 : 400);
    }

    public function deleteChannelTranslationAction(int $transUnitId, string $locale, int $channelId): JsonResponse
    {
        $this->checkCsrf();

        /** @var ?TransUnit $transUnit */
        $transUnit = $this->translationStorage->getTransUnitById($transUnitId);

        if ($transUnit === null) {
            throw $this->createNotFoundException(sprintf('No TransUnit found for id "%s".', $transUnitId));
        }

        /** @var ?Translation $translation */
        $translation = $transUnit->getTranslation($locale);

        if ($translation === null) {
            throw $this->createNotFoundException(sprintf('No Translation found for locale "%s".', $locale));
        }

        /** @var ?ChannelTranslation $channelTranslation */
        $channelTranslation = $this->channelTranslationRepository->findOneBy(['channel' => $channelId, 'translation' => $translation->getId()]);

        if ($channelTranslation === null) {
            throw $this->createNotFoundException(sprintf('No Channel translation found for $channel "%s" and $locale "%s".', $channelId, $locale));
        }

        $this->channelTranslationRepository->remove($channelTranslation);

        return new JsonResponse(['deleted' => true], 200);
    }
}
