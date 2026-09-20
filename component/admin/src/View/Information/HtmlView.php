<?php
namespace Xdecaro\Component\Decarofinance\Administrator\View\Information;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Version;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Throwable;
use Xdecaro\Component\Decarofinance\Administrator\Extension\DecarofinanceComponent;
use Xdecaro\Component\Decarofinance\Administrator\Helper\UiHelper;

final class HtmlView extends BaseHtmlView
{
    private const UPDATE_SITE_URL='https://raw.githubusercontent.com/xdecaro/finance/main/updates/pkg_decarofinance.xml';
    private const EXPECTED_TABLES=[
        '#__decarofinance_budgets',
        '#__decarofinance_budget_lines',
        '#__decarofinance_cost_centers',
        '#__decarofinance_obligations',
        '#__decarofinance_payments',
        '#__decarofinance_payment_allocations',
        '#__decarofinance_deposit_accounts',
        '#__decarofinance_deposit_movements',
        '#__decarofinance_accounts',
        '#__decarofinance_transactions',
        '#__decarofinance_orders',
        '#__decarofinance_order_approvals',
        '#__decarofinance_transfers',
        '#__decarofinance_cash_checks',
        '#__decarofinance_statements',
        '#__decarofinance_statement_lines',
    ];

    public array $info=[];
    public bool $canManageInstaller=false;

    public function display($tpl=null): void
    {
        $app=Factory::getApplication();
        $user=$app->getIdentity();
        if (!$user->authorise('core.manage','com_decarofinance')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        }

        ToolbarHelper::title(Text::_('COM_DECAROFINANCE_INFORMATION'),'info-circle');
        UiHelper::loadAssets();

        $component=$app->bootComponent('com_decarofinance');
        if (!$component instanceof DecarofinanceComponent) {
            throw new \RuntimeException('Finance component is unavailable.');
        }

        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $this->info=$this->buildInfo($db,$component);
        $this->canManageInstaller=$user->authorise('core.manage','com_installer');

        parent::display($tpl);
    }

    private function buildInfo(DatabaseInterface $db, DecarofinanceComponent $component): array
    {
        $extensions=[
            'package'=>$this->extension($db,'package','pkg_decarofinance'),
            'component'=>$this->extension($db,'component','com_decarofinance'),
            'task'=>$this->extension($db,'plugin','decarofinance','task'),
            'analytics'=>$this->extension($db,'plugin','decarofinance','xdecaroanalytics'),
        ];

        $versions=[];
        foreach($extensions as $key=>$extension) {
            $versions[$key]=$this->manifestVersion($extension);
        }
        $installedVersion=$versions['package'] ?: $versions['component'] ?: '0.0.0';

        $consistent=$installedVersion!=='0.0.0';
        foreach(['package','component','task','analytics'] as $key) {
            if ($versions[$key]==='' || $versions[$key]!==$installedVersion) {
                $consistent=false;
            }
        }

        $tables=$this->tableHealth($db);
        $schemaVersion=$this->schemaVersion($db,$extensions['component']);
        $update=$this->updateState($db,$extensions['package'],$installedVersion);

        $core=$component->getCoreIntegrationService();
        $coreVersion=$core->getVersion();
        $coreApi=$core->isReferenceApiAvailable();
        $coreUi=false;
        try {
            $coreUi=$core->enableUi(Factory::getApplication()->getDocument()->getWebAssetManager());
        } catch(Throwable) {
            $coreUi=false;
        }

        $integrations=$component->getReferenceLookupService()->diagnostics();
        $joomlaVersion=(new Version())->getShortVersion();

        $critical=[];
        if(!$consistent) $critical[]='versions';
        if(!$tables['all_present']) $critical[]='tables';
        if($schemaVersion!==$installedVersion) $critical[]='schema';

        $warnings=[];
        if(!$update['site_enabled']) $warnings[]='update_site';
        if($update['available']) $warnings[]='update_available';
        if($extensions['task']!==null && (int)($extensions['task']->enabled??0)!==1) $warnings[]='task_plugin';
        if($extensions['analytics']!==null && (int)($extensions['analytics']->enabled??0)!==1) $warnings[]='analytics_plugin';
        if($coreVersion!=='' && !$coreApi) $warnings[]='core_api';

        return [
            'installed_version'=>$installedVersion,
            'versions'=>$versions,
            'consistent'=>$consistent,
            'extensions'=>$extensions,
            'joomla_version'=>$joomlaVersion,
            'php_version'=>PHP_VERSION,
            'database_type'=>method_exists($db,'getServerType') ? (string)$db->getServerType() : (string)$db->getName(),
            'database_version'=>(string)$db->getVersion(),
            'schema_version'=>$schemaVersion,
            'tables'=>$tables,
            'update'=>$update,
            'core'=>[
                'version'=>$coreVersion,
                'api'=>$coreApi,
                'ui'=>$coreUi,
                'installed'=>$coreVersion!=='',
            ],
            'integrations'=>$integrations,
            'services'=>[
                'finance'=>method_exists($component,'getFinanceService'),
                'query'=>method_exists($component,'getFinanceQueryService'),
                'references'=>method_exists($component,'getReferenceLookupService'),
            ],
            'critical'=>$critical,
            'warnings'=>$warnings,
            'update_site_url'=>self::UPDATE_SITE_URL,
        ];
    }

    private function extension(DatabaseInterface $db,string $type,string $element,?string $folder=null): ?object
    {
        try {
            $q=$db->getQuery(true)
                ->select(['extension_id','manifest_cache','enabled','client_id'])
                ->from($db->quoteName('#__extensions'))
                ->where($db->quoteName('type').' = :type')
                ->where($db->quoteName('element').' = :element')
                ->bind(':type',$type)
                ->bind(':element',$element);
            if($folder!==null) {
                $q->where($db->quoteName('folder').' = :folder')->bind(':folder',$folder);
            }
            return $db->setQuery($q,0,1)->loadObject() ?: null;
        } catch(Throwable) {
            return null;
        }
    }

    private function manifestVersion(?object $extension): string
    {
        if($extension===null || empty($extension->manifest_cache)) return '';
        $manifest=json_decode((string)$extension->manifest_cache,true);
        return is_array($manifest) ? trim((string)($manifest['version']??'')) : '';
    }

    private function tableHealth(DatabaseInterface $db): array
    {
        try {
            $available=array_flip($db->getTableList());
        } catch(Throwable) {
            $available=[];
        }
        $items=[];
        foreach(self::EXPECTED_TABLES as $table) {
            $items[$table]=isset($available[$db->replacePrefix($table)]);
        }
        $present=count(array_filter($items));
        return [
            'items'=>$items,
            'present'=>$present,
            'expected'=>count($items),
            'all_present'=>$present===count($items),
        ];
    }

    private function schemaVersion(DatabaseInterface $db,?object $component): string
    {
        if($component===null || (int)($component->extension_id??0)<1) return '';
        try {
            $extensionId=(int)$component->extension_id;
            $q=$db->getQuery(true)
                ->select($db->quoteName('version_id'))
                ->from($db->quoteName('#__schemas'))
                ->where($db->quoteName('extension_id').' = :id')
                ->bind(':id',$extensionId,ParameterType::INTEGER);
            return trim((string)$db->setQuery($q,0,1)->loadResult());
        } catch(Throwable) {
            return '';
        }
    }

    private function updateState(DatabaseInterface $db,?object $package,string $installedVersion): array
    {
        $result=[
            'site_enabled'=>false,
            'last_check'=>0,
            'latest'=>'',
            'available'=>false,
        ];
        if($package===null || (int)($package->extension_id??0)<1) return $result;

        $extensionId=(int)$package->extension_id;
        try {
            $q=$db->getQuery(true)
                ->select(['s.enabled','s.last_check_timestamp'])
                ->from($db->quoteName('#__update_sites','s'))
                ->innerJoin($db->quoteName('#__update_sites_extensions','m').' ON m.update_site_id = s.update_site_id')
                ->where('m.extension_id = :id')
                ->where('s.location = :location')
                ->bind(':id',$extensionId,ParameterType::INTEGER)
                ->bind(':location',$resultUrl=self::UPDATE_SITE_URL);
            $site=$db->setQuery($q,0,1)->loadObject();
            if($site) {
                $result['site_enabled']=(int)$site->enabled===1;
                $result['last_check']=max(0,(int)$site->last_check_timestamp);
            }
        } catch(Throwable) {
        }

        try {
            $q=$db->getQuery(true)
                ->select($db->quoteName('version'))
                ->from($db->quoteName('#__updates'))
                ->where($db->quoteName('extension_id').' = :id')
                ->bind(':id',$extensionId,ParameterType::INTEGER)
                ->order($db->quoteName('update_id').' DESC');
            $result['latest']=trim((string)$db->setQuery($q,0,1)->loadResult());
        } catch(Throwable) {
        }

        $result['available']=$result['latest']!=='' && version_compare($result['latest'],$installedVersion,'>');
        return $result;
    }
}
