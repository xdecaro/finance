<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
?>
<div class="xdf-finance">
  <div class="alert alert-info"><?php echo Text::_('COM_DECAROFINANCE_COST_CENTER_HELP'); ?></div>

  <section class="xdecaro-card xdf-form-card">
    <div class="xdecaro-card__header"><h2 class="xdecaro-card__title"><?php echo Text::_('COM_DECAROFINANCE_NEW_COST_CENTER'); ?></h2></div>
    <div class="xdecaro-card__body">
      <form action="<?php echo Route::_('index.php?option=com_decarofinance&task=finance.createCostCenter'); ?>" method="post" class="xdf-form-grid">
        <label><?php echo Text::_('COM_DECAROFINANCE_CODE'); ?><input class="form-control" name="code" maxlength="64"></label>
        <label><?php echo Text::_('JGLOBAL_TITLE'); ?><input class="form-control" name="title" required maxlength="255"></label>
        <label class="xdf-span-2"><?php echo Text::_('COM_DECAROFINANCE_OWNER_REFERENCE'); ?>
          <select class="form-select" name="owner_organization_uuid">
            <option value=""><?php echo Text::_('COM_DECAROFINANCE_NO_OWNER'); ?></option>
            <?php foreach($this->organizations as $organization): ?>
              <option value="<?php echo htmlspecialchars((string)$organization['uuid'],ENT_QUOTES,'UTF-8'); ?>"><?php echo htmlspecialchars((string)$organization['name'],ENT_QUOTES,'UTF-8'); ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <div class="xdf-span-2"><button class="btn btn-primary" type="submit"><?php echo Text::_('JSAVE'); ?></button></div>
        <?php echo HTMLHelper::_('form.token'); ?>
      </form>
    </div>
  </section>

  <section class="xdecaro-card">
    <div class="xdecaro-card__body xdf-table-wrap">
      <table class="table table-striped">
        <thead><tr><th>ID</th><th><?php echo Text::_('COM_DECAROFINANCE_CODE'); ?></th><th><?php echo Text::_('JGLOBAL_TITLE'); ?></th><th><?php echo Text::_('COM_DECAROFINANCE_OWNER'); ?></th><th><?php echo Text::_('COM_DECAROFINANCE_ORIGIN'); ?></th></tr></thead>
        <tbody>
        <?php foreach($this->items as $row): ?>
          <tr>
            <td><?php echo (int)$row['id']; ?></td>
            <td><?php echo htmlspecialchars((string)($row['code']??''),ENT_QUOTES,'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars((string)$row['title'],ENT_QUOTES,'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars((string)($row['owner_label']??''),ENT_QUOTES,'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars((string)($row['source_label']??''),ENT_QUOTES,'UTF-8'); ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
</div>
