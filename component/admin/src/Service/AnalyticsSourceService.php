<?php
namespace Xdecaro\Component\Decarofinance\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use RuntimeException;

final class AnalyticsSourceService
{
    public function __construct(private FinanceQueryService $query) {}
    public function getMetrics(): array { $this->authorize(); return [
        ['key'=>'finance.obligations.open','label'=>'Open obligations','type'=>'number'],
        ['key'=>'finance.obligations.overdue','label'=>'Overdue obligations','type'=>'number'],
        ['key'=>'finance.obligations.outstanding','label'=>'Outstanding amount','type'=>'currency'],
        ['key'=>'finance.payments.total','label'=>'Payments total','type'=>'currency'],
        ['key'=>'finance.deposits.balance','label'=>'Deposit balance','type'=>'currency'],
    ]; }
    public function getDatasets(): array { $this->authorize(); return [
        ['key'=>'finance.obligations','label'=>'Finance obligations'],['key'=>'finance.transactions.monthly','label'=>'Monthly finance transactions'],['key'=>'finance.deposits','label'=>'Deposit accounts']
    ]; }
    public function getMetric(string $key,array $context=[]): array { $this->authorize(); $s=$this->query->getSummary(); $map=['finance.obligations.open'=>'open_obligations','finance.obligations.overdue'=>'overdue_obligations','finance.obligations.outstanding'=>'open_amount','finance.payments.total'=>'payments_total','finance.deposits.balance'=>'deposit_balance']; if (!isset($map[$key])) throw new RuntimeException('Unknown Finance metric.'); return ['key'=>$key,'value'=>$s[$map[$key]],'currency'=>$context['currency'] ?? 'EUR']; }
    public function getDataset(string $key,array $context=[]): array { $this->authorize(); return match($key) { 'finance.obligations'=>$this->query->listObligations((int)($context['limit'] ?? 100)), 'finance.transactions.monthly'=>$this->query->transactionsByMonth((int)($context['months'] ?? 12)), 'finance.deposits'=>$this->query->listDepositAccounts((int)($context['limit'] ?? 100)), default=>throw new RuntimeException('Unknown Finance dataset.') }; }
    private function authorize(): void { if (!Factory::getApplication()->getIdentity()->authorise('core.manage','com_decarofinance')) throw new RuntimeException('Not authorised to read Finance analytics.'); }
}
