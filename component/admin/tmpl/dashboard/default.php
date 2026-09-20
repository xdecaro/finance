<?php
defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
$s=$this->summary; $currency=htmlspecialchars((string)($s['currency']??'EUR'),ENT_QUOTES,'UTF-8');
$money=static fn($v)=>number_format((float)$v,2,',','.').' '.$currency;
?>
<div class="xdecaro-scope xdf-finance" data-core-ui="<?php echo $this->coreUiActive?'1':'0'; ?>">
  <div class="xdf-grid xdf-kpis">
    <a class="xdecaro-card xdf-kpi" href="<?php echo Route::_('index.php?option=com_decarofinance&view=obligations'); ?>"><span><?php echo Text::_('COM_DECAROFINANCE_OPEN_OBLIGATIONS'); ?></span><strong><?php echo (int)$s['open_obligations']; ?></strong></a>
    <a class="xdecaro-card xdf-kpi" href="<?php echo Route::_('index.php?option=com_decarofinance&view=obligations'); ?>"><span><?php echo Text::_('COM_DECAROFINANCE_OVERDUE'); ?></span><strong><?php echo (int)$s['overdue_obligations']; ?></strong></a>
    <a class="xdecaro-card xdf-kpi" href="<?php echo Route::_('index.php?option=com_decarofinance&view=accounts'); ?>"><span><?php echo Text::_('COM_DECAROFINANCE_ACCOUNT_BALANCE'); ?></span><strong><?php echo $money($s['account_balance']); ?></strong></a>
    <a class="xdecaro-card xdf-kpi" href="<?php echo Route::_('index.php?option=com_decarofinance&view=orders'); ?>"><span><?php echo Text::_('COM_DECAROFINANCE_PENDING_ORDERS'); ?></span><strong><?php echo (int)$s['pending_orders']; ?></strong></a>
    <section class="xdecaro-card xdf-kpi"><span><?php echo Text::_('COM_DECAROFINANCE_OUTSTANDING'); ?></span><strong><?php echo $money($s['open_amount']); ?></strong></section>
    <section class="xdecaro-card xdf-kpi"><span><?php echo Text::_('COM_DECAROFINANCE_DEPOSIT_BALANCE'); ?></span><strong><?php echo $money($s['deposit_balance']); ?></strong></section>
    <a class="xdecaro-card xdf-kpi" href="<?php echo Route::_('index.php?option=com_decarofinance&view=cashchecks'); ?>"><span><?php echo Text::_('COM_DECAROFINANCE_CASH_VARIANCES'); ?></span><strong><?php echo (int)$s['cash_check_variances']; ?></strong></a>
    <a class="xdecaro-card xdf-kpi" href="<?php echo Route::_('index.php?option=com_decarofinance&view=statements'); ?>"><span><?php echo Text::_('COM_DECAROFINANCE_DRAFT_STATEMENTS'); ?></span><strong><?php echo (int)$s['draft_statements']; ?></strong></a>
  </div>
  <div class="xdf-grid xdf-links">
    <a class="xdecaro-card xdf-action" href="<?php echo Route::_('index.php?option=com_decarofinance&view=transactions'); ?>"><?php echo Text::_('COM_DECAROFINANCE_TRANSACTIONS'); ?></a>
    <a class="xdecaro-card xdf-action" href="<?php echo Route::_('index.php?option=com_decarofinance&view=payments'); ?>"><?php echo Text::_('COM_DECAROFINANCE_PAYMENTS'); ?></a>
    <a class="xdecaro-card xdf-action" href="<?php echo Route::_('index.php?option=com_decarofinance&view=transfers'); ?>"><?php echo Text::_('COM_DECAROFINANCE_TRANSFERS'); ?></a>
    <a class="xdecaro-card xdf-action" href="<?php echo Route::_('index.php?option=com_decarofinance&view=cashchecks'); ?>"><?php echo Text::_('COM_DECAROFINANCE_CASH_CHECKS'); ?></a>
    <a class="xdecaro-card xdf-action" href="<?php echo Route::_('index.php?option=com_decarofinance&view=statements'); ?>"><?php echo Text::_('COM_DECAROFINANCE_STATEMENTS'); ?></a>
    <a class="xdecaro-card xdf-action" href="<?php echo Route::_('index.php?option=com_decarofinance&view=deposits'); ?>"><?php echo Text::_('COM_DECAROFINANCE_DEPOSITS'); ?></a>
    <a class="xdecaro-card xdf-action" href="<?php echo Route::_('index.php?option=com_decarofinance&view=budgets'); ?>"><?php echo Text::_('COM_DECAROFINANCE_BUDGETS'); ?></a>
    <a class="xdecaro-card xdf-action" href="<?php echo Route::_('index.php?option=com_decarofinance&view=costcenters'); ?>"><?php echo Text::_('COM_DECAROFINANCE_COST_CENTERS'); ?></a>
    <a class="xdecaro-card xdf-action" href="<?php echo Route::_('index.php?option=com_decarofinance&view=reports'); ?>"><?php echo Text::_('COM_DECAROFINANCE_REPORTS'); ?></a>
  </div>
</div>
