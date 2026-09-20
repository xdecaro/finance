<?php
defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Xdecaro\Component\Decarofinance\Administrator\Helper\UiHelper;

$methodLabel=static function(string $method):string {
    $key='COM_DECAROFINANCE_PAYMENT_METHOD_'.strtoupper($method);
    $label=Text::_($key);
    return $label===$key ? ucfirst(str_replace('_',' ',$method)) : $label;
};
?>
<div class="xdf-finance">
<section class="xdecaro-card xdf-form-card">
  <div class="xdecaro-card__header"><h2 class="xdecaro-card__title"><?php echo Text::_('COM_DECAROFINANCE_NEW_PAYMENT'); ?></h2></div>
  <div class="xdecaro-card__body">
    <form action="<?php echo Route::_('index.php?option=com_decarofinance&task=finance.recordPayment'); ?>" method="post" class="xdf-form-grid">
      <label><?php echo Text::_('COM_DECAROFINANCE_AMOUNT'); ?><input class="form-control" name="amount" required inputmode="decimal"></label>
      <label><?php echo Text::_('COM_DECAROFINANCE_CURRENCY'); ?><input class="form-control" name="currency" value="EUR" maxlength="3" required></label>
      <label><?php echo Text::_('COM_DECAROFINANCE_METHOD'); ?>
        <select class="form-select" name="method">
          <option value=""><?php echo Text::_('COM_DECAROFINANCE_NOT_SPECIFIED'); ?></option>
          <option value="bank_transfer"><?php echo Text::_('COM_DECAROFINANCE_PAYMENT_METHOD_BANK_TRANSFER'); ?></option>
          <option value="cash"><?php echo Text::_('COM_DECAROFINANCE_PAYMENT_METHOD_CASH'); ?></option>
          <option value="card"><?php echo Text::_('COM_DECAROFINANCE_PAYMENT_METHOD_CARD'); ?></option>
          <option value="direct_debit"><?php echo Text::_('COM_DECAROFINANCE_PAYMENT_METHOD_DIRECT_DEBIT'); ?></option>
          <option value="other"><?php echo Text::_('COM_DECAROFINANCE_PAYMENT_METHOD_OTHER'); ?></option>
        </select>
      </label>
      <label><?php echo Text::_('COM_DECAROFINANCE_REFERENCE'); ?><input class="form-control" name="reference" maxlength="191"></label>
      <label><?php echo Text::_('COM_DECAROFINANCE_PAYER'); ?>
        <select class="form-select" name="payer_ref">
          <option value=""><?php echo Text::_('COM_DECAROFINANCE_NO_PARTY'); ?></option>
          <optgroup label="<?php echo Text::_('COM_DECAROFINANCE_ORGANIZATIONS'); ?>"><?php foreach($this->organizations as $organization): ?><option value="organization:<?php echo htmlspecialchars((string)$organization['uuid'],ENT_QUOTES,'UTF-8'); ?>"><?php echo htmlspecialchars((string)$organization['name'],ENT_QUOTES,'UTF-8'); ?></option><?php endforeach; ?></optgroup>
          <optgroup label="<?php echo Text::_('COM_DECAROFINANCE_PEOPLE'); ?>"><?php foreach($this->people as $person): ?><option value="person:<?php echo htmlspecialchars((string)$person['uuid'],ENT_QUOTES,'UTF-8'); ?>"><?php echo htmlspecialchars((string)$person['display_name'],ENT_QUOTES,'UTF-8'); ?></option><?php endforeach; ?></optgroup>
        </select>
      </label>
      <label><?php echo Text::_('COM_DECAROFINANCE_OBLIGATION'); ?>
        <select class="form-select" name="obligation_id">
          <option value="0"><?php echo Text::_('COM_DECAROFINANCE_NO_OBLIGATION'); ?></option>
          <?php foreach($this->obligations as $obligation): $remaining=max(0,(float)$obligation['amount']-(float)$obligation['allocated_amount']); ?>
            <option value="<?php echo (int)$obligation['id']; ?>">#<?php echo (int)$obligation['id']; ?> · <?php echo htmlspecialchars((string)$obligation['kind'],ENT_QUOTES,'UTF-8'); ?> · <?php echo number_format($remaining,2,',','.').' '.htmlspecialchars((string)$obligation['currency'],ENT_QUOTES,'UTF-8'); ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <div class="xdf-span-2"><div class="form-text"><?php echo Text::_('COM_DECAROFINANCE_PAYMENT_ATOMIC_HELP'); ?></div></div>
      <div class="xdf-span-2"><button class="btn btn-primary" type="submit"><?php echo Text::_('JSAVE'); ?></button></div>
      <?php echo HTMLHelper::_('form.token'); ?>
    </form>
  </div>
</section>

<section class="xdecaro-card"><div class="xdecaro-card__body xdf-table-wrap">
<table class="table table-striped">
<thead><tr><th>ID</th><th><?php echo Text::_('COM_DECAROFINANCE_PAYER'); ?></th><th><?php echo Text::_('COM_DECAROFINANCE_AMOUNT'); ?></th><th><?php echo Text::_('COM_DECAROFINANCE_ALLOCATED'); ?></th><th><?php echo Text::_('COM_DECAROFINANCE_METHOD'); ?></th><th><?php echo Text::_('JDATE'); ?></th></tr></thead>
<tbody>
<?php foreach($this->items as $row): ?><tr>
<td><?php echo (int)$row['id']; ?></td>
<td><?php echo htmlspecialchars((string)($row['payer_label']??''),ENT_QUOTES,'UTF-8'); ?></td>
<td><?php echo number_format((float)$row['amount'],2,',','.').' '.htmlspecialchars((string)$row['currency'],ENT_QUOTES,'UTF-8'); ?></td>
<td><?php echo number_format((float)$row['allocated_amount'],2,',','.'); ?></td>
<td><?php echo htmlspecialchars($methodLabel((string)($row['method']??'')),ENT_QUOTES,'UTF-8'); ?></td>
<td><?php echo htmlspecialchars(UiHelper::formatDateTime((string)($row['paid_at']??'')),ENT_QUOTES,'UTF-8'); ?></td>
</tr><?php endforeach; ?>
</tbody>
</table>
</div></section>
</div>
