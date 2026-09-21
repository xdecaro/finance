<?php
namespace Xdecaro\Component\Decarofinance\Administrator\View\Orders;
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
    public array $items=[];
    public array $accounts=[];
    public array $budgetLines=[];
    public array $costCenters=[];
    public array $organizations=[];
    public array $people=[];
    public bool $canApprove=false;
    public bool $canExecute=false;
    public bool $canEdit=false;

    public function display($tpl=null): void
    {
        $app=Factory::getApplication(); $identity=$app->getIdentity();
        if (!$identity->authorise('core.manage','com_decarofinance')) { throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403); }
        ToolbarHelper::title(Text::_('COM_DECAROFINANCE_ORDERS'),'credit');
        \Xdecaro\Component\Decarofinance\Administrator\Helper\UiHelper::loadAssets();
        $component=$app->bootComponent('com_decarofinance');
        if (!$component instanceof \Xdecaro\Component\Decarofinance\Administrator\Extension\DecarofinanceComponent) { throw new \RuntimeException('Finance component is unavailable.'); }
        $query=$component->getFinanceQueryService(); $references=$component->getReferenceLookupService();
        $this->items=$query->listOrders(); $this->accounts=$query->listAccounts(); $this->budgetLines=$query->listBudgetLines(); $this->costCenters=$query->listCostCenters();
        $this->organizations=$references->listOrganizations(); $this->people=$references->listPeople();
        $this->canApprove=$identity->authorise('finance.approve','com_decarofinance');
        $this->canExecute=$identity->authorise('finance.execute','com_decarofinance');
        $this->canEdit=$identity->authorise('core.edit','com_decarofinance');
        parent::display($tpl);
    }
}
