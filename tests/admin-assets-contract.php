<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$helper = $root . '/component/admin/src/Helper/UiHelper.php';
$css = $root . '/component/media/css/admin.css';

if (!is_file($helper) || !is_file($css)) {
    fwrite(STDERR, "ERROR: Finance administrator asset files are missing.\n");
    exit(1);
}

$helperSource = (string) file_get_contents($helper);

foreach ([
    "registerStyle(",
    "com_decarofinance/css/admin.css",
    "useStyle(",
    "com_decarofinance.admin.runtime",
] as $required) {
    if (!str_contains($helperSource, $required)) {
        fwrite(STDERR, "ERROR: UiHelper is missing required asset loader fragment: {$required}\n");
        exit(1);
    }
}

$viewsRoot = $root . '/component/admin/src/View';
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($viewsRoot, FilesystemIterator::SKIP_DOTS));
$failures = [];

foreach ($iterator as $file) {
    if (!$file->isFile() || $file->getFilename() !== 'HtmlView.php') {
        continue;
    }

    $source = (string) file_get_contents($file->getPathname());

    if (!str_contains($source, 'UiHelper::loadAssets()')) {
        $failures[] = str_replace($root . '/', '', $file->getPathname());
    }

    if (str_contains($source, "addExtensionRegistryFile('com_decarofinance')")
        || str_contains($source, "useStyle('com_decarofinance.admin')")) {
        $failures[] = str_replace($root . '/', '', $file->getPathname()) . ' (legacy registry path)';
    }
}

if ($failures !== []) {
    fwrite(STDERR, "ERROR: Finance administrator views do not consistently load runtime CSS:\n - " . implode("\n - ", $failures) . "\n");
    exit(1);
}

fwrite(STDOUT, "Finance administrator asset loading contract OK\n");
