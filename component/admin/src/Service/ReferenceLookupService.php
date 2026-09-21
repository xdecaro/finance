<?php
namespace Xdecaro\Component\Decarofinance\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Throwable;

/**
 * Read-only bridge to optional xdecaro entity providers.
 *
 * Finance never reads another product's tables directly. Optional products are
 * discovered at runtime and queried only through their public component APIs.
 */
final class ReferenceLookupService
{
    public const ORGANIZATIONS = 'com_xdecaroorganizations';
    public const PEOPLE = 'com_xdecaropeople';
    public const DOCUMENTS = 'com_decarodocuments';
    public const MEMBERSHIP = 'com_decaromembership';
    public const COMPETITIONS = 'com_xdecarocompetitions';

    public function isEnabled(string $component): bool
    {
        return ComponentHelper::isEnabled($component);
    }

    public function listOrganizations(int $limit = 200): array
    {
        if (!$this->isEnabled(self::ORGANIZATIONS)) {
            return [];
        }

        try {
            $component = Factory::getApplication()->bootComponent(self::ORGANIZATIONS);
            if (!is_object($component) || !method_exists($component, 'getOrganizationProviderService')) {
                return [];
            }

            $provider = $component->getOrganizationProviderService();
            if (!is_object($provider) || !method_exists($provider, 'searchOrganizations')) {
                return [];
            }

            return array_values((array) $provider->searchOrganizations([], max(1, min(200, $limit)), false));
        } catch (Throwable) {
            return [];
        }
    }

    public function listPeople(int $limit = 200): array
    {
        if (!$this->isEnabled(self::PEOPLE)) {
            return [];
        }

        try {
            $component = Factory::getApplication()->bootComponent(self::PEOPLE);
            if (!is_object($component) || !method_exists($component, 'getPersonProviderService')) {
                return [];
            }

            $provider = $component->getPersonProviderService();
            if (!is_object($provider) || !method_exists($provider, 'searchPeople')) {
                return [];
            }

            return array_values((array) $provider->searchPeople([], max(1, min(200, $limit)), false));
        } catch (Throwable) {
            return [];
        }
    }

    public function organizationReference(?string $uuid): array
    {
        $uuid = strtolower(trim((string) $uuid));
        if ($uuid === '') {
            return [null, null, null];
        }

        foreach ($this->listOrganizations() as $organization) {
            if (strtolower((string) ($organization['uuid'] ?? '')) === $uuid) {
                return [self::ORGANIZATIONS, 'organization', $uuid];
            }
        }

        throw new \InvalidArgumentException('Selected organization is unavailable.');
    }

    public function partyReference(?string $selection): array
    {
        $selection = trim((string) $selection);
        if ($selection === '') {
            return [null, null, null];
        }

        [$type, $uuid] = array_pad(explode(':', $selection, 2), 2, '');
        $uuid = strtolower(trim($uuid));

        if ($type === 'organization') {
            return $this->organizationReference($uuid);
        }

        if ($type === 'person') {
            foreach ($this->listPeople() as $person) {
                if (strtolower((string) ($person['uuid'] ?? '')) === $uuid) {
                    return [self::PEOPLE, 'person', $uuid];
                }
            }

            throw new \InvalidArgumentException('Selected person is unavailable.');
        }

        throw new \InvalidArgumentException('Selected Finance reference is invalid.');
    }

    public function label(?string $component, ?string $entity, int|string|null $id): string
    {
        $component = trim((string) $component);
        $entity = trim((string) $entity);
        $id = trim((string) $id);
        if ($component === '' || $entity === '' || $id === '') {
            return '';
        }

        try {
            if ($component === self::ORGANIZATIONS && $entity === 'organization' && $this->isEnabled(self::ORGANIZATIONS)) {
                $cmp = Factory::getApplication()->bootComponent(self::ORGANIZATIONS);
                if (is_object($cmp) && method_exists($cmp, 'getOrganizationProviderService')) {
                    $row = $cmp->getOrganizationProviderService()->getOrganization($id, false);
                    if (is_array($row) && trim((string) ($row['name'] ?? '')) !== '') {
                        return (string) $row['name'];
                    }
                }
            }

            if ($component === self::PEOPLE && $entity === 'person' && $this->isEnabled(self::PEOPLE)) {
                $cmp = Factory::getApplication()->bootComponent(self::PEOPLE);
                if (is_object($cmp) && method_exists($cmp, 'getPersonProviderService')) {
                    $row = $cmp->getPersonProviderService()->getPerson($id, false);
                    if (is_array($row)) {
                        $label = trim((string) ($row['display_name'] ?? ''));
                        if ($label !== '') {
                            return $label;
                        }
                    }
                }
            }
        } catch (Throwable) {
        }

        return $component . ':' . $entity . ':' . $id;
    }

    public function diagnostics(): array
    {
        $products = [
            self::ORGANIZATIONS => ['label' => 'Organizations', 'provider' => 'getOrganizationProviderService'],
            self::PEOPLE => ['label' => 'People', 'provider' => 'getPersonProviderService'],
            self::DOCUMENTS => ['label' => 'Documents', 'provider' => null],
            self::MEMBERSHIP => ['label' => 'Membership', 'provider' => null],
            self::COMPETITIONS => ['label' => 'Competitions', 'provider' => null],
        ];

        $result = [];
        foreach ($products as $component => $meta) {
            $enabled = $this->isEnabled($component);
            $provider = false;
            if ($enabled && $meta['provider'] !== null) {
                try {
                    $booted = Factory::getApplication()->bootComponent($component);
                    $provider = is_object($booted) && method_exists($booted, (string) $meta['provider']);
                } catch (Throwable) {
                    $provider = false;
                }
            }

            $result[] = [
                'component' => $component,
                'label' => $meta['label'],
                'enabled' => $enabled,
                'provider' => $provider,
            ];
        }

        return $result;
    }
}
