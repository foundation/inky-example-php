<?php

declare(strict_types=1);

/**
 * A small production-shaped wrapper around Inky::build:
 * one theme, one template directory, optional shell caching.
 *
 * This is the class you'd adapt inside a CMS or app — see examples
 * 09 (transactional set) and 10 (Twig integration) for usage.
 */
final class EmailRenderer
{
    public function __construct(
        private readonly string $templateDir,
        private readonly string $themePath,
        private readonly ?string $cacheDir = null,
    ) {
    }

    /**
     * Build one template. $data is merged into the template (MiniJinja,
     * Twig-compatible syntax). Warnings go to STDERR; failures throw
     * \Inky\BuildException (warnings attached).
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed> $options extra Inky::build options
     */
    public function render(string $template, array $data = [], array $options = []): \Inky\BuildResult
    {
        $source = file_get_contents($this->templateDir . '/' . $template);
        if ($source === false) {
            throw new \RuntimeException("Template not found: {$template}");
        }

        // The theme is linked per-render so one renderer == one brand.
        $themeHref = $this->relativeThemeHref();
        $source = str_replace('</head>', "<link rel=\"stylesheet\" href=\"{$themeHref}\">\n</head>", $source);

        $options = array_merge(['plain_text' => true], $options);
        if ($data !== []) {
            $options['data'] = $data;
        }

        if ($this->cacheDir !== null) {
            $key = hash('sha256', $source . '|' . file_get_contents($this->themePath) . '|' . json_encode($options));
            $cached = $this->cacheDir . '/' . $key . '.json';
            if (is_file($cached)) {
                $hit = json_decode(file_get_contents($cached), true);
                return new \Inky\BuildResult($hit['html'], $hit['text'], []);
            }
        }

        $result = \Inky\Inky::build($source, $this->templateDir, $options);
        foreach ($result->warnings as $warning) {
            fwrite(STDERR, "warning: {$warning}\n");
        }

        if (isset($cached)) {
            @mkdir($this->cacheDir, 0755, true);
            file_put_contents($cached, json_encode(['html' => $result->html, 'text' => $result->text]));
        }

        return $result;
    }

    private function relativeThemeHref(): string
    {
        // Themes live outside templateDir; compute the href base_path resolves.
        // (Verified in Task 1: the pipeline resolves hrefs relative to base_path.)
        $themeReal = realpath($this->themePath);
        $dirReal = realpath($this->templateDir);
        if ($themeReal !== false && $dirReal !== false && str_starts_with($themeReal, $dirReal . '/')) {
            return substr($themeReal, strlen($dirReal) + 1);
        }
        // Fall back to a relative traversal computed from the two paths.
        return self::relativePath($dirReal ?: $this->templateDir, $themeReal ?: $this->themePath);
    }

    private static function relativePath(string $from, string $to): string
    {
        $from = explode('/', rtrim($from, '/'));
        $to = explode('/', $to);
        while ($from && $to && $from[0] === $to[0]) {
            array_shift($from);
            array_shift($to);
        }
        return str_repeat('../', count($from)) . implode('/', $to);
    }
}
