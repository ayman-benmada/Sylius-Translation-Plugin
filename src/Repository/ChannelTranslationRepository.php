<?php

declare(strict_types=1);

namespace Abenmada\TranslationPlugin\Repository;

use Abenmada\TranslationPlugin\Entity\Channel\ChannelTranslation;
use Doctrine\ORM\NonUniqueResultException;
use Lexik\Bundle\TranslationBundle\Entity\Translation;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Component\Channel\Model\ChannelInterface;

class ChannelTranslationRepository extends EntityRepository
{
    public function findTransUnitsByChannelAndLocalesAndKey(ChannelInterface $channel, array $locales, string $key): array
    {
        return $this
            ->createQueryBuilder('ct')
            ->select('ct.id,ct.content,translation.locale')
            ->leftJoin('ct.channel', 'channel')
            ->leftJoin('ct.translation', 'translation')
            ->leftJoin('translation.transUnit', 'transUnit')
            ->where('channel.id = :channelId')
            ->andWhere('translation.locale IN (:locales)')
            ->andWhere('transUnit.key = :key')
            ->setParameter('channelId', $channel->getId())
            ->setParameter('locales', $locales)
            ->setParameter('key', $key)
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findByChannelAndTranslation(ChannelInterface $channel, Translation $translation): ?ChannelTranslation
    {
        return $this // @phpstan-ignore-line
            ->createQueryBuilder('ct')
            ->leftJoin('ct.channel', 'channel')
            ->leftJoin('ct.translation', 'translation')
            ->where('channel.id = :channelId')
            ->andWhere('translation.id = :translationId')
            ->setParameter('channelId', $channel->getId())
            ->setParameter('translationId', $translation->getId())
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return ChannelTranslation[]
     */
    public function findAllByTranslation(Translation $translation): array
    {
        return $this // @phpstan-ignore-line
            ->createQueryBuilder('ct')
            ->leftJoin('ct.translation', 'translation')
            ->where('translation.id = :translationId')
            ->setParameter('translationId', $translation->getId())
            ->getQuery()
            ->getResult();
    }

    /**
     * @return ChannelTranslation[]
     */
    public function findAllByLocaleAndDomain(string $locale, string $domain): array
    {
        return $this // @phpstan-ignore-line
            ->createQueryBuilder('ct')
            ->leftJoin('ct.translation', 'translation')
            ->leftJoin('translation.transUnit', 'transUnit')
            ->andWhere('translation.locale = :locale')
            ->andWhere('transUnit.domain = :domain')
            ->setParameter('locale', $locale)
            ->setParameter('domain', $domain)
            ->getQuery()
            ->getResult();
    }
}
