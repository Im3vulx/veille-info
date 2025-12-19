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

        // Sécurité anti-crash 500 : si le json est invalide, on renvoie le défaut
        if (!is_array($content)) {
            return $default;
        }

        return $content[$key] ?? $default;
    }

    public function set(array $data): void
    {
        $current = [];
        if (file_exists($this->settingsFile)) {
            $decoded = json_decode(file_get_contents($this->settingsFile), true);
            if (is_array($decoded)) {
                $current = $decoded;
            }
        }

        $newSettings = array_merge($current, $data);

        if ($current !== $newSettings) {
            $this->filesystem->dumpFile($this->settingsFile, json_encode($newSettings, JSON_PRETTY_PRINT));
        }
    }
}
