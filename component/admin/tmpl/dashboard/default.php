<?php
defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
$s=$this->summary;
?>
<div class="xdecaro-scope xdf-finance" data-core-ui="<?php echo $this->coreUiActive?'1':'0'; ?>">
  <div class="xdf-grid xdf-kpis">
    <a class="xdecaro-card xdf-kpi" href="<?php echo Route::_('index.php?option=com_decarofinance&view=obligations'); ?>"><span><?php echo Text::_('COM_DECAROFINANCE_OPEN_OBLIGATIONS'); ?></span><strong><?php echo (int)$s['open_obligations']; ?></strong></a>
    <a class="xdecaro-card xdf-kpi" href="<?php echo Route::_('index.php?option=com_decarofinance&view=obligations'); ?>"><span><?php echo Text::_('COM_DECAROFINANCE_OVERDUE'); ?></span><strong><?php echo (int)$s['overdue_obligations']; ?></strong></a>
    <section class="xdecaro-card xdf-kpi"><span><?php echo Text::_('COM_DECAROFINANCE_OUTSTANDING'); ?></span><strong><?php echo number_format((float)$s['open_amount'],2,',','.'); ?> €</strong></section>
    <section class="xdecaro-card xdf-kpi"><span><?php echo Text::_('COM_DECAROFINANCE_DEPOSIT_BALANCE'); ?></span><strong><?php echo number_format((float)$s['deposit_balance'],2,',','.'); ?> €</strong></section>
  </div>
  <div class="xdf-grid xdf-links">
    <a class="xdecaro-card xdf-action" href="<?php echo Route::_('index.php?option=com_decarofinance&view=payments'); ?>"><?php echo Text::_('COM_DECAROFINANCE_PAYMENTS'); ?></a>
    <a class="xdecaro-card xdf-action" href="<?php echo Route::_('index.php?option=com_decarofinance&view=deposits'); ?>"><?php echo Text::_('COM_DECAROFINANCE_DEPOSITS'); ?></a>
    <a class="xdecaro-card xdf-action" href="<?php echo Route::_('index.php?option=com_decarofinance&view=budgets'); ?>"><?php echo Text::_('COM_DECAROFINANCE_BUDGETS'); ?></a>
  </div>
</div>
