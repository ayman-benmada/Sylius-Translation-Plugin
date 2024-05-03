<?php

declare(strict_types=1);

namespace Abenmada\TranslationPlugin\Controller;

use Lexik\Bundle\TranslationBundle\Controller\TranslationController as BaseTranslationController;
use Lexik\Bundle\TranslationBundle\Form\Handler\TransUnitFormHandler;
use Lexik\Bundle\TranslationBundle\Form\Type\TransUnitType;
use Lexik\Bundle\TranslationBundle\Manager\LocaleManagerInterface;
use Lexik\Bundle\TranslationBundle\Storage\StorageInterface;
use Lexik\Bundle\TranslationBundle\Translation\Translator;
use Lexik\Bundle\TranslationBundle\Util\Overview\StatsAggregator;
use Lexik\Bundle\TranslationBundle\Util\Profiler\TokenFinder;
use Abenmada\TranslationPlugin\Service\ChannelServiceInterface;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class TranslationController extends BaseTranslationController
{
    public function __construct(
        private ChannelServiceInterface $channelService,
        private ChannelRepositoryInterface $channelRepository,
        private FlashBagInterface $flashBag,
        protected StorageInterface $translationStorage,
        protected StatsAggregator $statsAggregator,
        protected TransUnitFormHandler $transUnitFormHandler,
        protected Translator $lexikTranslator,
        protected TranslatorInterface $translator,
        protected LocaleManagerInterface $localeManager,
        protected ?TokenFinder $tokenFinder
    ) {
        parent::__construct($translationStorage, $statsAggregator, $transUnitFormHandler, $lexikTranslator, $translator, $localeManager, $tokenFinder);
    }

    public function overviewAction(): Response
    {
        $stats = $this->statsAggregator->getStats();

        return $this->render('@TranslationPlugin/Translation/overview.html.twig', [
            'layout' => $this->getParameter('lexik_translation.base_layout'),
            'locales' => $this->getManagedLocales(),
            'domains' => $this->translationStorage->getTransUnitDomains(),
            'latestTrans' => $this->translationStorage->getLatestUpdatedAt(),
            'stats' => $stats,
        ]);
    }

    public function gridAction(): Response
    {
        $tokens = null;

        /** @phpstan-ignore-next-line */
        if ($this->getParameter('lexik_translation.dev_tools.enable') && $this->tokenFinder !== null) {
            $tokens = $this->tokenFinder->find();
        }

        $channel = $this->channelService->findByRequestOrContext();

        $locales = $channel->getLocales()->map(static fn (LocaleInterface $locale) => $locale->getCode())->toArray();

        return $this->render('@TranslationPlugin/Translation/grid.html.twig', [
            'layout' => $this->getParameter('lexik_translation.base_layout'),
            'inputType' => $this->getParameter('lexik_translation.grid_input_type'),
            'autoCacheClean' => $this->getParameter('lexik_translation.auto_cache_clean'),
            'toggleSimilar' => $this->getParameter('lexik_translation.grid_toggle_similar'),
            'locales' => $locales,
            'tokens' => $tokens,
            'selectedChannel' => [
                'id' => $channel->getId(),
                'code' => $channel->getCode(),
                'name' => $channel->getName(),
            ],
            'channels' => $this->channelRepository->findAll(),
        ]);
    }

    public function newAction(Request $request): Response
    {
        $form = $this->createForm(TransUnitType::class, $this->transUnitFormHandler->createFormData(), $this->transUnitFormHandler->getFormOptions());

        if ($this->transUnitFormHandler->process($form, $request)) {
            $message = $this->translator->trans('translations.successfully_added', [], 'LexikTranslationBundle');

            $this->flashBag->add('success', $message);

            $redirectUrl = $form->get('save_add')->isClicked() ? 'lexik_translation_new' : 'lexik_translation_grid'; // @phpstan-ignore-line

            return $this->redirect($this->generateUrl($redirectUrl));
        }

        return $this->render('@TranslationPlugin/Translation/new.html.twig', [
            'layout' => $this->getParameter('lexik_translation.base_layout'),
            'form' => $form->createView(),
        ]);
    }
}
