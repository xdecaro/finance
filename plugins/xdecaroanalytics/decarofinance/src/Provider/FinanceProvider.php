<?php
namespace Xdecaro\Plugin\Xdecaroanalytics\Decarofinance\Provider;
defined('_JEXEC') or die;
use xdecaro\Component\Analytics\Administrator\Contract\AnalyticsProviderInterface; use Xdecaro\Component\Decarofinance\Administrator\Service\AnalyticsSourceService;
final class FinanceProvider implements AnalyticsProviderInterface { public function __construct(private AnalyticsSourceService $source){} public function getKey():string{return 'finance';} public function getLabel():string{return 'Finance';} public function getMetrics():array{return $this->source->getMetrics();} public function getDatasets():array{return $this->source->getDatasets();} public function getMetric(string $metricKey,array $context=[]):array{return $this->source->getMetric($metricKey,$context);} public function getDataset(string $datasetKey,array $context=[]):array{return $this->source->getDataset($datasetKey,$context);} }
