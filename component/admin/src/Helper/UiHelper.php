<?php
namespace Xdecaro\Component\Decarofinance\Administrator\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;

final class UiHelper
{
    private const VERSION = '1.5.2';

    public static function loadAssets(): void
    {
        $wa = Factory::getApplication()->getDocument()->getWebAssetManager();
        $styleName = 'com_decarofinance.admin.runtime';

        if (!$wa->assetExists('style', $styleName)) {
            $wa->registerStyle(
                $styleName,
                'com_decarofinance/css/admin.css',
                ['version' => self::VERSION]
            );
        }

        $wa->useStyle($styleName);
    }
}
