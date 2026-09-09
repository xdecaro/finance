<?php
namespace Xdecaro\Component\Decarofinance\Administrator\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Extension\MVCComponent;
use LogicException;
use Xdecaro\Component\Decarofinance\Administrator\Service\AnalyticsSourceService;
use Xdecaro\Component\Decarofinance\Administrator\Service\CoreIntegrationService;
use Xdecaro\Component\Decarofinance\Administrator\Service\CrossProductIntegrationService;
use Xdecaro\Component\Decarofinance\Administrator\Service\FinanceQueryService;
use Xdecaro\Component\Decarofinance\Administrator\Service\FinanceReminderService;
use Xdecaro\Component\Decarofinance\Administrator\Service\FinanceService;

final class DecarofinanceComponent extends MVCComponent
{
    private ?FinanceService $finance=null; private ?FinanceQueryService $query=null; private ?CoreIntegrationService $core=null; private ?CrossProductIntegrationService $cross=null; private ?FinanceReminderService $reminders=null; private ?AnalyticsSourceService $analytics=null;
    public function setFinanceService(FinanceService $v):void{$this->finance=$v;} public function getFinanceService():FinanceService{return $this->finance??throw new LogicException('Finance service not initialized.');}
    public function setFinanceQueryService(FinanceQueryService $v):void{$this->query=$v;} public function getFinanceQueryService():FinanceQueryService{return $this->query??throw new LogicException('Finance query service not initialized.');}
    public function setCoreIntegrationService(CoreIntegrationService $v):void{$this->core=$v;} public function getCoreIntegrationService():CoreIntegrationService{return $this->core??throw new LogicException('Core integration service not initialized.');}
    public function setCrossProductIntegrationService(CrossProductIntegrationService $v):void{$this->cross=$v;} public function getCrossProductIntegrationService():CrossProductIntegrationService{return $this->cross??throw new LogicException('Cross-product integration service not initialized.');}
    public function setFinanceReminderService(FinanceReminderService $v):void{$this->reminders=$v;} public function getFinanceReminderService():FinanceReminderService{return $this->reminders??throw new LogicException('Finance reminder service not initialized.');}
    public function setAnalyticsSourceService(AnalyticsSourceService $v):void{$this->analytics=$v;} public function getAnalyticsSourceService():AnalyticsSourceService{return $this->analytics??throw new LogicException('Analytics source service not initialized.');}
}
