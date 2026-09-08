<?php
define('_JEXEC', 1);
require_once __DIR__ . '/../component/admin/src/Service/CoreIntegrationService.php';

use Xdecaro\Component\Decarofinance\Administrator\Service\CoreIntegrationService;

$service = new CoreIntegrationService();
if ($service->isReferenceApiAvailable()) {
    throw new RuntimeException('Core must be absent in isolated smoke test.');
}
if ($service->getVersion() !== '') {
    throw new RuntimeException('Absent Core must report an empty version.');
}

try {
    $service->createEntityReference('obligation', 1);
    throw new RuntimeException('Missing Core must not create references.');
} catch (RuntimeException $e) {
    if (!str_contains($e->getMessage(), 'Core by xdecaro 1.3.0+')) {
        throw $e;
    }
}

$source = file_get_contents(__DIR__ . '/../component/admin/src/Service/CoreIntegrationService.php');
if ($source === false || !str_contains($source, 'xdecaro\\Core') || str_contains($source, 'Xdecaro\\Core')) {
    throw new RuntimeException('Finance must consume only the canonical xdecaro\\Core namespace.');
}

echo "Finance optional Core 1.3 fallback smoke passed.\n";
