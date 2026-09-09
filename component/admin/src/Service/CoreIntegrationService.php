<?php
namespace Xdecaro\Component\Decarofinance\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\CMS\WebAsset\WebAssetManager;

final class CoreIntegrationService
{
    private const COMPONENT = 'com_decarofinance';
    private const MINIMUM_CORE_VERSION = '1.4.0';

    public function getVersion(): string
    {
        return class_exists(\xdecaro\Core\Version::class) ? trim((string) \xdecaro\Core\Version::VERSION) : '';
    }

    public function isReferenceApiAvailable(): bool
    {
        $v=$this->getVersion(); return $v!=='' && version_compare($v,self::MINIMUM_CORE_VERSION,'>=') && class_exists(\xdecaro\Core\Integration\EntityReference::class) && class_exists(\xdecaro\Core\Integration\RelationReference::class);
    }

    public function isCapabilityRegistryAvailable(): bool
    {
        $v=$this->getVersion(); return $v!=='' && version_compare($v,self::MINIMUM_CORE_VERSION,'>=') && class_exists(\xdecaro\Core\Integration\CapabilityRegistry::class) && class_exists(\xdecaro\Core\Integration\Capability::class);
    }

    public function registerCapabilities(object $registry): bool
    {
        if (!$this->isCapabilityRegistryAvailable() || !$registry instanceof \xdecaro\Core\Integration\CapabilityRegistry) { return false; }
        $caps=['finance.obligations','finance.payments','finance.deposits','finance.budgets','finance.query','finance.analytics.provider','finance.notifications.bridge','finance.tasks.bridge'];
        foreach ($caps as $name) { $registry->register(new \xdecaro\Core\Integration\Capability(self::COMPONENT,$name,'1')); }
        return true;
    }

    public function enableUi(WebAssetManager $webAssets): bool
    {
        $v=$this->getVersion(); if ($v===''||version_compare($v,self::MINIMUM_CORE_VERSION,'<')||!class_exists(\xdecaro\Core\Asset\AssetService::class)) return false;
        try { return (new \xdecaro\Core\Asset\AssetService())->useComponents($webAssets); } catch (\Throwable) { return false; }
    }

    public function createEntityReference(string $entity,int|string $id): object
    {
        if (!$this->isReferenceApiAvailable()) throw new \RuntimeException('Core by xdecaro 1.4.0+ reference API is unavailable.');
        return new \xdecaro\Core\Integration\EntityReference(self::COMPONENT,$entity,$id);
    }

    public function createRelationReference(string $sourceEntity,int|string $sourceId,string $targetComponent,string $targetEntity,int|string $targetId,string $relationType): object
    {
        if (!$this->isReferenceApiAvailable()) throw new \RuntimeException('Core by xdecaro 1.4.0+ reference API is unavailable.');
        return new \xdecaro\Core\Integration\RelationReference(new \xdecaro\Core\Integration\EntityReference(self::COMPONENT,$sourceEntity,$sourceId),new \xdecaro\Core\Integration\EntityReference($targetComponent,$targetEntity,$targetId),$relationType);
    }
}
