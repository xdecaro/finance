<?php
namespace Xdecaro\Component\Decarofinance\Administrator\View\Costcenters;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
    public array $items=[];
    public array $organizations=[];

    public function display($tpl=null): void
    {
        $app=Factory::getApplication();
        if (!$app->getIdentity()->authorise('core.manage','com_decarofinance')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        }

        ToolbarHelper::title(Text::_('COM_DECAROFINANCE_COST_CENTERS'),'folder');
        \Xdecaro\Component\Decarofinance\Administrator\Helper\UiHelper::loadAssets();

        $component=$app->bootComponent('com_decarofinance');
        if (!$component instanceof \Xdecaro\Component\Decarofinance\Administrator\Extension\DecarofinanceComponent) {
            throw new \RuntimeException('Finance component is unavailable.');
        }

        $this->items=$component->getFinanceQueryService()->listCostCenters();
        $this->organizations=$component->getReferenceLookupService()->listOrganizations();
        parent::display($tpl);
    }
}
