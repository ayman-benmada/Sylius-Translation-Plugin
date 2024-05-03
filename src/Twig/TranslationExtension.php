<?php

declare(strict_types=1);

namespace Abenmada\TranslationPlugin\Twig;

use Stringable;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Symfony\Bridge\Twig\Extension\TranslationExtension as BaseTranslationExtension;
use Symfony\Contracts\Translation\TranslatableInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class TranslationExtension extends AbstractExtension
{
    public function __construct(
        private ChannelContextInterface $channelContext,
        private BaseTranslationExtension $translationExtension
    ) {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('trans', [$this, '__invoke']),
        ];
    }

    public function __invoke(string|Stringable|TranslatableInterface|null $message, array|string $arguments = [], ?string $domain = null, ?string $locale = null, ?int $count = null): string
    {
        $channel = $this->channelContext->getChannel();

        if ($domain === null) {
            $domain = 'messages';
        }

        $content = $this->translationExtension->trans($message, $arguments, $domain . '-' . $channel->getCode(), $locale, $count);

        // Get global translation when channel translation does not exist
        if ($message === $content) {
            return $this->translationExtension->trans($message, $arguments, $domain, $locale, $count);
        }

        return $content;
    }
}
