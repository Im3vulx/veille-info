<?php

namespace App\Twig;

use App\Service\SiteSettings;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public function __construct(private SiteSettings $siteSettings) {}

    public function getFunctions(): array
    {
        return [
            // Permet d'utiliser {{ get_setting('key') }} dans Twig
            new TwigFunction('get_setting', [$this->siteSettings, 'get']),
        ];
    }
}
