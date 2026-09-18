<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
?>
<div class="xdf-finance">
  <section class="xdecaro-card xdf-form-card">
    <div class="xdecaro-card__header"><h2 class="xdecaro-card__title"><?php echo Text::_('COM_DECAROFINANCE_NEW_TRANSFER'); ?></h2></div>
    <div class="xdecaro-card__body">
      <form action="<?php echo Route::_('index.php?option=com_decarofinance&task=finance.transfer'); ?>" method="post" class="xdf-form-grid">
        <label><?php echo Text::_('COM_DECAROFINANCE_FROM_ACCOUNT'); ?>
          <select class="form-select" name="from_account_id" required>
            <option value="">—</option>
            <?php foreach($this->accounts as $a): if((int)($a['state']??0)!==1) continue; ?>
              <option value="<?php echo (int)$a['id']; ?>"><?php echo htmlspecialchars((string)$a['name'].' · '.(string)$a['currency'],ENT_QUOTES,'UTF-8'); ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label><?php echo Text::_('COM_DECAROFINANCE_TO_ACCOUNT'); ?>
          <select class="form-select" name="to_account_id" required>
            <option value="">—</option>
            <?php foreach($this->accounts as $a): if((int)($a['state']??0)!==1) continue; ?>
              <option value="<?php echo (int)$a['id']; ?>"><?php echo htmlspecialchars((string)$a['name'].' · '.(string)$a['currency'],ENT_QUOTES,'UTF-8'); ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label><?php echo Text::_('COM_DECAROFINANCE_AMOUNT'); ?><input class="form-control" name="amount" required inputmode="decimal"></label>
        <label><?php echo Text::_('COM_DECAROFINANCE_CURRENCY'); ?><input class="form-control" name="currency" value="EUR" maxlength="3" required></label>
        <label><?php echo Text::_('JDATE'); ?><input class="form-control" type="datetime-local" name="occurred_at"></label>
        <label><?php echo Text::_('COM_DECAROFINANCE_EXTERNAL_KEY'); ?><input class="form-control" name="external_key" maxlength="191"></label>
        <label class="xdf-span-2"><?php echo Text::_('COM_DECAROFINANCE_DESCRIPTION'); ?><textarea class="form-control" name="description" rows="2" maxlength="500"></textarea></label>
        <details class="xdf-span-2 xdf-advanced"><summary><?php echo Text::_('COM_DECAROFINANCE_SOURCE_REFERENCE'); ?></summary><div class="xdf-form-grid">
          <label><?php echo Text::_('COM_DECAROFINANCE_SOURCE_COMPONENT'); ?><input class="form-control" name="source_component" placeholder="com_xxx"></label>
          <label><?php echo Text::_('COM_DECAROFINANCE_SOURCE_ENTITY'); ?><input class="form-control" name="source_entity"></label>
          <label><?php echo Text::_('COM_DECAROFINANCE_SOURCE_ID'); ?><input class="form-control" name="source_id"></label>
        </div></details>
        <div class="xdf-span-2"><button class="btn btn-primary" type="submit"><?php echo Text::_('COM_DECAROFINANCE_TRANSFER'); ?></button></div>
        <?php echo HTMLHelper::_('form.token'); ?>
      </form>
    </div>
  </section>

  <section class="xdecaro-card">
    <div class="xdecaro-card__body xdf-table-wrap">
      <table class="table table-striped">
        <thead><tr><th>ID</th><th><?php echo Text::_('JDATE'); ?></th><th><?php echo Text::_('COM_DECAROFINANCE_FROM_ACCOUNT'); ?></th><th><?php echo Text::_('COM_DECAROFINANCE_TO_ACCOUNT'); ?></th><th><?php echo Text::_('COM_DECAROFINANCE_AMOUNT'); ?></th><th><?php echo Text::_('COM_DECAROFINANCE_DESCRIPTION'); ?></th></tr></thead>
        <tbody>
        <?php foreach($this->items as $row): ?>
          <tr>
            <td><?php echo (int)$row['id']; ?></td>
            <td><?php echo htmlspecialchars((string)$row['occurred_at'],ENT_QUOTES,'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars((string)($row['from_account_name']??''),ENT_QUOTES,'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars((string)($row['to_account_name']??''),ENT_QUOTES,'UTF-8'); ?></td>
            <td><?php echo number_format((float)$row['amount'],2,',','.').' '.htmlspecialchars((string)$row['currency'],ENT_QUOTES,'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars((string)($row['description']??''),ENT_QUOTES,'UTF-8'); ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
</div>
