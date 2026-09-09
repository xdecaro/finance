<?php
namespace Xdecaro\Component\Decarofinance\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Xdecaro\Component\Decarofinance\Administrator\Extension\DecarofinanceComponent;

return new class implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->registerServiceProvider(new MVCFactory('Xdecaro\\Component\\Decarofinance'));
        $container->registerServiceProvider(new ComponentDispatcherFactory('Xdecaro\\Component\\Decarofinance'));
        $container->share(CoreIntegrationService::class,static fn()=>new CoreIntegrationService());
        $container->share(FinanceService::class,static fn(Container $c)=>new FinanceService($c->get(DatabaseInterface::class)));
        $container->share(FinanceQueryService::class,static fn(Container $c)=>new FinanceQueryService($c->get(DatabaseInterface::class)));
        $container->share(CrossProductIntegrationService::class,static fn()=>new CrossProductIntegrationService());
        $container->share(AnalyticsSourceService::class,static fn(Container $c)=>new AnalyticsSourceService($c->get(FinanceQueryService::class)));
        $container->share(FinanceReminderService::class,static fn(Container $c)=>new FinanceReminderService($c->get(FinanceQueryService::class),$c->get(CrossProductIntegrationService::class)));
        $container->set(ComponentInterface::class,static function(Container $c):ComponentInterface {
            $component=new DecarofinanceComponent($c->get(ComponentDispatcherFactoryInterface::class),$c->get(MVCFactoryInterface::class));
            $component->setCoreIntegrationService($c->get(CoreIntegrationService::class)); $component->setFinanceService($c->get(FinanceService::class)); $component->setFinanceQueryService($c->get(FinanceQueryService::class)); $component->setCrossProductIntegrationService($c->get(CrossProductIntegrationService::class)); $component->setAnalyticsSourceService($c->get(AnalyticsSourceService::class)); $component->setFinanceReminderService($c->get(FinanceReminderService::class)); return $component;
        });
    }
};
