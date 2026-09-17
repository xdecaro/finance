<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
?>
<div class="xdf-finance">
  <section class="xdecaro-card xdf-form-card">
    <div class="xdecaro-card__header"><h2 class="xdecaro-card__title"><?php echo Text::_('COM_DECAROFINANCE_NEW_ORDER'); ?></h2></div>
    <div class="xdecaro-card__body">
      <form action="<?php echo Route::_('index.php?option=com_decarofinance&task=finance.createOrder'); ?>" method="post" class="xdf-form-grid">
        <label><?php echo Text::_('COM_DECAROFINANCE_DIRECTION'); ?><select class="form-select" name="direction"><option value="expense"><?php echo Text::_('COM_DECAROFINANCE_EXPENSE_ORDER'); ?></option><option value="income"><?php echo Text::_('COM_DECAROFINANCE_INCOME_ORDER'); ?></option></select></label>
        <label><?php echo Text::_('COM_DECAROFINANCE_ACCOUNT'); ?><select class="form-select" name="account_id"><option value="0">—</option><?php foreach($this->accounts as $a): ?><option value="<?php echo (int)$a['id']; ?>"><?php echo htmlspecialchars((string)$a['name'].' · '.(string)$a['currency'],ENT_QUOTES,'UTF-8'); ?></option><?php endforeach; ?></select></label>
        <label><?php echo Text::_('COM_DECAROFINANCE_BUDGET_LINE'); ?><select class="form-select" name="budget_line_id"><option value="0">—</option><?php foreach($this->budgetLines as $l): ?><option value="<?php echo (int)$l['id']; ?>"><?php echo htmlspecialchars((string)$l['budget_title'].' · '.(string)$l['title'].' · '.(string)$l['currency'],ENT_QUOTES,'UTF-8'); ?></option><?php endforeach; ?></select></label>
        <label><?php echo Text::_('COM_DECAROFINANCE_CATEGORY'); ?><input class="form-control" name="category" maxlength="100"></label>
        <label><?php echo Text::_('COM_DECAROFINANCE_AMOUNT'); ?><input class="form-control" name="amount" required inputmode="decimal"></label>
        <label><?php echo Text::_('COM_DECAROFINANCE_CURRENCY'); ?><input class="form-control" name="currency" value="EUR" maxlength="3" required></label>
        <label><?php echo Text::_('COM_DECAROFINANCE_DUE_DATE'); ?><input class="form-control" type="date" name="due_date"></label>
        <label><?php echo Text::_('COM_DECAROFINANCE_REQUIRED_APPROVALS'); ?><input class="form-control" type="number" name="required_approvals" min="1" max="5" value="2" required></label>
        <label class="xdf-span-2"><?php echo Text::_('COM_DECAROFINANCE_DESCRIPTION'); ?><textarea class="form-control" name="description" rows="2" maxlength="500" required></textarea></label>

        <details class="xdf-span-2 xdf-advanced"><summary><?php echo Text::_('COM_DECAROFINANCE_ADVANCED_REFERENCES'); ?></summary><div class="xdf-form-grid">
          <label><?php echo Text::_('COM_DECAROFINANCE_OWNER_COMPONENT'); ?><input class="form-control" name="owner_component" placeholder="com_xxx"></label>
          <label><?php echo Text::_('COM_DECAROFINANCE_OWNER_ENTITY'); ?><input class="form-control" name="owner_entity"></label>
          <label><?php echo Text::_('COM_DECAROFINANCE_OWNER_ID'); ?><input class="form-control" name="owner_id"></label>
          <label><?php echo Text::_('COM_DECAROFINANCE_COUNTERPARTY_COMPONENT'); ?><input class="form-control" name="counterparty_component" placeholder="com_xxx"></label>
          <label><?php echo Text::_('COM_DECAROFINANCE_COUNTERPARTY_ENTITY'); ?><input class="form-control" name="counterparty_entity"></label>
          <label><?php echo Text::_('COM_DECAROFINANCE_COUNTERPARTY_ID'); ?><input class="form-control" name="counterparty_id"></label>
          <label><?php echo Text::_('COM_DECAROFINANCE_SOURCE_COMPONENT'); ?><input class="form-control" name="source_component" placeholder="com_xxx"></label>
          <label><?php echo Text::_('COM_DECAROFINANCE_SOURCE_ENTITY'); ?><input class="form-control" name="source_entity"></label>
          <label><?php echo Text::_('COM_DECAROFINANCE_SOURCE_ID'); ?><input class="form-control" name="source_id"></label>
          <label><?php echo Text::_('COM_DECAROFINANCE_EVIDENCE_COMPONENT'); ?><input class="form-control" name="evidence_component" placeholder="com_xdecarodocuments"></label>
          <label><?php echo Text::_('COM_DECAROFINANCE_EVIDENCE_ENTITY'); ?><input class="form-control" name="evidence_entity" placeholder="document"></label>
          <label><?php echo Text::_('COM_DECAROFINANCE_EVIDENCE_ID'); ?><input class="form-control" name="evidence_id"></label>
          <label><?php echo Text::_('COM_DECAROFINANCE_EXTERNAL_KEY'); ?><input class="form-control" name="external_key" maxlength="191"></label>
        </div></details>
        <div class="xdf-span-2"><button class="btn btn-primary" type="submit"><?php echo Text::_('JSAVE'); ?></button></div>
        <?php echo HTMLHelper::_('form.token'); ?>
      </form>
    </div>
  </section>

  <section class="xdecaro-card">
    <div class="xdecaro-card__body xdf-table-wrap">
      <table class="table table-striped xdf-orders-table">
        <thead><tr><th>ID</th><th><?php echo Text::_('COM_DECAROFINANCE_DESCRIPTION'); ?></th><th><?php echo Text::_('COM_DECAROFINANCE_AMOUNT'); ?></th><th><?php echo Text::_('JSTATUS'); ?></th><th><?php echo Text::_('COM_DECAROFINANCE_APPROVALS'); ?></th><th><?php echo Text::_('COM_DECAROFINANCE_ACCOUNT'); ?></th><th><?php echo Text::_('COM_DECAROFINANCE_ACTIONS'); ?></th></tr></thead>
        <tbody>
        <?php foreach($this->items as $row): $status=(string)$row['status']; $next=(int)$row['approval_count']+1; ?>
          <tr>
            <td><?php echo (int)$row['id']; ?></td>
            <td><?php echo htmlspecialchars((string)($row['description']??''),ENT_QUOTES,'UTF-8'); ?><div class="xdf-muted"><?php echo htmlspecialchars((string)($row['budget_line_title']??''),ENT_QUOTES,'UTF-8'); ?></div></td>
            <td><?php echo number_format((float)$row['amount'],2,',','.').' '.htmlspecialchars((string)$row['currency'],ENT_QUOTES,'UTF-8'); ?></td>
            <td><span class="badge bg-secondary"><?php echo htmlspecialchars($status,ENT_QUOTES,'UTF-8'); ?></span></td>
            <td><?php echo (int)$row['approval_count'].' / '.(int)$row['required_approvals']; ?></td>
            <td><?php echo htmlspecialchars((string)($row['account_name']??''),ENT_QUOTES,'UTF-8'); ?></td>
            <td>
              <div class="xdf-row-actions">
              <?php if($this->canApprove && in_array($status,['draft','pending'],true) && $next<=(int)$row['required_approvals']): ?>
                <form action="<?php echo Route::_('index.php?option=com_decarofinance&task=finance.approveOrder'); ?>" method="post" class="xdf-inline-form">
                  <input type="hidden" name="order_id" value="<?php echo (int)$row['id']; ?>"><input type="hidden" name="step" value="<?php echo $next; ?>">
                  <input class="form-control form-control-sm" name="role" maxlength="64" placeholder="<?php echo Text::_('COM_DECAROFINANCE_APPROVER_ROLE'); ?>" required>
                  <button class="btn btn-sm btn-success" type="submit"><?php echo Text::_('COM_DECAROFINANCE_APPROVE'); ?></button><?php echo HTMLHelper::_('form.token'); ?>
                </form>
              <?php endif; ?>
              <?php if($this->canExecute && $status==='approved'): ?>
                <form action="<?php echo Route::_('index.php?option=com_decarofinance&task=finance.executeOrder'); ?>" method="post" class="xdf-inline-form"><input type="hidden" name="order_id" value="<?php echo (int)$row['id']; ?>"><button class="btn btn-sm btn-primary" type="submit"><?php echo Text::_('COM_DECAROFINANCE_EXECUTE'); ?></button><?php echo HTMLHelper::_('form.token'); ?></form>
              <?php endif; ?>
              <?php if($this->canEdit && in_array($status,['draft','pending','approved'],true)): ?>
                <form action="<?php echo Route::_('index.php?option=com_decarofinance&task=finance.cancelOrder'); ?>" method="post" class="xdf-inline-form"><input type="hidden" name="order_id" value="<?php echo (int)$row['id']; ?>"><button class="btn btn-sm btn-outline-danger" type="submit"><?php echo Text::_('JCANCEL'); ?></button><?php echo HTMLHelper::_('form.token'); ?></form>
              <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
</div>
