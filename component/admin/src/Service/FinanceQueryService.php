<?php
namespace Xdecaro\Component\Decarofinance\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

final class FinanceQueryService
{
    public function __construct(private DatabaseInterface $db) {}

    public function getSummary(): array
    {
        return [
            'open_obligations'=>$this->count("#__decarofinance_obligations","status IN ('open','partial')"),
            'overdue_obligations'=>$this->count("#__decarofinance_obligations","status IN ('open','partial') AND due_date IS NOT NULL AND due_date < CURRENT_DATE"),
            'open_amount'=>$this->outstandingTotal(),
            'payments_total'=>$this->sum('#__decarofinance_payments','amount'),
            'deposit_balance'=>$this->sum('#__decarofinance_deposit_movements','amount'),
            'budgets'=>$this->count('#__decarofinance_budgets','state = 1'),
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
        $q=$this->db->getQuery(true)->select('b.*, COALESCE(SUM(CASE WHEN l.kind = '.$this->db->quote('income').' THEN l.planned_amount ELSE 0 END),0) AS planned_income, COALESCE(SUM(CASE WHEN l.kind = '.$this->db->quote('expense').' THEN l.planned_amount ELSE 0 END),0) AS planned_expense')->from($this->db->quoteName('#__decarofinance_budgets','b'))->leftJoin($this->db->quoteName('#__decarofinance_budget_lines','l').' ON l.budget_id = b.id')->group('b.id')->order('b.created DESC');
        return $this->db->setQuery($q,0,max(1,min(500,$limit)))->loadAssocList();
    }

    public function getDueObligations(int $days): array
    {
        $days=max(0,min(90,$days)); $until=(new \DateTimeImmutable('today'))->modify('+'.$days.' days')->format('Y-m-d');
        $q=$this->db->getQuery(true)->select('o.*, COALESCE(SUM(a.amount),0) AS allocated_amount')->from($this->db->quoteName('#__decarofinance_obligations','o'))->leftJoin($this->db->quoteName('#__decarofinance_payment_allocations','a').' ON a.obligation_id = o.id')->where("o.status IN ('open','partial')")->where('o.due_date IS NOT NULL')->where('o.due_date <= :until')->group('o.id')->order('o.due_date ASC')->bind(':until',$until);
        return $this->db->setQuery($q)->loadAssocList();
    }

    public function transactionsByMonth(int $months=12): array
    {
        $months=max(1,min(60,$months)); $from=(new \DateTimeImmutable('first day of this month'))->modify('-'.($months-1).' months')->format('Y-m-d 00:00:00');
        $q=$this->db->getQuery(true)->select("DATE_FORMAT(occurred_at,'%Y-%m') AS period, direction, currency, SUM(amount) AS amount")->from($this->db->quoteName('#__decarofinance_transactions'))->where('occurred_at >= :from')->group("DATE_FORMAT(occurred_at,'%Y-%m'), direction, currency")->order('period ASC')->bind(':from',$from);
        return $this->db->setQuery($q)->loadAssocList();
    }

    private function count(string $table,string $where): int { $q=$this->db->getQuery(true)->select('COUNT(*)')->from($this->db->quoteName($table))->where($where); return (int)$this->db->setQuery($q)->loadResult(); }
    private function sum(string $table,string $column): float { $q=$this->db->getQuery(true)->select('COALESCE(SUM('.$this->db->quoteName($column).'),0)')->from($this->db->quoteName($table)); return (float)$this->db->setQuery($q)->loadResult(); }
    private function outstandingTotal(): float { $q=$this->db->getQuery(true)->select('COALESCE(SUM(o.amount - COALESCE(a.paid,0)),0)')->from($this->db->quoteName('#__decarofinance_obligations','o'))->leftJoin('(SELECT obligation_id, SUM(amount) AS paid FROM '.$this->db->quoteName('#__decarofinance_payment_allocations').' GROUP BY obligation_id) a ON a.obligation_id=o.id')->where("o.status IN ('open','partial')"); return (float)$this->db->setQuery($q)->loadResult(); }
}
