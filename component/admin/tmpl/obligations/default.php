<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$statusLabel=static function(string $status):string {
    $key='COM_DECAROFINANCE_OBLIGATION_STATUS_'.strtoupper($status);
    $label=Text::_($key);
    return $label===$key ? $status : $label;
};
$kindLabel=static function(string $kind):string {
    $key='COM_DECAROFINANCE_KIND_'.strtoupper($kind);
    $label=Text::_($key);
    return $label===$key ? ucfirst(str_replace('_',' ',$kind)) : $label;
};
?>
<div class="xdf-finance">
<section class="xdecaro-card xdf-form-card">
  <div class="xdecaro-card__header"><h2 class="xdecaro-card__title"><?php echo Text::_('COM_DECAROFINANCE_NEW_OBLIGATION'); ?></h2></div>
  <div class="xdecaro-card__body">
    <form action="<?php echo Route::_('index.php?option=com_decarofinance&task=finance.createObligation'); ?>" method="post" class="xdf-form-grid">
      <label><?php echo Text::_('COM_DECAROFINANCE_KIND'); ?><input class="form-control" name="kind" required maxlength="64" placeholder="<?php echo Text::_('COM_DECAROFINANCE_KIND_PLACEHOLDER'); ?>"></label>
      <label><?php echo Text::_('COM_DECAROFINANCE_AMOUNT'); ?><input class="form-control" name="amount" inputmode="decimal" required></label>
      <label><?php echo Text::_('COM_DECAROFINANCE_CURRENCY'); ?><input class="form-control" name="currency" value="EUR" maxlength="3" required></label>
      <label><?php echo Text::_('COM_DECAROFINANCE_DUE_DATE'); ?><input class="form-control" type="date" name="due_date"></label>
      <label class="xdf-span-2"><?php echo Text::_('COM_DECAROFINANCE_DEBTOR'); ?>
        <select class="form-select" name="debtor_ref">
          <option value=""><?php echo Text::_('COM_DECAROFINANCE_NO_PARTY'); ?></option>
          <optgroup label="<?php echo Text::_('COM_DECAROFINANCE_ORGANIZATIONS'); ?>">
            <?php foreach($this->organizations as $organization): ?><option value="organization:<?php echo htmlspecialchars((string)$organization['uuid'],ENT_QUOTES,'UTF-8'); ?>"><?php echo htmlspecialchars((string)$organization['name'],ENT_QUOTES,'UTF-8'); ?></option><?php endforeach; ?>
          </optgroup>
          <optgroup label="<?php echo Text::_('COM_DECAROFINANCE_PEOPLE'); ?>">
            <?php foreach($this->people as $person): ?><option value="person:<?php echo htmlspecialchars((string)$person['uuid'],ENT_QUOTES,'UTF-8'); ?>"><?php echo htmlspecialchars((string)$person['display_name'],ENT_QUOTES,'UTF-8'); ?></option><?php endforeach; ?>
          </optgroup>
        </select>
      </label>
      <label class="xdf-span-2"><?php echo Text::_('COM_DECAROFINANCE_DESCRIPTION'); ?><textarea class="form-control" name="description" rows="2" maxlength="500"></textarea></label>
      <div class="xdf-span-2"><div class="form-text"><?php echo Text::_('COM_DECAROFINANCE_EXTERNAL_KEY_AUTOMATIC_HELP'); ?></div></div>
      <div class="xdf-span-2"><button class="btn btn-primary" type="submit"><?php echo Text::_('JSAVE'); ?></button></div>
      <?php echo HTMLHelper::_('form.token'); ?>
    </form>
  </div>
</section>

<section class="xdecaro-card">
  <div class="xdecaro-card__body xdf-table-wrap">
    <table class="table table-striped">
      <thead><tr><th>ID</th><th><?php echo Text::_('COM_DECAROFINANCE_KIND'); ?></th><th><?php echo Text::_('COM_DECAROFINANCE_DEBTOR'); ?></th><th><?php echo Text::_('COM_DECAROFINANCE_AMOUNT'); ?></th><th><?php echo Text::_('COM_DECAROFINANCE_PAID'); ?></th><th><?php echo Text::_('JSTATUS'); ?></th><th><?php echo Text::_('COM_DECAROFINANCE_DUE_DATE'); ?></th></tr></thead>
      <tbody>
      <?php foreach($this->items as $row): ?><tr>
        <td><?php echo (int)$row['id']; ?></td>
        <td><?php echo htmlspecialchars($kindLabel((string)$row['kind']),ENT_QUOTES,'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars((string)($row['debtor_label']??''),ENT_QUOTES,'UTF-8'); ?></td>
        <td><?php echo number_format((float)$row['amount'],2,',','.').' '.htmlspecialchars((string)$row['currency'],ENT_QUOTES,'UTF-8'); ?></td>
        <td><?php echo number_format((float)$row['allocated_amount'],2,',','.'); ?></td>
        <td><span class="badge bg-secondary"><?php echo htmlspecialchars($statusLabel((string)$row['status']),ENT_QUOTES,'UTF-8'); ?></span></td>
        <td><?php echo htmlspecialchars((string)($row['due_date']??''),ENT_QUOTES,'UTF-8'); ?></td>
      </tr><?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
</div>
