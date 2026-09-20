<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Xdecaro\Component\Decarofinance\Administrator\Helper\UiHelper;

$directionLabel=static function(string $direction):string {
    return $direction==='income' ? Text::_('COM_DECAROFINANCE_INCOME') : Text::_('COM_DECAROFINANCE_EXPENSE');
};
$categoryLabel=static function(string $category):string {
    if ($category==='internal_transfer') { return Text::_('COM_DECAROFINANCE_CATEGORY_INTERNAL_TRANSFER'); }
    return $category;
};
?>
<div class="xdf-finance">
  <section class="xdecaro-card xdf-form-card">
    <div class="xdecaro-card__header"><h2 class="xdecaro-card__title"><?php echo Text::_('COM_DECAROFINANCE_NEW_TRANSACTION'); ?></h2></div>
    <div class="xdecaro-card__body">
      <form action="<?php echo Route::_('index.php?option=com_decarofinance&task=finance.recordTransaction'); ?>" method="post" class="xdf-form-grid">
        <label><?php echo Text::_('COM_DECAROFINANCE_DIRECTION'); ?><select class="form-select" name="direction"><option value="income"><?php echo Text::_('COM_DECAROFINANCE_INCOME'); ?></option><option value="expense"><?php echo Text::_('COM_DECAROFINANCE_EXPENSE'); ?></option></select></label>
        <label><?php echo Text::_('COM_DECAROFINANCE_ACCOUNT'); ?><select class="form-select" name="account_id"><option value="0">—</option><?php foreach($this->accounts as $a): ?><option value="<?php echo (int)$a['id']; ?>"><?php echo htmlspecialchars((string)$a['name'].' · '.(string)$a['currency'],ENT_QUOTES,'UTF-8'); ?></option><?php endforeach; ?></select></label>
        <label><?php echo Text::_('COM_DECAROFINANCE_BUDGET_LINE'); ?><select class="form-select" name="budget_line_id"><option value="0">—</option><?php foreach($this->budgetLines as $l): ?><option value="<?php echo (int)$l['id']; ?>"><?php echo htmlspecialchars((string)$l['budget_title'].' · '.(string)$l['title'].' · '.(string)$l['currency'],ENT_QUOTES,'UTF-8'); ?></option><?php endforeach; ?></select></label>
        <label><?php echo Text::_('COM_DECAROFINANCE_COST_CENTER'); ?><select class="form-select" name="cost_center_id"><option value="0"><?php echo Text::_('COM_DECAROFINANCE_NO_COST_CENTER'); ?></option><?php foreach($this->costCenters as $center): ?><option value="<?php echo (int)$center['id']; ?>"><?php echo htmlspecialchars((string)$center['title'],ENT_QUOTES,'UTF-8'); ?></option><?php endforeach; ?></select></label>
        <label><?php echo Text::_('COM_DECAROFINANCE_CATEGORY'); ?><input class="form-control" name="category" maxlength="100"></label>
        <label><?php echo Text::_('COM_DECAROFINANCE_AMOUNT'); ?><input class="form-control" name="amount" required inputmode="decimal"></label>
        <label><?php echo Text::_('COM_DECAROFINANCE_CURRENCY'); ?><input class="form-control" name="currency" value="EUR" maxlength="3" required></label>
        <label><?php echo Text::_('JDATE'); ?><input class="form-control" type="datetime-local" name="occurred_at"></label>
        <label class="xdf-span-2"><?php echo Text::_('COM_DECAROFINANCE_COUNTERPARTY'); ?>
          <select class="form-select" name="counterparty_ref">
            <option value=""><?php echo Text::_('COM_DECAROFINANCE_NO_PARTY'); ?></option>
            <optgroup label="<?php echo Text::_('COM_DECAROFINANCE_ORGANIZATIONS'); ?>"><?php foreach($this->organizations as $organization): ?><option value="organization:<?php echo htmlspecialchars((string)$organization['uuid'],ENT_QUOTES,'UTF-8'); ?>"><?php echo htmlspecialchars((string)$organization['name'],ENT_QUOTES,'UTF-8'); ?></option><?php endforeach; ?></optgroup>
            <optgroup label="<?php echo Text::_('COM_DECAROFINANCE_PEOPLE'); ?>"><?php foreach($this->people as $person): ?><option value="person:<?php echo htmlspecialchars((string)$person['uuid'],ENT_QUOTES,'UTF-8'); ?>"><?php echo htmlspecialchars((string)$person['display_name'],ENT_QUOTES,'UTF-8'); ?></option><?php endforeach; ?></optgroup>
          </select>
        </label>
        <label class="xdf-span-2"><?php echo Text::_('COM_DECAROFINANCE_DESCRIPTION'); ?><textarea class="form-control" name="description" rows="2" maxlength="500"></textarea></label>
        <div class="xdf-span-2"><div class="form-text"><?php echo Text::_('COM_DECAROFINANCE_SOURCE_AUTOMATIC_HELP'); ?></div></div>
        <div class="xdf-span-2"><button class="btn btn-primary" type="submit"><?php echo Text::_('JSAVE'); ?></button></div>
        <?php echo HTMLHelper::_('form.token'); ?>
      </form>
    </div>
  </section>

  <section class="xdecaro-card">
    <div class="xdecaro-card__body xdf-table-wrap">
      <table class="table table-striped">
        <thead><tr><th>ID</th><th><?php echo Text::_('JDATE'); ?></th><th><?php echo Text::_('COM_DECAROFINANCE_DIRECTION'); ?></th><th><?php echo Text::_('COM_DECAROFINANCE_ACCOUNT'); ?></th><th><?php echo Text::_('COM_DECAROFINANCE_COST_CENTER'); ?></th><th><?php echo Text::_('COM_DECAROFINANCE_CATEGORY'); ?></th><th><?php echo Text::_('COM_DECAROFINANCE_AMOUNT'); ?></th><th><?php echo Text::_('COM_DECAROFINANCE_DESCRIPTION'); ?></th></tr></thead>
        <tbody>
        <?php foreach($this->items as $row): ?>
          <tr>
            <td><?php echo (int)$row['id']; ?></td>
            <td><?php echo htmlspecialchars(UiHelper::formatDateTime((string)$row['occurred_at']),ENT_QUOTES,'UTF-8'); ?></td>
            <td><span class="badge bg-secondary"><?php echo htmlspecialchars($directionLabel((string)$row['direction']),ENT_QUOTES,'UTF-8'); ?></span></td>
            <td><?php echo htmlspecialchars((string)($row['account_name']??''),ENT_QUOTES,'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars((string)($row['cost_center_title']??''),ENT_QUOTES,'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($categoryLabel((string)($row['category']??'')),ENT_QUOTES,'UTF-8'); ?></td>
            <td><?php echo number_format((float)$row['amount'],2,',','.').' '.htmlspecialchars((string)$row['currency'],ENT_QUOTES,'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars((string)($row['description']??''),ENT_QUOTES,'UTF-8'); ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
</div>
