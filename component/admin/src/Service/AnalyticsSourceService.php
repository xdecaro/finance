<?php
namespace Xdecaro\Component\Decarofinance\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use RuntimeException;

final class AnalyticsSourceService
{
    public function __construct(private FinanceQueryService $query) {}

    public function getMetrics(): array
    {
        $this->authorize();
        return [
            ['key'=>'finance.obligations.open','label'=>'Open obligations','type'=>'number'],
            ['key'=>'finance.obligations.overdue','label'=>'Overdue obligations','type'=>'number'],
            ['key'=>'finance.obligations.outstanding','label'=>'Outstanding amount','type'=>'currency'],
            ['key'=>'finance.payments.total','label'=>'Payments total','type'=>'currency'],
            ['key'=>'finance.deposits.balance','label'=>'Deposit balance','type'=>'currency'],
            ['key'=>'finance.accounts.balance','label'=>'Financial account balance','type'=>'currency'],
            ['key'=>'finance.transactions.income','label'=>'Recorded income','type'=>'currency'],
            ['key'=>'finance.transactions.expense','label'=>'Recorded expenses','type'=>'currency'],
            ['key'=>'finance.orders.pending','label'=>'Orders to complete','type'=>'number'],
        ];
    }

    public function getDatasets(): array
    {
        $this->authorize();
        return [
            ['key'=>'finance.obligations','label'=>'Finance obligations'],
            ['key'=>'finance.transactions.monthly','label'=>'Monthly finance transactions'],
            ['key'=>'finance.transactions','label'=>'Financial transactions'],
            ['key'=>'finance.deposits','label'=>'Deposit accounts'],
            ['key'=>'finance.accounts','label'=>'Financial accounts'],
            ['key'=>'finance.orders','label'=>'Financial orders'],
            ['key'=>'finance.budgets.usage','label'=>'Budget usage'],
        ];
    }

    public function getMetric(string $key,array $context=[]): array
    {
        $this->authorize();
        $currency=(string)($context['currency'] ?? 'EUR');
        $s=$this->query->getSummary($currency);
        $map=[
            'finance.obligations.open'=>'open_obligations',
            'finance.obligations.overdue'=>'overdue_obligations',
            'finance.obligations.outstanding'=>'open_amount',
            'finance.payments.total'=>'payments_total',
            'finance.deposits.balance'=>'deposit_balance',
            'finance.accounts.balance'=>'account_balance',
            'finance.transactions.income'=>'income_total',
            'finance.transactions.expense'=>'expense_total',
            'finance.orders.pending'=>'pending_orders',
        ];
        if (!isset($map[$key])) { throw new RuntimeException('Unknown Finance metric.'); }
        return ['key'=>$key,'value'=>$s[$map[$key]],'currency'=>$currency];
    }

    public function getDataset(string $key,array $context=[]): array
    {
        $this->authorize();
        $currency=(string)($context['currency'] ?? 'EUR');
        return match($key) {
            'finance.obligations'=>$this->query->listObligations((int)($context['limit'] ?? 100)),
            'finance.transactions.monthly'=>$this->query->transactionsByMonth((int)($context['months'] ?? 12),$currency),
            'finance.transactions'=>$this->query->listTransactions((int)($context['limit'] ?? 100)),
            'finance.deposits'=>$this->query->listDepositAccounts((int)($context['limit'] ?? 100)),
            'finance.accounts'=>$this->query->listAccounts((int)($context['limit'] ?? 100)),
            'finance.orders'=>$this->query->listOrders((int)($context['limit'] ?? 100)),
            'finance.budgets.usage'=>$this->query->reportBudgetUsage($currency),
            default=>throw new RuntimeException('Unknown Finance dataset.'),
        };
    }

    private function authorize(): void
    {
        if (!Factory::getApplication()->getIdentity()->authorise('core.manage','com_decarofinance')) { throw new RuntimeException('Not authorised to read Finance analytics.'); }
    }
}
