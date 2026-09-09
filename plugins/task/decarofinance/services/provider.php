<?php
defined('_JEXEC') or die;
use Joomla\CMS\Extension\PluginInterface; use Joomla\CMS\Plugin\PluginHelper; use Joomla\DI\Container; use Joomla\DI\ServiceProviderInterface; use Xdecaro\Plugin\Task\Decarofinance\Extension\Decarofinance;
return new class implements ServiceProviderInterface { public function register(Container $container):void{$container->set(PluginInterface::class,$container->lazy(Decarofinance::class,static fn():Decarofinance=>new Decarofinance((array)PluginHelper::getPlugin('task','decarofinance'))));} };
