<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
?>
<div class="xdf-finance">
  <section class="xdecaro-card xdf-form-card">
    <div class="xdecaro-card__header"><h2 class="xdecaro-card__title"><?php echo Text::_('COM_DECAROFINANCE_NEW_ACCOUNT'); ?></h2></div>
    <div class="xdecaro-card__body">
      <form action="<?php echo Route::_('index.php?option=com_decarofinance&task=finance.createAccount'); ?>" method="post" class="xdf-form-grid">
        <label><?php echo Text::_('COM_DECAROFINANCE_ACCOUNT_CODE'); ?><input class="form-control" name="code" maxlength="64"></label>
        <label><?php echo Text::_('COM_DECAROFINANCE_ACCOUNT_NAME'); ?><input class="form-control" name="name" required maxlength="255"></label>
        <label><?php echo Text::_('COM_DECAROFINANCE_ACCOUNT_TYPE'); ?>
          <select class="form-select" name="account_type">
            <option value="bank"><?php echo Text::_('COM_DECAROFINANCE_ACCOUNT_TYPE_BANK'); ?></option>
            <option value="cash"><?php echo Text::_('COM_DECAROFINANCE_ACCOUNT_TYPE_CASH'); ?></option>
            <option value="payment"><?php echo Text::_('COM_DECAROFINANCE_ACCOUNT_TYPE_PAYMENT'); ?></option>
            <option value="other"><?php echo Text::_('COM_DECAROFINANCE_ACCOUNT_TYPE_OTHER'); ?></option>
          </select>
        </label>
        <label><?php echo Text::_('COM_DECAROFINANCE_ACCOUNT_IDENTIFIER'); ?><input class="form-control" name="identifier" maxlength="191"></label>
        <label><?php echo Text::_('COM_DECAROFINANCE_CURRENCY'); ?><input class="form-control" name="currency" value="EUR" maxlength="3" required></label>
        <label><?php echo Text::_('COM_DECAROFINANCE_OPENING_BALANCE'); ?><input class="form-control" name="opening_balance" value="0.00" inputmode="decimal" required></label>
        <label><?php echo Text::_('COM_DECAROFINANCE_EXTERNAL_KEY'); ?><input class="form-control" name="external_key" maxlength="191"></label>
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

  <section class="xdecaro-card">
    <div class="xdecaro-card__body xdf-table-wrap">
      <table class="table table-striped">
        <thead><tr><th>ID</th><th><?php echo Text::_('COM_DECAROFINANCE_ACCOUNT_CODE'); ?></th><th><?php echo Text::_('COM_DECAROFINANCE_ACCOUNT_NAME'); ?></th><th><?php echo Text::_('COM_DECAROFINANCE_ACCOUNT_TYPE'); ?></th><th><?php echo Text::_('COM_DECAROFINANCE_OWNER'); ?></th><th><?php echo Text::_('COM_DECAROFINANCE_CURRENCY'); ?></th><th><?php echo Text::_('COM_DECAROFINANCE_BALANCE'); ?></th></tr></thead>
        <tbody>
        <?php foreach ($this->items as $row): $owner=array_filter([$row['owner_component'],$row['owner_entity'],$row['owner_id']]); ?>
          <tr>
            <td><?php echo (int)$row['id']; ?></td>
            <td><?php echo htmlspecialchars((string)($row['code']??''),ENT_QUOTES,'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars((string)$row['name'],ENT_QUOTES,'UTF-8'); ?><div class="xdf-muted"><?php echo htmlspecialchars((string)($row['identifier']??''),ENT_QUOTES,'UTF-8'); ?></div></td>
            <td><?php echo htmlspecialchars((string)$row['account_type'],ENT_QUOTES,'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars(implode(':',$owner),ENT_QUOTES,'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars((string)$row['currency'],ENT_QUOTES,'UTF-8'); ?></td>
            <td><?php echo number_format((float)$row['balance'],2,',','.'); ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
</div>
