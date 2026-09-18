<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$typeLabel=static function(string $type):string {
    $key='COM_DECAROFINANCE_STATEMENT_TYPE_'.strtoupper($type);
    return Text::_($key);
};
$statusLabel=static function(string $status):string {
    $key='COM_DECAROFINANCE_STATEMENT_STATUS_'.strtoupper($status);
    return Text::_($key);
};
?>
<div class="xdf-finance">
  <div class="alert alert-info"><?php echo Text::_('COM_DECAROFINANCE_STATEMENT_DISCLAIMER'); ?></div>
  <?php if($this->canCreate): ?>
  <section class="xdecaro-card xdf-form-card">
    <div class="xdecaro-card__header"><h2 class="xdecaro-card__title"><?php echo Text::_('COM_DECAROFINANCE_NEW_STATEMENT'); ?></h2></div>
    <div class="xdecaro-card__body">
      <form action="<?php echo Route::_('index.php?option=com_decarofinance&task=finance.createStatement'); ?>" method="post" class="xdf-form-grid">
        <label><?php echo Text::_('COM_DECAROFINANCE_STATEMENT_TYPE'); ?>
          <select class="form-select" name="statement_type">
            <option value="operational"><?php echo Text::_('COM_DECAROFINANCE_STATEMENT_TYPE_OPERATIONAL'); ?></option>
            <option value="financial_position"><?php echo Text::_('COM_DECAROFINANCE_STATEMENT_TYPE_FINANCIAL_POSITION'); ?></option>
            <option value="management"><?php echo Text::_('COM_DECAROFINANCE_STATEMENT_TYPE_MANAGEMENT'); ?></option>
            <option value="mission"><?php echo Text::_('COM_DECAROFINANCE_STATEMENT_TYPE_MISSION'); ?></option>
            <option value="social"><?php echo Text::_('COM_DECAROFINANCE_STATEMENT_TYPE_SOCIAL'); ?></option>
            <option value="custom"><?php echo Text::_('COM_DECAROFINANCE_STATEMENT_TYPE_CUSTOM'); ?></option>
          </select>
        </label>
        <label><?php echo Text::_('JGLOBAL_TITLE'); ?><input class="form-control" name="title" required maxlength="255"></label>
        <label><?php echo Text::_('COM_DECAROFINANCE_PERIOD_START'); ?><input class="form-control" type="date" name="period_start"></label>
        <label><?php echo Text::_('COM_DECAROFINANCE_PERIOD_END'); ?><input class="form-control" type="date" name="period_end"></label>
        <label><?php echo Text::_('COM_DECAROFINANCE_CURRENCY'); ?><input class="form-control" name="currency" value="EUR" maxlength="3" required></label>
        <label><?php echo Text::_('COM_DECAROFINANCE_EXTERNAL_KEY'); ?><input class="form-control" name="external_key" maxlength="191"></label>
        <details class="xdf-span-2 xdf-advanced"><summary><?php echo Text::_('COM_DECAROFINANCE_OWNER_REFERENCE'); ?></summary><div class="xdf-form-grid">
          <label><?php echo Text::_('COM_DECAROFINANCE_OWNER_COMPONENT'); ?><input class="form-control" name="owner_component" placeholder="com_xxx"></label>
          <label><?php echo Text::_('COM_DECAROFINANCE_OWNER_ENTITY'); ?><input class="form-control" name="owner_entity"></label>
          <label><?php echo Text::_('COM_DECAROFINANCE_OWNER_ID'); ?><input class="form-control" name="owner_id"></label>
        </div></details>
        <details class="xdf-span-2 xdf-advanced"><summary><?php echo Text::_('COM_DECAROFINANCE_SOURCE_AND_DOCUMENT'); ?></summary><div class="xdf-form-grid">
          <label><?php echo Text::_('COM_DECAROFINANCE_SOURCE_COMPONENT'); ?><input class="form-control" name="source_component" placeholder="com_xxx"></label>
          <label><?php echo Text::_('COM_DECAROFINANCE_SOURCE_ENTITY'); ?><input class="form-control" name="source_entity"></label>
          <label><?php echo Text::_('COM_DECAROFINANCE_SOURCE_ID'); ?><input class="form-control" name="source_id"></label>
          <label><?php echo Text::_('COM_DECAROFINANCE_DOCUMENT_COMPONENT'); ?><input class="form-control" name="document_component" placeholder="com_xdecarodocuments"></label>
          <label><?php echo Text::_('COM_DECAROFINANCE_DOCUMENT_ENTITY'); ?><input class="form-control" name="document_entity" placeholder="document"></label>
          <label><?php echo Text::_('COM_DECAROFINANCE_DOCUMENT_ID'); ?><input class="form-control" name="document_id"></label>
        </div></details>
        <div class="xdf-span-2"><button class="btn btn-primary" type="submit"><?php echo Text::_('JSAVE'); ?></button></div>
        <?php echo HTMLHelper::_('form.token'); ?>
      </form>
    </div>
  </section>
  <?php endif; ?>

  <div class="xdf-statement-list">
  <?php foreach($this->items as $statement): $id=(int)$statement['id']; $status=(string)$statement['status']; $lines=$this->lines[$id]??[]; ?>
    <section class="xdecaro-card xdf-statement">
      <div class="xdecaro-card__header xdf-statement-head">
        <div>
          <h2 class="xdecaro-card__title"><?php echo htmlspecialchars((string)$statement['title'],ENT_QUOTES,'UTF-8'); ?></h2>
          <div class="xdf-muted"><?php echo htmlspecialchars($typeLabel((string)$statement['statement_type']),ENT_QUOTES,'UTF-8'); ?> · <?php echo htmlspecialchars((string)($statement['period_start']??''),ENT_QUOTES,'UTF-8'); ?> → <?php echo htmlspecialchars((string)($statement['period_end']??''),ENT_QUOTES,'UTF-8'); ?></div>
        </div>
        <span class="badge bg-secondary"><?php echo htmlspecialchars($statusLabel($status),ENT_QUOTES,'UTF-8'); ?></span>
      </div>
      <div class="xdecaro-card__body">
        <div class="xdf-table-wrap">
          <table class="table table-sm">
            <thead><tr><th><?php echo Text::_('COM_DECAROFINANCE_SECTION'); ?></th><th><?php echo Text::_('COM_DECAROFINANCE_CODE'); ?></th><th><?php echo Text::_('COM_DECAROFINANCE_LABEL'); ?></th><th><?php echo Text::_('COM_DECAROFINANCE_LINE_TYPE'); ?></th><th><?php echo Text::_('COM_DECAROFINANCE_VALUE'); ?></th></tr></thead>
            <tbody>
            <?php foreach($lines as $line): ?>
              <tr>
                <td><?php echo htmlspecialchars((string)($line['section_code']??''),ENT_QUOTES,'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars((string)($line['line_code']??''),ENT_QUOTES,'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars((string)$line['label'],ENT_QUOTES,'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars((string)$line['line_type'],ENT_QUOTES,'UTF-8'); ?></td>
                <td><?php echo in_array((string)$line['line_type'],['amount','subtotal'],true) ? number_format((float)$line['amount'],2,',','.').' '.htmlspecialchars((string)$statement['currency'],ENT_QUOTES,'UTF-8') : htmlspecialchars((string)($line['text_value']??''),ENT_QUOTES,'UTF-8'); ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <?php if($status==='draft' && $this->canCreate): ?>
        <details class="xdf-advanced"><summary><?php echo Text::_('COM_DECAROFINANCE_ADD_STATEMENT_LINE'); ?></summary>
          <form action="<?php echo Route::_('index.php?option=com_decarofinance&task=finance.addStatementLine'); ?>" method="post" class="xdf-form-grid xdf-statement-line-form">
            <input type="hidden" name="statement_id" value="<?php echo $id; ?>">
            <label><?php echo Text::_('COM_DECAROFINANCE_SECTION'); ?><input class="form-control" name="section_code" maxlength="64"></label>
            <label><?php echo Text::_('COM_DECAROFINANCE_CODE'); ?><input class="form-control" name="line_code" maxlength="64"></label>
            <label class="xdf-span-2"><?php echo Text::_('COM_DECAROFINANCE_LABEL'); ?><input class="form-control" name="label" required maxlength="255"></label>
            <label><?php echo Text::_('COM_DECAROFINANCE_LINE_TYPE'); ?><select class="form-select" name="line_type"><option value="amount"><?php echo Text::_('COM_DECAROFINANCE_LINE_TYPE_AMOUNT'); ?></option><option value="subtotal"><?php echo Text::_('COM_DECAROFINANCE_LINE_TYPE_SUBTOTAL'); ?></option><option value="text"><?php echo Text::_('COM_DECAROFINANCE_LINE_TYPE_TEXT'); ?></option><option value="note"><?php echo Text::_('COM_DECAROFINANCE_LINE_TYPE_NOTE'); ?></option></select></label>
            <label><?php echo Text::_('COM_DECAROFINANCE_AMOUNT'); ?><input class="form-control" name="amount" inputmode="decimal" value="0.00"></label>
            <label class="xdf-span-2"><?php echo Text::_('COM_DECAROFINANCE_TEXT_VALUE'); ?><textarea class="form-control" name="text_value" rows="2" maxlength="4000"></textarea></label>
            <label><?php echo Text::_('COM_DECAROFINANCE_SORT_ORDER'); ?><input class="form-control" type="number" name="sort_order" value="0"></label>
            <div class="xdf-span-2"><button class="btn btn-primary" type="submit"><?php echo Text::_('COM_DECAROFINANCE_ADD_LINE'); ?></button></div>
            <?php echo HTMLHelper::_('form.token'); ?>
          </form>
        </details>
        <?php endif; ?>

        <div class="xdf-row-actions xdf-statement-actions">
          <?php if($status==='draft' && $this->canReconcile): ?>
          <form action="<?php echo Route::_('index.php?option=com_decarofinance&task=finance.finaliseStatement'); ?>" method="post"><input type="hidden" name="statement_id" value="<?php echo $id; ?>"><button class="btn btn-outline-primary" type="submit"><?php echo Text::_('COM_DECAROFINANCE_FINALISE'); ?></button><?php echo HTMLHelper::_('form.token'); ?></form>
          <?php endif; ?>
          <?php if($status==='finalised' && $this->canApprove): ?>
          <form action="<?php echo Route::_('index.php?option=com_decarofinance&task=finance.approveStatement'); ?>" method="post"><input type="hidden" name="statement_id" value="<?php echo $id; ?>"><button class="btn btn-success" type="submit"><?php echo Text::_('COM_DECAROFINANCE_APPROVE'); ?></button><?php echo HTMLHelper::_('form.token'); ?></form>
          <?php endif; ?>
        </div>
      </div>
    </section>
  <?php endforeach; ?>
  </div>
</div>
