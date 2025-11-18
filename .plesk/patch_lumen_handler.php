<?php
// Robust patcher for Lumen Handler::renderForConsole.
// Finds the renderForConsole method, replaces its body with a safe implementation
// that ensures a Symfony OutputInterface is always provided (NullOutput fallback).
// Idempotent and writes a timestamped backup before modifying the file.

$file = __DIR__ . "/../vendor/laravel/lumen-framework/src/Exceptions/Handler.php";
if (! file_exists($file)) {
    echo "Handler file not found: $file\n";
    exit(0);
}

$content = file_get_contents($file);

$needle = 'public function renderForConsole';
$pos = strpos($content, $needle);
if ($pos === false) {
    echo "No renderForConsole() method found in Handler.php\n";
    exit(0);
}

// Find opening brace of the method
$openBracePos = strpos($content, '{', $pos);
if ($openBracePos === false) {
    echo "Could not find opening brace for renderForConsole()\n";
    exit(1);
}

// Walk the file from the opening brace to find the matching closing brace
$len = strlen($content);
$depth = 0;
$i = $openBracePos;
for (; $i < $len; $i++) {
    $ch = $content[$i];
    if ($ch === '{') {
        $depth++;
    } elseif ($ch === '}') {
        $depth--;
        if ($depth === 0) {
            // $i is index of the matching closing brace
            break;
        }
    }
}

if ($depth !== 0) {
    echo "Could not locate end of renderForConsole() method (unbalanced braces)\n";
    exit(1);
}

$methodStart = $pos;
$methodEnd = $i; // inclusive index of '}'

$newMethod = <<<'NOW'
public function renderForConsole($output, Throwable $e)
{
    // Ensure we always pass a proper OutputInterface (use NullOutput as a safe fallback).
    if (! $output instanceof \Symfony\Component\Console\Output\OutputInterface) {
        $output = new \Symfony\Component\Console\Output\NullOutput();
    }

    if (class_exists('\Illuminate\Console\CommandNotFoundException') && $e instanceof \Illuminate\Console\CommandNotFoundException) {
        $message = \Illuminate\Support\Str::of($e->getMessage())->explode('.')->first();

        if (! empty($alternatives = $e->getAlternatives())) {
            $message .= '. Did you mean one of these?';

            // Use SymfonyStyle to present the error message when available
            if (class_exists('\Symfony\Component\Console\Style\SymfonyStyle')) {
                with(new \Symfony\Component\Console\Style\SymfonyStyle($output))->error($message);
            } else {
                $output->writeln('<error>' . $message . '</error>');
            }

            foreach ($alternatives as $alt) {
                $output->writeln('  - ' . $alt);
            }

            $output->writeln('');
        } else {
            if (class_exists('\Symfony\Component\Console\Style\SymfonyStyle')) {
                with(new \Symfony\Component\Console\Style\SymfonyStyle($output))->error($message);
            } else {
                $output->writeln('<error>' . $message . '</error>');
            }
        }

        return;
    }

    (new \Symfony\Component\Console\Application)->renderThrowable($e, $output);
}
NOW;

// Build the new content
$before = substr($content, 0, $methodStart);
$after = substr($content, $methodEnd + 1);

// Backup original file
@copy($file, $file . '.bak.' . time());

$newContent = $before . $newMethod . "\n" . $after;

if (file_put_contents($file, $newContent) === false) {
    echo "Failed to write patched Handler.php\n";
    exit(1);
}

echo "Patched Handler.php (renderForConsole)\n";
exit(0);

