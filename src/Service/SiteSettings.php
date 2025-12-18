<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;

class SiteSettings
{
    private string $settingsFile;

    public function __construct(
        #[Autowire('%kernel.project_dir%')] string $projectDir,
        private Filesystem $filesystem
    ) {
        $this->settingsFile = $projectDir . '/var/settings.json';
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (!file_exists($this->settingsFile)) {
            return $default;
        }

        $content = json_decode(file_get_contents($this->settingsFile), true);
        return $content[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $settings = [];
        if (file_exists($this->settingsFile)) {
            $settings = json_decode(file_get_contents($this->settingsFile), true) ?? [];
        }

        $settings[$key] = $value;
        $this->filesystem->dumpFile($this->settingsFile, json_encode($settings, JSON_PRETTY_PRINT));
    }
}
