<?php

declare(strict_types=1);

namespace Abenmada\TranslationPlugin\Model\Channel;

use Abenmada\TranslationPlugin\Entity\Channel\ChannelTranslation;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

trait ChannelTrait
{
    /** @ORM\OneToMany(targetEntity=ChannelTranslation::class, mappedBy="channel", orphanRemoval=true, cascade={"all"}) */
    private Collection $channelTranslations;

    public function getChannelTranslations(): Collection
    {
        return $this->channelTranslations;
    }

    public function setChannelTranslations(Collection $channelTranslations): void
    {
        $this->channelTranslations = $channelTranslations;
    }

    public function addChannelTranslation(ChannelTranslation $channelTranslation): void
    {
        if ($this->channelTranslations->contains($channelTranslation)) {
            return;
        }

        $this->channelTranslations[] = $channelTranslation;
        $channelTranslation->setChannel($this);
    }

    public function removeChannelTranslation(ChannelTranslation $channelTranslation): void
    {
        $this->channelTranslations->removeElement($channelTranslation);
    }
}
