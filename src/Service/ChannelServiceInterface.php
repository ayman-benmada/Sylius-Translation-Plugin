<?php

declare(strict_types=1);

namespace Abenmada\TranslationPlugin\Service;

use Sylius\Component\Core\Model\Channel;

interface ChannelServiceInterface
{
    public function findByRequestOrContext(): Channel;
}
