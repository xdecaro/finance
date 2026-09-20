<?php
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Xdecaro\Component\Decarofinance\Administrator\Helper\UiHelper;

$info=$this->info;
$version=(string)($info['installed_version']??'0.0.0');
$versions=(array)($info['versions']??[]);
$extensions=(array)($info['extensions']??[]);
$tables=(array)($info['tables']??[]);
$update=(array)($info['update']??[]);
$core=(array)($info['core']??[]);
$integrations=(array)($info['integrations']??[]);
$services=(array)($info['services']??[]);
$critical=(array)($info['critical']??[]);
$warnings=(array)($info['warnings']??[]);
$ok=count($critical)===0;

$badge=static function(bool $condition,string $yes,string $no,bool $warning=false):string {
    $class=$condition?'xdecaro-badge--success':($warning?'xdecaro-badge--warning':'xdecaro-badge--danger');
    return '<span class="xdecaro-badge '.$class.'">'.htmlspecialchars($condition?$yes:$no,ENT_QUOTES,'UTF-8').'</span>';
};
$versionBadge=static function(string $value) use($version):string {
    if($value==='') return '<span class="xdecaro-badge xdecaro-badge--muted">'.Text::_('COM_DECAROFINANCE_INFO_NOT_INSTALLED').'</span>';
    $class=$value===$version?'xdecaro-badge--success':'xdecaro-badge--warning';
    return '<span class="xdecaro-badge '.$class.'">'.htmlspecialchars($value,ENT_QUOTES,'UTF-8').'</span>';
};
$updateState=!empty($update['available'])?'available':(!empty($update['site_enabled'])?'current':'inactive');
$updateLabel=match($updateState){
    'available'=>Text::_('COM_DECAROFINANCE_INFO_UPDATE_AVAILABLE'),
    'current'=>Text::_('COM_DECAROFINANCE_INFO_UP_TO_DATE'),
    default=>Text::_('COM_DECAROFINANCE_INFO_INACTIVE'),
};
$updateClass=match($updateState){
    'available'=>'xdecaro-badge--warning',
    'current'=>'xdecaro-badge--success',
    default=>'xdecaro-badge--muted',
};
?>
<div class="xdecaro-scope xdf-finance xdf-information">
  <?php if(!$ok): ?>
    <div class="alert alert-danger"><?php echo Text::_('COM_DECAROFINANCE_INFO_ATTENTION_REQUIRED'); ?></div>
  <?php elseif(!empty($update['available'])): ?>
    <div class="alert alert-info"><?php echo Text::sprintf('COM_DECAROFINANCE_INFO_UPDATE_AVAILABLE_DESC',(string)$update['latest']); ?></div>
  <?php endif; ?>

  <div class="xdf-info-summary">
    <strong>Finance <?php echo htmlspecialchars($version,ENT_QUOTES,'UTF-8'); ?></strong>
    <span class="xdecaro-badge <?php echo $updateClass; ?>"><?php echo htmlspecialchars($updateLabel,ENT_QUOTES,'UTF-8'); ?></span>
    <span class="xdecaro-badge <?php echo $ok?'xdecaro-badge--success':'xdecaro-badge--danger'; ?>"><?php echo Text::_($ok?'COM_DECAROFINANCE_INFO_SYSTEM_OK':'COM_DECAROFINANCE_INFO_SYSTEM_CHECK'); ?></span>
  </div>

  <div class="xdf-info-grid">
    <section class="xdecaro-card">
      <div class="xdecaro-card__header"><h2 class="xdecaro-card__title"><?php echo Text::_('COM_DECAROFINANCE_INFO_PRODUCT_ENVIRONMENT'); ?></h2></div>
      <div class="xdecaro-card__body">
        <dl class="xdf-details">
          <dt><?php echo Text::_('COM_DECAROFINANCE_VERSION'); ?></dt><dd><?php echo htmlspecialchars($version,ENT_QUOTES,'UTF-8'); ?></dd>
          <dt>Joomla</dt><dd><?php echo htmlspecialchars((string)($info['joomla_version']??'—'),ENT_QUOTES,'UTF-8'); ?></dd>
          <dt>PHP</dt><dd><?php echo htmlspecialchars((string)($info['php_version']??'—'),ENT_QUOTES,'UTF-8'); ?></dd>
          <dt><?php echo Text::_('COM_DECAROFINANCE_INFO_DATABASE'); ?></dt><dd><?php echo htmlspecialchars(trim((string)($info['database_type']??'').' '.(string)($info['database_version']??'')),ENT_QUOTES,'UTF-8'); ?></dd>
          <dt><?php echo Text::_('COM_DECAROFINANCE_INFO_SCHEMA'); ?></dt><dd><?php echo htmlspecialchars((string)($info['schema_version']??'—'),ENT_QUOTES,'UTF-8'); ?></dd>
          <dt><?php echo Text::_('COM_DECAROFINANCE_CORE'); ?></dt><dd><?php echo !empty($core['installed'])?htmlspecialchars((string)($core['version']??''),ENT_QUOTES,'UTF-8'):Text::_('COM_DECAROFINANCE_INFO_NOT_INSTALLED'); ?></dd>
          <dt><?php echo Text::_('COM_DECAROFINANCE_CORE_API'); ?></dt><dd><?php echo $badge(!empty($core['api']),Text::_('COM_DECAROFINANCE_INFO_AVAILABLE'),Text::_('COM_DECAROFINANCE_INFO_NOT_AVAILABLE'),true); ?></dd>
          <dt><?php echo Text::_('COM_DECAROFINANCE_CORE_UI'); ?></dt><dd><?php echo $badge(!empty($core['ui']),Text::_('COM_DECAROFINANCE_INFO_ACTIVE'),Text::_('COM_DECAROFINANCE_INFO_NOT_ACTIVE'),true); ?></dd>
        </dl>
      </div>
    </section>

    <section class="xdecaro-card">
      <div class="xdecaro-card__header"><h2 class="xdecaro-card__title"><?php echo Text::_('COM_DECAROFINANCE_INFO_INCLUDED_EXTENSIONS'); ?></h2></div>
      <div class="xdecaro-card__body">
        <dl class="xdf-details">
          <dt><?php echo Text::_('COM_DECAROFINANCE_INFO_PACKAGE'); ?></dt><dd><?php echo $versionBadge((string)($versions['package']??'')); ?></dd>
          <dt><?php echo Text::_('COM_DECAROFINANCE_INFO_COMPONENT'); ?></dt><dd><?php echo $versionBadge((string)($versions['component']??'')); ?></dd>
          <dt><?php echo Text::_('COM_DECAROFINANCE_INFO_TASK_PLUGIN'); ?></dt><dd><?php echo $versionBadge((string)($versions['task']??'')); ?> <?php if(isset($extensions['task'])) echo $badge((int)($extensions['task']->enabled??0)===1,Text::_('COM_DECAROFINANCE_INFO_ENABLED'),Text::_('COM_DECAROFINANCE_INFO_DISABLED'),true); ?></dd>
          <dt><?php echo Text::_('COM_DECAROFINANCE_INFO_ANALYTICS_PLUGIN'); ?></dt><dd><?php echo $versionBadge((string)($versions['analytics']??'')); ?> <?php if(isset($extensions['analytics'])) echo $badge((int)($extensions['analytics']->enabled??0)===1,Text::_('COM_DECAROFINANCE_INFO_ENABLED'),Text::_('COM_DECAROFINANCE_INFO_DISABLED'),true); ?></dd>
          <dt><?php echo Text::_('COM_DECAROFINANCE_INFO_INSTALLATION'); ?></dt><dd><?php echo $badge(!empty($info['consistent']),Text::_('COM_DECAROFINANCE_INFO_CONSISTENT'),Text::_('COM_DECAROFINANCE_INFO_CHECK_REQUIRED')); ?></dd>
        </dl>
      </div>
    </section>

    <section class="xdecaro-card">
      <div class="xdecaro-card__header"><h2 class="xdecaro-card__title"><?php echo Text::_('COM_DECAROFINANCE_INFO_UPDATES'); ?></h2></div>
      <div class="xdecaro-card__body">
        <dl class="xdf-details">
          <dt><?php echo Text::_('COM_DECAROFINANCE_INFO_UPDATE_SERVER'); ?></dt><dd><?php echo $badge(!empty($update['site_enabled']),Text::_('COM_DECAROFINANCE_INFO_ACTIVE'),Text::_('COM_DECAROFINANCE_INFO_NOT_ACTIVE'),true); ?></dd>
          <dt><?php echo Text::_('COM_DECAROFINANCE_INFO_INSTALLED_VERSION'); ?></dt><dd><?php echo htmlspecialchars($version,ENT_QUOTES,'UTF-8'); ?></dd>
          <dt><?php echo Text::_('COM_DECAROFINANCE_INFO_LATEST_VERSION'); ?></dt><dd><?php echo htmlspecialchars((string)($update['latest']?:Text::_('COM_DECAROFINANCE_INFO_NO_UPDATE_DETECTED')),ENT_QUOTES,'UTF-8'); ?></dd>
          <dt><?php echo Text::_('COM_DECAROFINANCE_INFO_STATUS'); ?></dt><dd><span class="xdecaro-badge <?php echo $updateClass; ?>"><?php echo htmlspecialchars($updateLabel,ENT_QUOTES,'UTF-8'); ?></span></dd>
          <dt><?php echo Text::_('COM_DECAROFINANCE_INFO_LAST_CHECK'); ?></dt><dd><?php echo !empty($update['last_check'])?htmlspecialchars(date('d/m/Y H:i',(int)$update['last_check']),ENT_QUOTES,'UTF-8'):Text::_('COM_DECAROFINANCE_INFO_NEVER'); ?></dd>
        </dl>
        <?php if($this->canManageInstaller): ?><div class="xdf-info-actions"><a class="btn btn-primary" href="<?php echo Route::_('index.php?option=com_installer&view=update'); ?>"><?php echo Text::_('COM_DECAROFINANCE_INFO_OPEN_UPDATES'); ?></a><a class="btn btn-outline-secondary" href="<?php echo Route::_('index.php?option=com_installer&view=updatesites'); ?>"><?php echo Text::_('COM_DECAROFINANCE_INFO_OPEN_UPDATE_SITES'); ?></a></div><?php endif; ?>
      </div>
    </section>

    <section class="xdecaro-card">
      <div class="xdecaro-card__header"><h2 class="xdecaro-card__title"><?php echo Text::_('COM_DECAROFINANCE_INFO_DATABASE_HEALTH'); ?></h2></div>
      <div class="xdecaro-card__body">
        <dl class="xdf-details">
          <dt><?php echo Text::_('COM_DECAROFINANCE_INFO_TABLES'); ?></dt><dd><?php echo (int)($tables['present']??0); ?>/<?php echo (int)($tables['expected']??0); ?> <?php echo $badge(!empty($tables['all_present']),Text::_('COM_DECAROFINANCE_INFO_OK'),Text::_('COM_DECAROFINANCE_INFO_CHECK_REQUIRED')); ?></dd>
          <dt><?php echo Text::_('COM_DECAROFINANCE_INFO_SCHEMA'); ?></dt><dd><?php echo $badge((string)($info['schema_version']??'')===$version,Text::_('COM_DECAROFINANCE_INFO_ALIGNED'),Text::_('COM_DECAROFINANCE_INFO_NOT_ALIGNED')); ?></dd>
          <dt><?php echo Text::_('COM_DECAROFINANCE_INFO_CRITICAL_ISSUES'); ?></dt><dd><?php echo count($critical); ?></dd>
          <dt><?php echo Text::_('COM_DECAROFINANCE_INFO_WARNINGS'); ?></dt><dd><?php echo count($warnings); ?></dd>
        </dl>
      </div>
    </section>

    <section class="xdecaro-card xdf-info-full">
      <div class="xdecaro-card__header"><h2 class="xdecaro-card__title"><?php echo Text::_('COM_DECAROFINANCE_INFO_CONNECTED_COMPONENTS'); ?></h2></div>
      <div class="xdecaro-card__body">
        <p class="xdf-muted"><?php echo Text::_('COM_DECAROFINANCE_INFO_CONNECTED_COMPONENTS_DESC'); ?></p>
        <div class="xdf-integrations">
          <?php foreach($integrations as $integration): ?>
          <article class="xdf-integration">
            <div><strong><?php echo htmlspecialchars((string)$integration['label'],ENT_QUOTES,'UTF-8'); ?></strong><div class="xdf-muted"><code><?php echo htmlspecialchars((string)$integration['component'],ENT_QUOTES,'UTF-8'); ?></code></div></div>
            <div><?php echo $badge(!empty($integration['enabled']),Text::_('COM_DECAROFINANCE_INFO_INSTALLED'),Text::_('COM_DECAROFINANCE_INFO_NOT_INSTALLED'),true); ?></div>
            <div><?php if(in_array((string)$integration['component'],['com_xdecaroorganizations','com_xdecaropeople'],true)): echo $badge(!empty($integration['provider']),Text::_('COM_DECAROFINANCE_INFO_PUBLIC_PROVIDER_OK'),Text::_('COM_DECAROFINANCE_INFO_PUBLIC_PROVIDER_MISSING'),true); else: ?><span class="xdecaro-badge xdecaro-badge--muted"><?php echo Text::_('COM_DECAROFINANCE_INFO_OPTIONAL'); ?></span><?php endif; ?></div>
          </article>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <section class="xdecaro-card xdf-info-full">
      <div class="xdecaro-card__header"><h2 class="xdecaro-card__title"><?php echo Text::_('COM_DECAROFINANCE_INFO_DIAGNOSTICS'); ?></h2></div>
      <div class="xdecaro-card__body">
        <div class="xdf-diagnostics">
          <div class="xdf-check <?php echo !empty($info['consistent'])?'is-ok':'is-error'; ?>"><?php echo !empty($info['consistent'])?'✓':'!'; ?> <?php echo Text::_('COM_DECAROFINANCE_INFO_CHECK_VERSIONS'); ?></div>
          <div class="xdf-check <?php echo !empty($tables['all_present'])?'is-ok':'is-error'; ?>"><?php echo !empty($tables['all_present'])?'✓':'!'; ?> <?php echo Text::_('COM_DECAROFINANCE_INFO_CHECK_TABLES'); ?></div>
          <div class="xdf-check <?php echo (string)($info['schema_version']??'')===$version?'is-ok':'is-error'; ?>"><?php echo (string)($info['schema_version']??'')===$version?'✓':'!'; ?> <?php echo Text::_('COM_DECAROFINANCE_INFO_CHECK_SCHEMA'); ?></div>
          <div class="xdf-check <?php echo !empty($update['site_enabled'])?'is-ok':'is-warning'; ?>"><?php echo !empty($update['site_enabled'])?'✓':'!'; ?> <?php echo Text::_('COM_DECAROFINANCE_INFO_CHECK_UPDATE_SERVER'); ?></div>
          <div class="xdf-check <?php echo !empty($services['finance'])&& !empty($services['query'])?'is-ok':'is-error'; ?>"><?php echo !empty($services['finance'])&& !empty($services['query'])?'✓':'!'; ?> <?php echo Text::_('COM_DECAROFINANCE_INFO_CHECK_SERVICES'); ?></div>
          <div class="xdf-check <?php echo !empty($services['references'])?'is-ok':'is-warning'; ?>"><?php echo !empty($services['references'])?'✓':'!'; ?> <?php echo Text::_('COM_DECAROFINANCE_INFO_CHECK_REFERENCES'); ?></div>
        </div>
        <details class="xdf-advanced">
          <summary><?php echo Text::_('COM_DECAROFINANCE_INFO_TECHNICAL_DETAILS'); ?></summary>
          <dl class="xdf-details">
            <dt><?php echo Text::_('COM_DECAROFINANCE_INFO_COMPONENT_ID'); ?></dt><dd><code>com_decarofinance</code></dd>
            <dt><?php echo Text::_('COM_DECAROFINANCE_INFO_PACKAGE_ID'); ?></dt><dd><code>pkg_decarofinance</code></dd>
            <dt><?php echo Text::_('COM_DECAROFINANCE_INFO_UPDATE_SERVER'); ?></dt><dd><code><?php echo htmlspecialchars((string)$info['update_site_url'],ENT_QUOTES,'UTF-8'); ?></code></dd>
            <dt><?php echo Text::_('COM_DECAROFINANCE_INFO_REPOSITORY'); ?></dt><dd><a href="https://github.com/xdecaro/finance" target="_blank" rel="noopener noreferrer">xdecaro/finance ↗</a></dd>
          </dl>
        </details>
      </div>
    </section>
  </div>
</div>
