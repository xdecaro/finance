<?php
namespace Xdecaro\Component\Decarofinance\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\CMS\WebAsset\WebAssetManager;

final class CoreIntegrationService
{
    private const COMPONENT = 'com_decarofinance';
    private const MINIMUM_CORE_VERSION = '1.3.0';

    public function isReferenceApiAvailable(): bool
    {
        $version = $this->getVersion();

        return $version !== ''
            && version_compare($version, self::MINIMUM_CORE_VERSION, '>=')
            && class_exists(\xdecaro\Core\Integration\EntityReference::class)
            && class_exists(\xdecaro\Core\Integration\RelationReference::class);
    }

    public function getVersion(): string
    {
        return class_exists(\xdecaro\Core\Version::class)
            ? trim((string) \xdecaro\Core\Version::VERSION)
            : '';
    }

    public function enableUi(WebAssetManager $webAssets): bool
    {
        $version = $this->getVersion();
        if ($version === ''
            || version_compare($version, self::MINIMUM_CORE_VERSION, '<')
            || !class_exists(\xdecaro\Core\Asset\AssetService::class)) {
            return false;
        }

        try {
            return (new \xdecaro\Core\Asset\AssetService())->useComponents($webAssets);
        } catch (\Throwable) {
            return false;
        }
    }

    public function createEntityReference(string $entity, int|string $id): object
    {
        if (!$this->isReferenceApiAvailable()) {
            throw new \RuntimeException('Core by xdecaro 1.3.0+ reference API is unavailable.');
        }

        return new \xdecaro\Core\Integration\EntityReference(self::COMPONENT, $entity, $id);
    }

    public function createRelationReference(
        string $sourceEntity,
        int|string $sourceId,
        string $targetComponent,
        string $targetEntity,
        int|string $targetId,
        string $relationType
    ): object {
        if (!$this->isReferenceApiAvailable()) {
            throw new \RuntimeException('Core by xdecaro 1.3.0+ reference API is unavailable.');
        }

        $source = new \xdecaro\Core\Integration\EntityReference(self::COMPONENT, $sourceEntity, $sourceId);
        $target = new \xdecaro\Core\Integration\EntityReference($targetComponent, $targetEntity, $targetId);

        return new \xdecaro\Core\Integration\RelationReference($source, $target, $relationType);
    }
}
