<?php
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
$s=$this->summary;
$currency=$this->currency;
$money=static fn($v)=>number_format((float)$v,2,',','.').' '.htmlspecialchars($currency,ENT_QUOTES,'UTF-8');
$directionLabel=static fn(string $v):string=>$v==='income'?Text::_('COM_DECAROFINANCE_INCOME'):($v==='expense'?Text::_('COM_DECAROFINANCE_EXPENSE'):$v);
$categoryLabel=static fn(string $v):string=>$v==='internal_transfer'?Text::_('COM_DECAROFINANCE_CATEGORY_INTERNAL_TRANSFER'):$v;
?>
<div class="xdf-finance">
  <form class="xdf-report-filter" method="get" action="<?php echo Route::_('index.php'); ?>">
    <input type="hidden" name="option" value="com_decarofinance"><input type="hidden" name="view" value="reports">
    <label><?php echo Text::_('COM_DECAROFINANCE_CURRENCY'); ?><input class="form-control" name="currency" value="<?php echo htmlspecialchars($this->currency,ENT_QUOTES,'UTF-8'); ?>" maxlength="3"></label>
    <button class="btn btn-primary" type="submit"><?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?></button>
  </form>

  <div class="xdf-grid xdf-kpis">
    <section class="xdecaro-card xdf-kpi"><span><?php echo Text::_('COM_DECAROFINANCE_INCOME_TOTAL'); ?></span><strong><?php echo $money($s['income_total']); ?></strong></section>
    <section class="xdecaro-card xdf-kpi"><span><?php echo Text::_('COM_DECAROFINANCE_EXPENSE_TOTAL'); ?></span><strong><?php echo $money($s['expense_total']); ?></strong></section>
    <section class="xdecaro-card xdf-kpi"><span><?php echo Text::_('COM_DECAROFINANCE_NET_TOTAL'); ?></span><strong><?php echo $money($s['net_total']); ?></strong></section>
    <section class="xdecaro-card xdf-kpi"><span><?php echo Text::_('COM_DECAROFINANCE_ACCOUNT_BALANCE'); ?></span><strong><?php echo $money($s['account_balance']); ?></strong></section>
    <section class="xdecaro-card xdf-kpi"><span><?php echo Text::_('COM_DECAROFINANCE_PENDING_ORDERS'); ?></span><strong><?php echo (int)$s['pending_orders']; ?></strong></section>
    <section class="xdecaro-card xdf-kpi"><span><?php echo Text::_('COM_DECAROFINANCE_COMMITTED_AMOUNT'); ?></span><strong><?php echo $money((float)$s['pending_orders_amount']+(float)$s['approved_orders_amount']); ?></strong></section>
    <section class="xdecaro-card xdf-kpi"><span><?php echo Text::_('COM_DECAROFINANCE_CASH_VARIANCES'); ?></span><strong><?php echo (int)$s['cash_check_variances']; ?></strong></section>
    <section class="xdecaro-card xdf-kpi"><span><?php echo Text::_('COM_DECAROFINANCE_CASH_VARIANCE_TOTAL'); ?></span><strong><?php echo $money($s['cash_variance_total']); ?></strong></section>
    <section class="xdecaro-card xdf-kpi"><span><?php echo Text::_('COM_DECAROFINANCE_DRAFT_STATEMENTS'); ?></span><strong><?php echo (int)$s['draft_statements']; ?></strong></section>
  </div>

  <section class="xdecaro-card">
    <div class="xdecaro-card__header"><h2 class="xdecaro-card__title"><?php echo Text::_('COM_DECAROFINANCE_BY_CATEGORY'); ?></h2></div>
    <div class="xdecaro-card__body xdf-table-wrap"><table class="table table-striped"><thead><tr><th><?php echo Text::_('COM_DECAROFINANCE_CATEGORY'); ?></th><th><?php echo Text::_('COM_DECAROFINANCE_DIRECTION'); ?></th><th><?php echo Text::_('COM_DECAROFINANCE_MOVEMENTS'); ?></th><th><?php echo Text::_('COM_DECAROFINANCE_AMOUNT'); ?></th></tr></thead><tbody>
    <?php foreach($this->categories as $row): ?><tr><td><?php echo htmlspecialchars($categoryLabel((string)$row['category']),ENT_QUOTES,'UTF-8'); ?></td><td><?php echo htmlspecialchars($directionLabel((string)$row['direction']),ENT_QUOTES,'UTF-8'); ?></td><td><?php echo (int)$row['movements']; ?></td><td><?php echo $money($row['amount']); ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
  </section>

  <section class="xdecaro-card">
    <div class="xdecaro-card__header"><h2 class="xdecaro-card__title"><?php echo Text::_('COM_DECAROFINANCE_BUDGET_USAGE'); ?></h2></div>
    <div class="xdecaro-card__body xdf-table-wrap"><table class="table table-striped"><thead><tr><th><?php echo Text::_('COM_DECAROFINANCE_BUDGET'); ?></th><th><?php echo Text::_('COM_DECAROFINANCE_BUDGET_LINE'); ?></th><th><?php echo Text::_('COM_DECAROFINANCE_COST_CENTER'); ?></th><th><?php echo Text::_('COM_DECAROFINANCE_PLANNED_AMOUNT'); ?></th><th><?php echo Text::_('COM_DECAROFINANCE_REALIZED'); ?></th><th><?php echo Text::_('COM_DECAROFINANCE_COMMITTED'); ?></th><th><?php echo Text::_('COM_DECAROFINANCE_AVAILABLE'); ?></th></tr></thead><tbody>
    <?php foreach($this->budgetUsage as $row): ?><tr><td><?php echo htmlspecialchars((string)$row['budget_title'],ENT_QUOTES,'UTF-8'); ?></td><td><?php echo htmlspecialchars((string)$row['title'],ENT_QUOTES,'UTF-8'); ?></td><td><?php echo htmlspecialchars((string)($row['cost_center_title']??''),ENT_QUOTES,'UTF-8'); ?></td><td><?php echo $money($row['planned_amount']); ?></td><td><?php echo $money($row['realized_amount']); ?></td><td><?php echo $money($row['committed_amount']); ?></td><td><?php echo $money($row['available_amount']); ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
  </section>
</div>
