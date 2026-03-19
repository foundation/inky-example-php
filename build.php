<?php
require_once __DIR__ . '/vendor/autoload.php';

use Inky\Inky;

$template = file_get_contents('src/emails/welcome.inky');

// Build without data merge (template tags pass through)
$html = Inky::transformInline($template);
@mkdir('dist', 0755, true);
file_put_contents('dist/welcome.html', $html);
echo "built dist/welcome.html\n";

// Build with data merge
$data = file_get_contents('data/welcome.json');
$merged = Inky::transformWithData($template, $data);
file_put_contents('dist/welcome-merged.html', $merged);
echo "built dist/welcome-merged.html\n";

// Generate plain text
$text = Inky::toPlainText($merged);
file_put_contents('dist/welcome.txt', $text);
echo "built dist/welcome.txt\n";

// Validate
$diagnostics = Inky::validate($template);
$issues = json_decode($diagnostics, true);
if (count($issues) > 0) {
    echo "\nvalidation warnings:\n";
    foreach ($issues as $d) {
        echo "  [{$d['severity']}] {$d['rule']}: {$d['message']}\n";
    }
} else {
    echo "\nno validation issues found\n";
}
