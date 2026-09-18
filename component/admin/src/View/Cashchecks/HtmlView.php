<?php
namespace Xdecaro\Component\Decarofinance\Administrator\View\Cashchecks;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
    public array $items=[];
    public array $cashAccounts=[];
    public bool $canReconcile=false;

    public function display($tpl=null): void
    {
        $app=Factory::getApplication(); $identity=$app->getIdentity();
        if (!$identity->authorise('core.manage','com_decarofinance')) { throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403); }
        ToolbarHelper::title(Text::_('COM_DECAROFINANCE_CASH_CHECKS'),'check-circle');
        \Xdecaro\Component\Decarofinance\Administrator\Helper\UiHelper::loadAssets();
        $component=$app->bootComponent('com_decarofinance');
        if (!$component instanceof \Xdecaro\Component\Decarofinance\Administrator\Extension\DecarofinanceComponent) { throw new \RuntimeException('Finance component is unavailable.'); }
        $query=$component->getFinanceQueryService();
        $this->items=$query->listCashChecks();
        $this->cashAccounts=array_values(array_filter($query->listAccounts(),static fn(array $row):bool => ($row['account_type'] ?? '')==='cash' && (int)($row['state'] ?? 0)===1));
        $this->canReconcile=$identity->authorise('finance.reconcile','com_decarofinance');
        parent::display($tpl);
    }
}
