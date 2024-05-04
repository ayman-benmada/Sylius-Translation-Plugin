<?php

declare(strict_types=1);

namespace Abenmada\TranslationPlugin\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

final readonly class TemplatingController
{
    public function __construct(private Environment $templatingEngine)
    {
    }

    /**
     * @throws LoaderError|RuntimeError|SyntaxError
     */
    public function __invoke(Request $request, array $parameters = []): Response
    {
        $template = $this->getSyliusAttribute($request, 'template');

        return new Response($this->templatingEngine->render($template, $parameters));
    }

    private function getSyliusAttribute(Request $request, string $attribute, ?string $default = null): string
    {
        $attributes = $request->attributes->get('_sylius');

        return $attributes[$attribute] ?? $default; // @phpstan-ignore-line
    }
}
