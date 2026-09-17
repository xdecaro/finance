<?php
defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
?>
<div class="xdf-finance">
  <div class="xdf-grid">
    <section class="xdecaro-card xdf-form-card">
      <div class="xdecaro-card__header"><h2 class="xdecaro-card__title"><?php echo Text::_('COM_DECAROFINANCE_NEW_BUDGET'); ?></h2></div>
      <div class="xdecaro-card__body">
        <form action="<?php echo Route::_('index.php?option=com_decarofinance&task=finance.createBudget'); ?>" method="post" class="xdf-form-grid">
          <label class="xdf-span-2"><?php echo Text::_('JGLOBAL_TITLE'); ?><input class="form-control" name="title" required maxlength="255"></label>
          <label><?php echo Text::_('COM_DECAROFINANCE_PERIOD_START'); ?><input class="form-control" type="date" name="period_start"></label>
          <label><?php echo Text::_('COM_DECAROFINANCE_PERIOD_END'); ?><input class="form-control" type="date" name="period_end"></label>
          <label><?php echo Text::_('COM_DECAROFINANCE_CURRENCY'); ?><input class="form-control" name="currency" value="EUR" maxlength="3" required></label>
          <details class="xdf-span-2 xdf-advanced"><summary><?php echo Text::_('COM_DECAROFINANCE_OWNER_REFERENCE'); ?></summary><div class="xdf-form-grid">
            <label><?php echo Text::_('COM_DECAROFINANCE_OWNER_COMPONENT'); ?><input class="form-control" name="owner_component" placeholder="com_xxx"></label>
            <label><?php echo Text::_('COM_DECAROFINANCE_OWNER_ENTITY'); ?><input class="form-control" name="owner_entity" placeholder="organization"></label>
            <label><?php echo Text::_('COM_DECAROFINANCE_OWNER_ID'); ?><input class="form-control" name="owner_id"></label>
          </div></details>
          <div class="xdf-span-2"><button class="btn btn-primary" type="submit"><?php echo Text::_('JSAVE'); ?></button></div>
          <?php echo HTMLHelper::_('form.token'); ?>
        </form>
      </div>
    </section>

    <section class="xdecaro-card xdf-form-card">
      <div class="xdecaro-card__header"><h2 class="xdecaro-card__title"><?php echo Text::_('COM_DECAROFINANCE_NEW_BUDGET_LINE'); ?></h2></div>
      <div class="xdecaro-card__body">
        <form action="<?php echo Route::_('index.php?option=com_decarofinance&task=finance.addBudgetLine'); ?>" method="post" class="xdf-form-grid">
          <label class="xdf-span-2"><?php echo Text::_('COM_DECAROFINANCE_BUDGET'); ?><select class="form-select" name="budget_id" required><option value="">—</option><?php foreach($this->items as $budget): ?><option value="<?php echo (int)$budget['id']; ?>"><?php echo htmlspecialchars((string)$budget['title'].' · '.(string)$budget['currency'],ENT_QUOTES,'UTF-8'); ?></option><?php endforeach; ?></select></label>
          <label><?php echo Text::_('COM_DECAROFINANCE_KIND'); ?><select class="form-select" name="kind"><option value="income"><?php echo Text::_('COM_DECAROFINANCE_INCOME'); ?></option><option value="expense"><?php echo Text::_('COM_DECAROFINANCE_EXPENSE'); ?></option></select></label>
          <label><?php echo Text::_('COM_DECAROFINANCE_CODE'); ?><input class="form-control" name="code" maxlength="64"></label>
          <label><?php echo Text::_('COM_DECAROFINANCE_CATEGORY'); ?><input class="form-control" name="category" maxlength="100"></label>
          <label><?php echo Text::_('JGLOBAL_TITLE'); ?><input class="form-control" name="title" required maxlength="255"></label>
          <label><?php echo Text::_('COM_DECAROFINANCE_PLANNED_AMOUNT'); ?><input class="form-control" name="planned_amount" required inputmode="decimal"></label>
          <div class="xdf-span-2"><button class="btn btn-primary" type="submit"><?php echo Text::_('JSAVE'); ?></button></div>
          <?php echo HTMLHelper::_('form.token'); ?>
        </form>
      </div>
    </section>
  </div>

  <section class="xdecaro-card">
    <div class="xdecaro-card__body xdf-table-wrap">
      <table class="table table-striped">
        <thead><tr><th>ID</th><th><?php echo Text::_('JGLOBAL_TITLE'); ?></th><th><?php echo Text::_('COM_DECAROFINANCE_OWNER'); ?></th><th><?php echo Text::_('COM_DECAROFINANCE_PERIOD'); ?></th><th><?php echo Text::_('COM_DECAROFINANCE_PLANNED_INCOME'); ?></th><th><?php echo Text::_('COM_DECAROFINANCE_PLANNED_EXPENSE'); ?></th></tr></thead>
        <tbody>
        <?php foreach($this->items as $row): $owner=array_filter([$row['owner_component'],$row['owner_entity'],$row['owner_id']]); ?>
          <tr>
            <td><?php echo (int)$row['id']; ?></td>
            <td><?php echo htmlspecialchars((string)$row['title'],ENT_QUOTES,'UTF-8'); ?><div class="xdf-muted"><?php echo htmlspecialchars((string)$row['currency'],ENT_QUOTES,'UTF-8'); ?></div></td>
            <td><?php echo htmlspecialchars(implode(':',$owner),ENT_QUOTES,'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars((string)($row['period_start']??''),ENT_QUOTES,'UTF-8').' → '.htmlspecialchars((string)($row['period_end']??''),ENT_QUOTES,'UTF-8'); ?></td>
            <td><?php echo number_format((float)$row['planned_income'],2,',','.'); ?></td>
            <td><?php echo number_format((float)$row['planned_expense'],2,',','.'); ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
</div>
