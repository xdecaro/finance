<?php
namespace Xdecaro\Component\Decarofinance\Administrator\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

final class UiHelper
{
    private const VERSION = '1.5.3';

    public static function loadAssets(): void
    {
        $document = Factory::getApplication()->getDocument();
        $href = Uri::root(true) . '/media/com_decarofinance/css/admin.css?v=' . rawurlencode(self::VERSION);

        // The site's Joomla administrator currently does not emit the Finance
        // stylesheet when it is activated only through the component WAM registry.
        // addHeadLink produces the exact stylesheet link proven to work in-browser,
        // while remaining scoped to Finance administrator views only.
        $document->addHeadLink($href, 'stylesheet', 'rel', ['type' => 'text/css']);
    }
}
