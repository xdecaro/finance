<?php
defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
?>
<div class="xdf-finance">
<div class="alert alert-info"><?php echo Text::_('COM_DECAROFINANCE_DEPOSIT_OWNER_HELP'); ?></div>
<section class="xdecaro-card xdf-form-card">
<div class="xdecaro-card__header"><h2 class="xdecaro-card__title"><?php echo Text::_('COM_DECAROFINANCE_NEW_DEPOSIT_MOVEMENT'); ?></h2></div>
<div class="xdecaro-card__body">
<form action="<?php echo Route::_('index.php?option=com_decarofinance&task=finance.depositMovement'); ?>" method="post" class="xdf-form-grid">
<label class="xdf-span-2"><?php echo Text::_('COM_DECAROFINANCE_OWNER'); ?>
<select class="form-select" name="owner_ref">
<option value=""><?php echo Text::_('JSELECT'); ?></option>
<optgroup label="<?php echo Text::_('COM_DECAROFINANCE_ORGANIZATIONS'); ?>"><?php foreach($this->organizations as $organization): ?><option value="organization:<?php echo htmlspecialchars((string)$organization['uuid'],ENT_QUOTES,'UTF-8'); ?>"><?php echo htmlspecialchars((string)$organization['name'],ENT_QUOTES,'UTF-8'); ?></option><?php endforeach; ?></optgroup>
<optgroup label="<?php echo Text::_('COM_DECAROFINANCE_PEOPLE'); ?>"><?php foreach($this->people as $person): ?><option value="person:<?php echo htmlspecialchars((string)$person['uuid'],ENT_QUOTES,'UTF-8'); ?>"><?php echo htmlspecialchars((string)$person['display_name'],ENT_QUOTES,'UTF-8'); ?></option><?php endforeach; ?></optgroup>
</select>
</label>
<details class="xdf-span-2 xdf-advanced">
<summary><?php echo Text::_('COM_DECAROFINANCE_ADVANCED_OWNER_REFERENCE'); ?></summary>
<div class="xdf-form-grid">
<label><?php echo Text::_('COM_DECAROFINANCE_OWNER_COMPONENT'); ?><input class="form-control" name="owner_component" placeholder="com_xxx"></label>
<label><?php echo Text::_('COM_DECAROFINANCE_OWNER_ENTITY'); ?><input class="form-control" name="owner_entity" placeholder="team / organization / person"></label>
<label class="xdf-span-2"><?php echo Text::_('COM_DECAROFINANCE_OWNER_ID'); ?><input class="form-control" name="owner_id"></label>
</div>
<div class="form-text"><?php echo Text::_('COM_DECAROFINANCE_ADVANCED_OWNER_HELP'); ?></div>
</details>
<label><?php echo Text::_('COM_DECAROFINANCE_CURRENCY'); ?><input class="form-control" name="currency" value="EUR" required maxlength="3"></label>
<label><?php echo Text::_('COM_DECAROFINANCE_MOVEMENT_TYPE'); ?>
<select class="form-select" name="movement_type">
<option value="credit"><?php echo Text::_('COM_DECAROFINANCE_DEPOSIT_TYPE_CREDIT'); ?></option>
<option value="debit"><?php echo Text::_('COM_DECAROFINANCE_DEPOSIT_TYPE_DEBIT'); ?></option>
<option value="refund"><?php echo Text::_('COM_DECAROFINANCE_DEPOSIT_TYPE_REFUND'); ?></option>
<option value="adjustment"><?php echo Text::_('COM_DECAROFINANCE_DEPOSIT_TYPE_ADJUSTMENT'); ?></option>
<option value="reversal"><?php echo Text::_('COM_DECAROFINANCE_DEPOSIT_TYPE_REVERSAL'); ?></option>
</select>
</label>
<label><?php echo Text::_('COM_DECAROFINANCE_AMOUNT_SIGNED'); ?><input class="form-control" name="amount" required inputmode="decimal"></label>
<label class="xdf-span-2"><?php echo Text::_('COM_DECAROFINANCE_DESCRIPTION'); ?><textarea class="form-control" name="description" rows="2"></textarea></label>
<div class="xdf-span-2"><div class="form-text"><?php echo Text::_('COM_DECAROFINANCE_EXTERNAL_KEY_AUTOMATIC_HELP'); ?></div></div>
<div class="xdf-span-2"><button class="btn btn-primary" type="submit"><?php echo Text::_('JSAVE'); ?></button></div>
<?php echo HTMLHelper::_('form.token'); ?>
</form>
</div></section>
<section class="xdecaro-card"><div class="xdecaro-card__body xdf-table-wrap"><table class="table table-striped">
<thead><tr><th>ID</th><th><?php echo Text::_('COM_DECAROFINANCE_OWNER'); ?></th><th><?php echo Text::_('COM_DECAROFINANCE_CURRENCY'); ?></th><th><?php echo Text::_('COM_DECAROFINANCE_BALANCE'); ?></th></tr></thead>
<tbody><?php foreach($this->items as $row): ?><tr><td><?php echo (int)$row['id']; ?></td><td><?php echo htmlspecialchars((string)($row['owner_label']??''),ENT_QUOTES,'UTF-8'); ?></td><td><?php echo htmlspecialchars((string)$row['currency'],ENT_QUOTES,'UTF-8'); ?></td><td><?php echo number_format((float)$row['balance'],2,',','.'); ?></td></tr><?php endforeach; ?></tbody>
</table></div></section>
</div>
