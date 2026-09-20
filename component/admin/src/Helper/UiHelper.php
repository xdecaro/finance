<?php
namespace Xdecaro\Component\Decarofinance\Administrator\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use DateTimeZone;
use Throwable;

final class UiHelper
{
    private const VERSION = '1.6.0';

    public static function formatDateTime(?string $value, string $format='Y-m-d H:i:s'): string
    {
        $value=trim((string)$value);
        if ($value==='') { return ''; }

        try {
            $app=Factory::getApplication();
            $user=$app->getIdentity();
            $timezone=(string)($user->getParam('timezone') ?: $app->get('offset','UTC'));
            $date=Factory::getDate($value,'UTC');
            $date->setTimezone(new DateTimeZone($timezone));
            return $date->format($format,true);
        } catch (Throwable) {
            return $value;
        }
    }

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
