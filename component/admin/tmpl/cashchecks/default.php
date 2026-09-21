<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Xdecaro\Component\Decarofinance\Administrator\Helper\UiHelper;
?>
<div class="xdf-finance">
  <?php if($this->canReconcile): ?>
  <section class="xdecaro-card xdf-form-card">
    <div class="xdecaro-card__header"><h2 class="xdecaro-card__title"><?php echo Text::_('COM_DECAROFINANCE_NEW_CASH_CHECK'); ?></h2></div>
    <div class="xdecaro-card__body">
      <div class="alert alert-info"><?php echo Text::_('COM_DECAROFINANCE_CASH_CHECK_NOTE'); ?></div>
      <?php if(!$this->cashAccounts): ?>
        <div class="alert alert-info"><?php echo Text::_('COM_DECAROFINANCE_NO_CASH_ACCOUNTS'); ?></div>
      <?php else: ?>
      <form action="<?php echo Route::_('index.php?option=com_decarofinance&task=finance.cashCheck'); ?>" method="post" class="xdf-form-grid">
        <label><?php echo Text::_('COM_DECAROFINANCE_CASH_ACCOUNT'); ?>
          <select class="form-select" name="account_id" required><option value="">—</option>
          <?php foreach($this->cashAccounts as $a): ?><option value="<?php echo (int)$a['id']; ?>"><?php echo htmlspecialchars((string)$a['name'].' · '.number_format((float)$a['balance'],2,',','.').' '.(string)$a['currency'],ENT_QUOTES,'UTF-8'); ?></option><?php endforeach; ?>
          </select>
        </label>
        <label><?php echo Text::_('COM_DECAROFINANCE_CHECKED_AT'); ?><input class="form-control" type="datetime-local" name="checked_at"></label>
        <label><?php echo Text::_('COM_DECAROFINANCE_ACTUAL_BALANCE'); ?><input class="form-control" name="actual_balance" required inputmode="decimal"></label>
        <label class="xdf-span-2"><?php echo Text::_('COM_DECAROFINANCE_NOTE'); ?><textarea class="form-control" name="note" rows="2" maxlength="1000"></textarea></label>
        <div class="xdf-span-2"><div class="form-text"><?php echo Text::_('COM_DECAROFINANCE_DOCUMENT_LINK_AUTOMATIC_HELP'); ?></div></div>
        <div class="xdf-span-2"><button class="btn btn-primary" type="submit"><?php echo Text::_('COM_DECAROFINANCE_REGISTER_CHECK'); ?></button></div>
        <?php echo HTMLHelper::_('form.token'); ?>
      </form>
      <?php endif; ?>
    </div>
  </section>
  <?php endif; ?>

  <section class="xdecaro-card">
    <div class="xdecaro-card__body xdf-table-wrap">
      <table class="table table-striped">
        <thead><tr><th>ID</th><th><?php echo Text::_('COM_DECAROFINANCE_CASH_ACCOUNT'); ?></th><th><?php echo Text::_('COM_DECAROFINANCE_CHECKED_AT'); ?></th><th><?php echo Text::_('COM_DECAROFINANCE_EXPECTED_BALANCE'); ?></th><th><?php echo Text::_('COM_DECAROFINANCE_ACTUAL_BALANCE'); ?></th><th><?php echo Text::_('COM_DECAROFINANCE_DIFFERENCE'); ?></th><th><?php echo Text::_('COM_DECAROFINANCE_NOTE'); ?></th></tr></thead>
        <tbody>
        <?php foreach($this->items as $row): $diff=(float)$row['difference']; ?>
          <tr>
            <td><?php echo (int)$row['id']; ?></td>
            <td><?php echo htmlspecialchars((string)$row['account_name'],ENT_QUOTES,'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars(UiHelper::formatDateTime((string)$row['checked_at']),ENT_QUOTES,'UTF-8'); ?></td>
            <td><?php echo number_format((float)$row['expected_balance'],2,',','.').' '.htmlspecialchars((string)$row['currency'],ENT_QUOTES,'UTF-8'); ?></td>
            <td><?php echo number_format((float)$row['actual_balance'],2,',','.').' '.htmlspecialchars((string)$row['currency'],ENT_QUOTES,'UTF-8'); ?></td>
            <td><span class="badge <?php echo abs($diff)<0.005?'bg-success':'bg-warning text-dark'; ?>"><?php echo number_format($diff,2,',','.'); ?></span></td>
            <td><?php echo htmlspecialchars((string)($row['note']??''),ENT_QUOTES,'UTF-8'); ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
</div>
