<?php

declare(strict_types=1);

/**
 * A small production-shaped wrapper around Inky::build:
 * one theme, one template directory, optional shell caching.
 *
 * The theme is linked per-render by splicing a `<link rel="stylesheet">`
 * into the raw template source before it goes to Inky::build — see
 * injectThemeLink() for why that has to branch on the template's shape
 * (layout-based vs. full-document vs. bare fragment).
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
     * Note: a cache HIT below returns early with an empty $warnings array
     * — it never re-runs Inky::build(), so it has nothing to report.
     * Warnings are only ever surfaced on the build that originally produced
     * the cached entry; a warm run reporting zero warnings is not itself
     * evidence that the template is warning-clean.
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

        $themeHref = $this->relativeThemeHref();
        $source = $this->injectThemeLink($source, $themeHref);

        $options = array_merge(['plain_text' => true], $options);
        if ($data !== []) {
            $options['data'] = $data;
        }

        $cachePath = null;
        if ($this->cacheDir !== null) {
            $key = hash('sha256', $source . '|' . file_get_contents($this->themePath) . '|' . json_encode($options));
            $cachePath = $this->cacheDir . '/' . $key . '.json';
            if (is_file($cachePath)) {
                $hit = json_decode(file_get_contents($cachePath), true);
                return new \Inky\BuildResult($hit['html'], $hit['text'], []);
            }
        }

        $result = \Inky\Inky::build($source, $this->templateDir, $options);
        foreach ($result->warnings as $warning) {
            fwrite(STDERR, "warning: {$warning}\n");
        }

        if ($cachePath !== null) {
            @mkdir($this->cacheDir, 0755, true);
            file_put_contents($cachePath, json_encode(['html' => $result->html, 'text' => $result->text]));
        }

        return $result;
    }

    /**
     * Splice a `<link rel="stylesheet" href="...">` for the renderer's theme
     * into the raw template source, before it is handed to Inky::build.
     *
     * This has to branch on the *shape* of the incoming template, because
     * a naive `str_replace('</head>', ..., $source)` — the previous
     * implementation — silently does nothing on the most common shape in
     * this suite: a layout-based template.
     *
     * Background: every real template here starts with
     * `<layout src="...">` (see shared/layout.html and examples 09/10).
     * inky-core's `process_layout` (crates/inky-core/src/include.rs) finds
     * that opening tag and keeps only the content AFTER it — anything
     * textually before the tag in the source string is simply discarded,
     * never making it into the resolved document. The `</head>` tag itself
     * lives inside the *layout file*, not in the child template we're
     * given here, so a child-template-only str_replace('</head>', ...)
     * never finds a match: no error, no warning, just a compiled page with
     * no theme. That's the bug this method exists to fix.
     *
     * The fix relies on two things confirmed empirically against
     * inky-core: (1) content placed immediately after the `<layout ...>`
     * tag is exactly what flows into the layout's `<yield>` slot, so a
     * `<link>` inserted there ends up in the final document; and (2)
     * inky-core's SCSS extractor (`extract_scss_sources` in
     * crates/inky-core/src/scss.rs) scans the *entire* layout-resolved
     * document for `<link href="*.scss">` — the tag does NOT need to be
     * inside `<head>` to be found, compiled, and stripped. So inserting
     * right after the layout tag is both sufficient and safe.
     *
     * Three cases, in priority order:
     *
     * 1. Layout-based template (`<layout ...>` as the opening tag): insert
     *    the link immediately after that tag's closing `>`. This is the
     *    fixed case above.
     * 2. Full-document template (no `<layout>`, but has a literal
     *    `</head>`): insert the link just before `</head>`. This was the
     *    old (and still correct, for this shape) behavior.
     * 3. Bare fragment (neither of the above): prepend the link to the
     *    source. The SCSS extractor finds and strips a leading `<link>`
     *    just as well as one embedded deeper in the document.
     */
    private function injectThemeLink(string $source, string $href): string
    {
        $link = "<link rel=\"stylesheet\" href=\"{$href}\">";

        if (preg_match('/<layout\s[^>]*>/i', $source, $matches, PREG_OFFSET_CAPTURE)) {
            [$tag, $offset] = $matches[0];
            $insertAt = $offset + strlen($tag);
            return substr($source, 0, $insertAt) . "\n{$link}" . substr($source, $insertAt);
        }

        if (str_contains($source, '</head>')) {
            return str_replace('</head>', "{$link}\n</head>", $source);
        }

        return "{$link}\n{$source}";
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
