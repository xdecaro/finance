<?php
namespace Xdecaro\Component\Decarofinance\Administrator\View\Transactions;
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Xdecaro\Component\Decarofinance\Administrator\Service\FinanceQueryService;

final class HtmlView extends BaseHtmlView
{
    public array $items=[];
    public array $accounts=[];
    public array $budgetLines=[];

    public function display($tpl=null): void
    {
        $app=Factory::getApplication();
        if (!$app->getIdentity()->authorise('core.manage','com_decarofinance')) { throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403); }
        ToolbarHelper::title(Text::_('COM_DECAROFINANCE_TRANSACTIONS'),'credit');
        $wa=$app->getDocument()->getWebAssetManager(); $wa->getRegistry()->addExtensionRegistryFile('com_decarofinance'); $wa->useStyle('com_decarofinance.admin');
        $query=Factory::getContainer()->get(FinanceQueryService::class);
        $this->items=$query->listTransactions(); $this->accounts=$query->listAccounts(); $this->budgetLines=$query->listBudgetLines();
        parent::display($tpl);
    }
}
