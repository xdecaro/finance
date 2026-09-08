<?php
defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
?>
<div class="xdecaro-scope xdf-finance" data-core-ui="<?php echo $this->coreUiActive ? '1' : '0'; ?>">
  <div class="xdf-grid">
    <section class="xdecaro-card"><div class="xdecaro-card__header"><h2 class="xdecaro-card__title"><?php echo Text::_('COM_DECAROFINANCE_BUDGETS'); ?></h2></div><div class="xdecaro-card__body"><p><?php echo Text::_('COM_DECAROFINANCE_BUDGETS_DESC'); ?></p></div></section>
    <section class="xdecaro-card"><div class="xdecaro-card__header"><h2 class="xdecaro-card__title"><?php echo Text::_('COM_DECAROFINANCE_OBLIGATIONS'); ?></h2></div><div class="xdecaro-card__body"><p><?php echo Text::_('COM_DECAROFINANCE_OBLIGATIONS_DESC'); ?></p></div></section>
    <section class="xdecaro-card"><div class="xdecaro-card__header"><h2 class="xdecaro-card__title"><?php echo Text::_('COM_DECAROFINANCE_DEPOSITS'); ?></h2></div><div class="xdecaro-card__body"><p><?php echo Text::_('COM_DECAROFINANCE_DEPOSITS_DESC'); ?></p></div></section>
    <section class="xdecaro-card"><div class="xdecaro-card__header"><h2 class="xdecaro-card__title"><?php echo Text::_('COM_DECAROFINANCE_PAYMENTS'); ?></h2></div><div class="xdecaro-card__body"><p><?php echo Text::_('COM_DECAROFINANCE_PAYMENTS_DESC'); ?></p></div></section>
  </div>
</div>
