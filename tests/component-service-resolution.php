<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$src = $root . '/component/admin/src';
$failures = [];

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS));
foreach ($iterator as $file) {
    if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
        continue;
    }

    $contents = (string) file_get_contents($file->getPathname());
    if (str_contains($contents, 'Factory::getContainer()->get(')) {
        $failures[] = str_replace($root . '/', '', $file->getPathname());
    }
}

if ($failures !== []) {
    fwrite(STDERR, "ERROR: administrator code bypasses the component service container:\n - " . implode("\n - ", $failures) . "\n");
    exit(1);
}

foreach ([
    '/component/admin/src/View/Dashboard/HtmlView.php',
    '/component/admin/src/Controller/FinanceController.php',
] as $relative) {
    $contents = (string) file_get_contents($root . $relative);
    if (!str_contains($contents, "bootComponent('com_decarofinance')")) {
        fwrite(STDERR, "ERROR: expected bootComponent service resolution is missing in {$relative}.\n");
        exit(1);
    }
}

fwrite(STDOUT, "Finance component service resolution contract OK\n");
