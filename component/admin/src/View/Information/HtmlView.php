<?php
namespace Xdecaro\Component\Decarofinance\Administrator\View\Information;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Xdecaro\Component\Decarofinance\Administrator\Extension\DecarofinanceComponent;
use Xdecaro\Component\Decarofinance\Administrator\Helper\UiHelper;

final class HtmlView extends BaseHtmlView
{
    public array $info=[];
    public bool $canManageInstaller=false;

    public function display($tpl=null): void
    {
        $app=Factory::getApplication();
        $user=$app->getIdentity();
        if (!$user->authorise('core.manage','com_decarofinance')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);
        }

        ToolbarHelper::title(Text::_('COM_DECAROFINANCE_INFORMATION'),'info-circle');
        UiHelper::loadAssets();

        $this->info=(array)$this->get('Info');
        $this->canManageInstaller=$user->authorise('core.manage','com_installer');

        try {
            $component=$app->bootComponent('com_decarofinance');
            if($component instanceof DecarofinanceComponent) {
                $this->info['core']['ui']=$component->getCoreIntegrationService()->enableUi($app->getDocument()->getWebAssetManager());
            }
        } catch(\Throwable) {
            $this->info['core']['ui']=false;
        }

        if(count($errors=$this->get('Errors'))) {
            throw new \RuntimeException(implode("\n",$errors));
        }

        parent::display($tpl);
    }
}
