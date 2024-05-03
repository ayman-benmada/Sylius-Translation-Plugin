<?php

declare(strict_types=1);

namespace Abenmada\TranslationPlugin\Service;

use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\Channel;
use Symfony\Component\HttpFoundation\RequestStack;

final class ChannelService implements ChannelServiceInterface
{
    public function __construct(
        private ChannelContextInterface $channelContext,
        private RequestStack $requestStack,
        private ChannelRepositoryInterface $channelRepository,
    ) {
    }

    public function findByRequestOrContext(): Channel
    {
        $channel = null;

        $request = $this->requestStack->getCurrentRequest();

        if ($request !== null) {
            $channelCode = $request->get('channelCode');

            /** @var ?Channel $channel */
            $channel = $this->channelRepository->findOneBy(['code' => $channelCode]);
        }

        if ($channel === null) {
            /** @var Channel $channel */
            $channel = $this->channelContext->getChannel();
        }

        return $channel;
    }
}
