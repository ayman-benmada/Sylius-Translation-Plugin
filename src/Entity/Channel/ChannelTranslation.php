<?php

declare(strict_types=1);

namespace Abenmada\TranslationPlugin\Entity\Channel;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Lexik\Bundle\TranslationBundle\Entity\Translation;
use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Resource\Model\ResourceInterface;

/**
 * @ORM\Entity
 *
 * @ORM\Table(name="abenmada_translation_channel_translation")
 */
#[ORM\Table(name: 'abenmada_translation_channel_translation')]
#[ORM\Entity]
class ChannelTranslation implements ResourceInterface
{
    use TimestampableEntity;

    /**
     * @ORM\Id
     *
     * @ORM\GeneratedValue
     *
     * @ORM\Column(type="integer")
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    /** @ORM\Column(name="content", type="string", length=255, nullable=false) */
    #[ORM\Column(name: 'content', type: 'string', length: 255, nullable: false)]
    private string $content;

    /**
     * @ORM\ManyToOne(targetEntity=Translation::class, inversedBy="channelTranslations")
     *
     * @ORM\JoinColumn(name="translation_id")
     */
    #[ORM\JoinColumn(name: 'translation_id')]
    #[ORM\ManyToOne(targetEntity: Translation::class, inversedBy: 'channelTranslations')]
    private Translation $translation;

    /**
     * @ORM\ManyToOne(targetEntity=Channel::class, inversedBy="channelTranslations")
     *
     * @ORM\JoinColumn(name="channel_id", nullable=false)
     */
    #[ORM\JoinColumn(name: 'channel_id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: Channel::class, inversedBy: 'channelTranslations')]
    private Channel $channel;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    public function getTranslation(): Translation
    {
        return $this->translation;
    }

    public function setTranslation(Translation $translation): void
    {
        $this->translation = $translation;
    }

    public function getChannel(): Channel
    {
        return $this->channel;
    }

    public function setChannel(Channel $channel): void
    {
        $this->channel = $channel;
    }
}
