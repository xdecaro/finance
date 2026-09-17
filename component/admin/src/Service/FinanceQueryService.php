<?php
namespace Xdecaro\Component\Decarofinance\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

final class FinanceQueryService
{
    public function __construct(private DatabaseInterface $db) {}

    public function getSummary(string $currency='EUR'): array
    {
        $currency=$this->normalizeCurrency($currency);

        return [
            'currency'=>$currency,
            'open_obligations'=>$this->count("#__decarofinance_obligations","status IN ('open','partial') AND currency = ".$this->db->quote($currency)),
            'overdue_obligations'=>$this->count("#__decarofinance_obligations","status IN ('open','partial') AND currency = ".$this->db->quote($currency)." AND due_date IS NOT NULL AND due_date < CURRENT_DATE"),
            'open_amount'=>$this->outstandingTotal($currency),
            'payments_total'=>$this->sumWhere('#__decarofinance_payments','amount','currency = '.$this->db->quote($currency)),
            'deposit_balance'=>$this->sumWhere('#__decarofinance_deposit_movements','amount',"account_id IN (SELECT id FROM ".$this->db->quoteName('#__decarofinance_deposit_accounts')." WHERE currency = ".$this->db->quote($currency).")"),
            'budgets'=>$this->count('#__decarofinance_budgets','state = 1 AND currency = '.$this->db->quote($currency)),
            'accounts'=>$this->count('#__decarofinance_accounts','state = 1 AND currency = '.$this->db->quote($currency)),
            'account_balance'=>$this->accountBalanceTotal($currency),
            'income_total'=>$this->sumWhere('#__decarofinance_transactions','amount',"direction = 'income' AND currency = ".$this->db->quote($currency)),
            'expense_total'=>$this->sumWhere('#__decarofinance_transactions','amount',"direction = 'expense' AND currency = ".$this->db->quote($currency)),
            'pending_orders'=>$this->count('#__decarofinance_orders',"status IN ('draft','pending','approved') AND currency = ".$this->db->quote($currency)),
        ];
    }

    public function listObligations(int $limit=100): array
    {
        $limit=max(1,min(500,$limit));
        $q=$this->db->getQuery(true)->select('o.*, COALESCE(SUM(a.amount),0) AS allocated_amount')->from($this->db->quoteName('#__decarofinance_obligations','o'))->leftJoin($this->db->quoteName('#__decarofinance_payment_allocations','a').' ON a.obligation_id = o.id')->group('o.id')->order('o.created DESC');
        return $this->db->setQuery($q,0,$limit)->loadAssocList();
    }

    public function listPayments(int $limit=100): array
    {
        $q=$this->db->getQuery(true)->select('p.*, COALESCE(SUM(a.amount),0) AS allocated_amount')->from($this->db->quoteName('#__decarofinance_payments','p'))->leftJoin($this->db->quoteName('#__decarofinance_payment_allocations','a').' ON a.payment_id = p.id')->group('p.id')->order('p.paid_at DESC, p.id DESC');
        return $this->db->setQuery($q,0,max(1,min(500,$limit)))->loadAssocList();
    }

    public function listDepositAccounts(int $limit=100): array
    {
        $q=$this->db->getQuery(true)->select('a.*, COALESCE(SUM(m.amount),0) AS balance')->from($this->db->quoteName('#__decarofinance_deposit_accounts','a'))->leftJoin($this->db->quoteName('#__decarofinance_deposit_movements','m').' ON m.account_id = a.id')->group('a.id')->order('a.id DESC');
        return $this->db->setQuery($q,0,max(1,min(500,$limit)))->loadAssocList();
    }

    public function listBudgets(int $limit=100): array
    {
        $q=$this->db->getQuery(true)
            ->select('b.*, COALESCE(SUM(CASE WHEN l.kind = '.$this->db->quote('income').' THEN l.planned_amount ELSE 0 END),0) AS planned_income, COALESCE(SUM(CASE WHEN l.kind = '.$this->db->quote('expense').' THEN l.planned_amount ELSE 0 END),0) AS planned_expense')
            ->from($this->db->quoteName('#__decarofinance_budgets','b'))
            ->leftJoin($this->db->quoteName('#__decarofinance_budget_lines','l').' ON l.budget_id = b.id')
            ->group('b.id')->order('b.created DESC');
        return $this->db->setQuery($q,0,max(1,min(500,$limit)))->loadAssocList();
    }

    public function listBudgetLines(int $limit=500): array
    {
        $q=$this->db->getQuery(true)
            ->select(['l.*','b.title AS budget_title','b.currency','b.period_start','b.period_end'])
            ->from($this->db->quoteName('#__decarofinance_budget_lines','l'))
            ->innerJoin($this->db->quoteName('#__decarofinance_budgets','b').' ON b.id = l.budget_id')
            ->where('b.state = 1')
            ->order('b.created DESC, l.id ASC');
        return $this->db->setQuery($q,0,max(1,min(1000,$limit)))->loadAssocList();
    }

    public function listAccounts(int $limit=200): array
    {
        $q=$this->db->getQuery(true)
            ->select('a.*, (a.opening_balance + COALESCE(SUM(CASE WHEN t.direction = '.$this->db->quote('income').' THEN t.amount ELSE -t.amount END),0)) AS balance')
            ->from($this->db->quoteName('#__decarofinance_accounts','a'))
            ->leftJoin($this->db->quoteName('#__decarofinance_transactions','t').' ON t.account_id = a.id')
            ->group('a.id')
            ->order('a.state DESC, a.name ASC');
        return $this->db->setQuery($q,0,max(1,min(500,$limit)))->loadAssocList();
    }

    public function listTransactions(int $limit=200): array
    {
        $q=$this->db->getQuery(true)
            ->select(['t.*','a.name AS account_name','b.title AS budget_title','l.title AS budget_line_title'])
            ->from($this->db->quoteName('#__decarofinance_transactions','t'))
            ->leftJoin($this->db->quoteName('#__decarofinance_accounts','a').' ON a.id = t.account_id')
            ->leftJoin($this->db->quoteName('#__decarofinance_budget_lines','l').' ON l.id = t.budget_line_id')
            ->leftJoin($this->db->quoteName('#__decarofinance_budgets','b').' ON b.id = l.budget_id')
            ->order('t.occurred_at DESC, t.id DESC');
        return $this->db->setQuery($q,0,max(1,min(1000,$limit)))->loadAssocList();
    }

    public function listOrders(int $limit=200): array
    {
        $q=$this->db->getQuery(true)
            ->select(['o.*','a.name AS account_name','b.title AS budget_title','l.title AS budget_line_title','COALESCE(ap.approval_count,0) AS approval_count'])
            ->from($this->db->quoteName('#__decarofinance_orders','o'))
            ->leftJoin($this->db->quoteName('#__decarofinance_accounts','a').' ON a.id = o.account_id')
            ->leftJoin($this->db->quoteName('#__decarofinance_budget_lines','l').' ON l.id = o.budget_line_id')
            ->leftJoin($this->db->quoteName('#__decarofinance_budgets','b').' ON b.id = l.budget_id')
            ->leftJoin('(SELECT order_id, COUNT(*) AS approval_count FROM '.$this->db->quoteName('#__decarofinance_order_approvals')." WHERE decision = 'approved' GROUP BY order_id) ap ON ap.order_id = o.id")
            ->order("FIELD(o.status,'pending','draft','approved','executed','cancelled'), o.due_date IS NULL, o.due_date ASC, o.id DESC");
        return $this->db->setQuery($q,0,max(1,min(1000,$limit)))->loadAssocList();
    }

    public function listOrderApprovals(int $orderId): array
    {
        if ($orderId<1) { return []; }
        $id=$orderId;
        $q=$this->db->getQuery(true)->select('*')->from($this->db->quoteName('#__decarofinance_order_approvals'))->where($this->db->quoteName('order_id').' = :id')->order('step ASC')->bind(':id',$id,ParameterType::INTEGER);
        return $this->db->setQuery($q)->loadAssocList();
    }

    public function getReportSummary(string $currency='EUR'): array
    {
        $currency=$this->normalizeCurrency($currency);
        $summary=$this->getSummary($currency);
        $summary['net_total']=round((float)$summary['income_total']-(float)$summary['expense_total'],2);
        $summary['approved_orders_amount']=$this->sumWhere('#__decarofinance_orders','amount',"direction = 'expense' AND status = 'approved' AND currency = ".$this->db->quote($currency));
        $summary['pending_orders_amount']=$this->sumWhere('#__decarofinance_orders','amount',"direction = 'expense' AND status IN ('draft','pending') AND currency = ".$this->db->quote($currency));
        return $summary;
    }

    public function reportByCategory(string $currency='EUR'): array
    {
        $currency=$this->normalizeCurrency($currency);
        $q=$this->db->getQuery(true)
            ->select(["COALESCE(NULLIF(category,''),'uncategorized') AS category",'direction','SUM(amount) AS amount','COUNT(*) AS movements'])
            ->from($this->db->quoteName('#__decarofinance_transactions'))
            ->where('currency = '.$this->db->quote($currency))
            ->group("COALESCE(NULLIF(category,''),'uncategorized'), direction")
            ->order('category ASC, direction ASC');
        return $this->db->setQuery($q)->loadAssocList();
    }

    public function reportBudgetUsage(string $currency='EUR'): array
    {
        $currency=$this->normalizeCurrency($currency);
        $q=$this->db->getQuery(true)
            ->select([
                'l.id','l.budget_id','b.title AS budget_title','b.currency','l.kind','l.code','l.category','l.title','l.planned_amount',
                'COALESCE(t.realized,0) AS realized_amount','COALESCE(o.committed,0) AS committed_amount',
                '(l.planned_amount - COALESCE(t.realized,0) - COALESCE(o.committed,0)) AS available_amount'
            ])
            ->from($this->db->quoteName('#__decarofinance_budget_lines','l'))
            ->innerJoin($this->db->quoteName('#__decarofinance_budgets','b').' ON b.id = l.budget_id')
            ->leftJoin('(SELECT budget_line_id, SUM(amount) AS realized FROM '.$this->db->quoteName('#__decarofinance_transactions').' WHERE budget_line_id IS NOT NULL GROUP BY budget_line_id) t ON t.budget_line_id = l.id')
            ->leftJoin('(SELECT budget_line_id, SUM(amount) AS committed FROM '.$this->db->quoteName('#__decarofinance_orders')." WHERE budget_line_id IS NOT NULL AND status IN ('pending','approved') GROUP BY budget_line_id) o ON o.budget_line_id = l.id")
            ->where('b.state = 1')
            ->where('b.currency = '.$this->db->quote($currency))
            ->order('b.created DESC, l.kind ASC, l.id ASC');
        return $this->db->setQuery($q)->loadAssocList();
    }

    public function getDueObligations(int $days): array
    {
        $days=max(0,min(90,$days)); $until=(new \DateTimeImmutable('today'))->modify('+'.$days.' days')->format('Y-m-d');
        $q=$this->db->getQuery(true)->select('o.*, COALESCE(SUM(a.amount),0) AS allocated_amount')->from($this->db->quoteName('#__decarofinance_obligations','o'))->leftJoin($this->db->quoteName('#__decarofinance_payment_allocations','a').' ON a.obligation_id = o.id')->where("o.status IN ('open','partial')")->where('o.due_date IS NOT NULL')->where('o.due_date <= :until')->group('o.id')->order('o.due_date ASC')->bind(':until',$until);
        return $this->db->setQuery($q)->loadAssocList();
    }

    public function transactionsByMonth(int $months=12,string $currency='EUR'): array
    {
        $months=max(1,min(60,$months)); $currency=$this->normalizeCurrency($currency); $from=(new \DateTimeImmutable('first day of this month'))->modify('-'.($months-1).' months')->format('Y-m-d 00:00:00');
        $q=$this->db->getQuery(true)->select("DATE_FORMAT(occurred_at,'%Y-%m') AS period, direction, currency, SUM(amount) AS amount")->from($this->db->quoteName('#__decarofinance_transactions'))->where('occurred_at >= :from')->where('currency = :currency')->group("DATE_FORMAT(occurred_at,'%Y-%m'), direction, currency")->order('period ASC')->bind(':from',$from)->bind(':currency',$currency);
        return $this->db->setQuery($q)->loadAssocList();
    }

    private function count(string $table,string $where): int { $q=$this->db->getQuery(true)->select('COUNT(*)')->from($this->db->quoteName($table))->where($where); return (int)$this->db->setQuery($q)->loadResult(); }
    private function sumWhere(string $table,string $column,string $where): float { $q=$this->db->getQuery(true)->select('COALESCE(SUM('.$this->db->quoteName($column).'),0)')->from($this->db->quoteName($table))->where($where); return (float)$this->db->setQuery($q)->loadResult(); }

    private function outstandingTotal(string $currency): float
    {
        $q=$this->db->getQuery(true)->select('COALESCE(SUM(o.amount - COALESCE(a.paid,0)),0)')->from($this->db->quoteName('#__decarofinance_obligations','o'))->leftJoin('(SELECT obligation_id, SUM(amount) AS paid FROM '.$this->db->quoteName('#__decarofinance_payment_allocations').' GROUP BY obligation_id) a ON a.obligation_id=o.id')->where("o.status IN ('open','partial')")->where('o.currency = '.$this->db->quote($currency));
        return (float)$this->db->setQuery($q)->loadResult();
    }

    private function accountBalanceTotal(string $currency): float
    {
        $q=$this->db->getQuery(true)->select('COALESCE(SUM(opening_balance),0)')->from($this->db->quoteName('#__decarofinance_accounts'))->where('state = 1')->where('currency = '.$this->db->quote($currency));
        $opening=(float)$this->db->setQuery($q)->loadResult();

        $q=$this->db->getQuery(true)
            ->select('COALESCE(SUM(CASE WHEN t.direction = '.$this->db->quote('income').' THEN t.amount WHEN t.direction = '.$this->db->quote('expense').' THEN -t.amount ELSE 0 END),0)')
            ->from($this->db->quoteName('#__decarofinance_transactions','t'))
            ->innerJoin($this->db->quoteName('#__decarofinance_accounts','a').' ON a.id = t.account_id')
            ->where('a.state = 1')
            ->where('a.currency = '.$this->db->quote($currency));
        $movements=(float)$this->db->setQuery($q)->loadResult();

        return round($opening+$movements,2);
    }

    private function normalizeCurrency(string $currency): string
    {
        $currency=strtoupper(trim($currency));
        return preg_match('/^[A-Z]{3}$/',$currency) ? $currency : 'EUR';
    }
}
