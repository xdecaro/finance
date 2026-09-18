<?php
namespace Xdecaro\Component\Decarofinance\Administrator\View\Reports;
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
    public string $currency='EUR';
    public array $summary=[];
    public array $categories=[];
    public array $budgetUsage=[];

    public function display($tpl=null): void
    {
        $app=Factory::getApplication();
        if (!$app->getIdentity()->authorise('core.manage','com_decarofinance')) { throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403); }
        ToolbarHelper::title(Text::_('COM_DECAROFINANCE_REPORTS'),'chart');
        \Xdecaro\Component\Decarofinance\Administrator\Helper\UiHelper::loadAssets();
        $currency=strtoupper(trim($app->input->getCmd('currency','EUR'))); $this->currency=preg_match('/^[A-Z]{3}$/',$currency)?$currency:'EUR';
        $component=$app->bootComponent('com_decarofinance');
        if (!$component instanceof \Xdecaro\Component\Decarofinance\Administrator\Extension\DecarofinanceComponent) { throw new \RuntimeException('Finance component is unavailable.'); }
        $query=$component->getFinanceQueryService();
        $this->summary=$query->getReportSummary($this->currency);
        $this->categories=$query->reportByCategory($this->currency);
        $this->budgetUsage=$query->reportBudgetUsage($this->currency);
        parent::display($tpl);
    }
}
