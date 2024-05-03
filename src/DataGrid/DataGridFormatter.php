<?php

declare(strict_types=1);

namespace Abenmada\TranslationPlugin\DataGrid;

use function array_key_exists;
use function in_array;
use function is_object;
use Lexik\Bundle\TranslationBundle\Entity\TransUnit;
use Lexik\Bundle\TranslationBundle\Manager\LocaleManagerInterface;
use Lexik\Bundle\TranslationBundle\Storage\StorageInterface;
use Lexik\Bundle\TranslationBundle\Util\DataGrid\DataGridFormatter as BaseDataGridFormatter;
use Symfony\Component\HttpFoundation\JsonResponse;

class DataGridFormatter extends BaseDataGridFormatter
{
    public function __construct(LocaleManagerInterface $localeManager, string $storage)
    {
        parent::__construct($localeManager, $storage);
    }

    public function createListResponseByLocales(array $transUnits, int $total, array $locales): JsonResponse
    {
        return new JsonResponse([
            'translations' => $this->formatByLocales($transUnits, $locales),
            'total' => $total,
        ]);
    }

    protected function formatByLocales(array $transUnits, array $locales): array
    {
        $formatted = [];

        foreach ($transUnits as $transUnit) {
            $formatted[] = $this->formatOneByLocales($transUnit, $locales);
        }

        return $formatted;
    }

    protected function formatOneByLocales(array|TransUnit $transUnit, array $locales): array
    {
        if (is_object($transUnit)) {
            $transUnit = $this->toArray($transUnit);
        } elseif ($this->storage === StorageInterface::STORAGE_MONGODB) {
            $transUnit['id'] = $transUnit['_id']->{'$id'}; // @phpstan-ignore-line
        }

        $formatted = [
            '_id' => $transUnit['id'],
            '_domain' => $transUnit['domain'],
            '_key' => $transUnit['key'],
        ];

        // Add locales in the same order as in managed_locales param
        foreach ($locales as $locale) {
            $formatted[$locale] = '';
        }

        // Then fill locales value
        foreach ($transUnit['translations'] as $translation) {
            if (!in_array($translation['locale'], $locales, true)) {
                continue;
            }

            $formatted[$translation['locale']] = $translation['content'];

            if (!array_key_exists('channel_translation', $translation)) {
                continue;
            }

            $formatted['_' . $translation['locale']] = $translation['channel_translation'];
        }

        return $formatted;
    }
}
