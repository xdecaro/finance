<?php
namespace Xdecaro\Component\Decarofinance\Administrator\View\Information;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
    public string $version = '';
    public string $coreVersion = '';
    public bool $coreApiAvailable = false;
    public bool $coreUiActive = false;

    public function display($tpl = null): void
    {
        $app = Factory::getApplication();
        if (!$app->getIdentity()->authorise('core.manage', 'com_decarofinance')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }
        ToolbarHelper::title(Text::_('COM_DECAROFINANCE_INFORMATION'), 'info-circle');
        $manifest = JPATH_ADMINISTRATOR . '/components/com_decarofinance/decarofinance.xml';
        if (is_file($manifest)) {
            $xml = @simplexml_load_file($manifest);
            $this->version = $xml !== false ? trim((string) $xml->version) : '';
        }
        \Xdecaro\Component\Decarofinance\Administrator\Helper\UiHelper::loadAssets();
        $wa = $app->getDocument()->getWebAssetManager();
        try {
            $component = $app->bootComponent('com_decarofinance');
            if (!$component instanceof \Xdecaro\Component\Decarofinance\Administrator\Extension\DecarofinanceComponent) {
                throw new \RuntimeException('Finance component is unavailable.');
            }
            $core = $component->getCoreIntegrationService();
            $this->coreVersion = $core->getVersion();
            $this->coreApiAvailable = $core->isReferenceApiAvailable();
            $this->coreUiActive = $core->enableUi($wa);
        } catch (\Throwable) {
        }
        parent::display($tpl);
    }
}
