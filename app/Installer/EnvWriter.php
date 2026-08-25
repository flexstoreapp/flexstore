<?php

declare(strict_types=1);

namespace App\Installer;

use App\Installer\Contracts\EnvWriter as EnvWriterContract;
use RuntimeException;

final readonly class EnvWriter implements EnvWriterContract
{
    /**
     * @param  array<string, string>  $values
     */
    public function write(array $values): void
    {
        $envPath = base_path('.env');
        $env = file_get_contents($envPath);

        if ($env === false) {
            throw new RuntimeException("Cannot read environment file: {$envPath}");
        }

        $lines = explode("\n", $env);

        foreach ($values as $key => $value) {
            $formatted = "{$key}={$this->formatValue($value)}";
            $found = false;

            foreach ($lines as &$line) {
                if (str_starts_with($line, "{$key}=") || str_starts_with($line, "# {$key}=")) {
                    $line = $formatted;
                    $found = true;

                    break;
                }
            }

            unset($line);

            if (! $found) {
                $lines[] = $formatted;
            }
        }

        file_put_contents($envPath, implode("\n", $lines), LOCK_EX);

        $this->syncProcessEnvironment($values);
    }

    /**
     * @param  array<string, string>  $values
     */
    private function syncProcessEnvironment(array $values): void
    {
        foreach ($values as $key => $value) {
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
            putenv("{$key}={$value}");
        }
    }

    private function formatValue(string $value): string
    {
        if ($value === '') {
            return '""';
        }

        if (preg_match('/[\s"\\\\#]/', $value)) {
            return '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $value) . '"';
        }

        return $value;
    }
}
