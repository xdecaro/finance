<?php
namespace Xdecaro\Component\Decarofinance\Administrator\View\Statements;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
    public array $items=[];
    public array $lines=[];
    public bool $canCreate=false;
    public bool $canReconcile=false;
    public bool $canApprove=false;

    public function display($tpl=null): void
    {
        $app=Factory::getApplication(); $identity=$app->getIdentity();
        if (!$identity->authorise('core.manage','com_decarofinance')) { throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403); }
        ToolbarHelper::title(Text::_('COM_DECAROFINANCE_STATEMENTS'),'file');
        $wa=$app->getDocument()->getWebAssetManager(); $wa->getRegistry()->addExtensionRegistryFile('com_decarofinance'); $wa->useStyle('com_decarofinance.admin');
        $component=$app->bootComponent('com_decarofinance');
        if (!$component instanceof \Xdecaro\Component\Decarofinance\Administrator\Extension\DecarofinanceComponent) { throw new \RuntimeException('Finance component is unavailable.'); }
        $query=$component->getFinanceQueryService();
        $this->items=$query->listStatements();
        foreach ($this->items as $row) { $this->lines[(int)$row['id']]=$query->listStatementLines((int)$row['id']); }
        $this->canCreate=$identity->authorise('core.create','com_decarofinance');
        $this->canReconcile=$identity->authorise('finance.reconcile','com_decarofinance');
        $this->canApprove=$identity->authorise('finance.approve','com_decarofinance');
        parent::display($tpl);
    }
}
