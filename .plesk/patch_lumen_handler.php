<?php
// Patch the Lumen Exception Handler to ensure renderForConsole always passes
// a Symfony OutputInterface (use NullOutput fallback). This is a temporary
// runtime shim to avoid type errors when artisan is executed in certain
// chrooted deploy environments. Safe to run repeatedly.

$file = __DIR__ . "/../vendor/laravel/lumen-framework/src/Exceptions/Handler.php";
if (!file_exists($file)) {
    echo "Handler file not found: $file\n";
    exit(0);
}

$content = file_get_contents($file);
$pattern = '/public function renderForConsole\s*\((?:.|\s)*?\n\s*\}\n/s';

$replacement = <<<'PHP'
public function renderForConsole($output, Throwable $e)
{
    // Ensure we always pass a proper OutputInterface (use NullOutput as a safe fallback).
    if (! $output instanceof \Symfony\Component\Console\Output\OutputInterface) {
        $output = new \Symfony\Component\Console\Output\NullOutput();
    }

    if ($e instanceof CommandNotFoundException) {
        $message = str($e->getMessage())->explode('.')->first();

        if (! empty($alternatives = $e->getAlternatives())) {
            $message .= '. Did you mean one of these?';

            with(new Error($output))->render($message);
            with(new BulletList($output))->render($e->getAlternatives());

            $output->writeln('');
        } else {
            with(new Error($output))->render($message);
        }

        return;
    }

    (new \Symfony\Component\Console\Application)->renderThrowable($e, $output);
}
PHP;

$new = preg_replace($pattern, $replacement . "\n", $content, 1, $count);
if ($new === null) {
    echo "Regex error while patching Handler.php\n";
    exit(1);
}

if ($count > 0) {
    file_put_contents($file, $new);
    echo "Patched Handler.php (renderForConsole)\n";
} else {
    echo "No renderForConsole() method replaced (already patched?).\n";
}

exit(0);
