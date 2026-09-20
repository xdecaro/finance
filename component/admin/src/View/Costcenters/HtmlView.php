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

        $references=$component->getReferenceLookupService();
        $this->items=$component->getFinanceQueryService()->listCostCenters();
        foreach ($this->items as &$item) {
            $item['owner_label']=$references->label($item['owner_component']??null,$item['owner_entity']??null,$item['owner_id']??null);
            $item['source_label']=$references->label($item['source_component']??null,$item['source_entity']??null,$item['source_id']??null);
        }
        unset($item);
        $this->organizations=$references->listOrganizations();
        parent::display($tpl);
    }
}
