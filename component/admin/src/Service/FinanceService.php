<?php
namespace Xdecaro\Component\Decarofinance\Administrator\Service;

defined('_JEXEC') or die;

use InvalidArgumentException;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use RuntimeException;
use Throwable;

final class FinanceService
{
    private const OBLIGATION_STATUSES = ['open','partial','paid','cancelled'];
    private const DEPOSIT_TYPES = ['credit','debit','refund','adjustment','reversal'];
    private const DIRECTIONS = ['income','expense'];

    private DatabaseInterface $db;

    public function __construct(DatabaseInterface $db) { $this->db = $db; }

    public function createObligation(array $data, int $actorUserId = 0): int
    {
        $externalKey = $this->externalKey($data['external_key'] ?? null);
        if ($externalKey !== null) {
            $existing = $this->findExternal('#__decarofinance_obligations', $externalKey);
            if ($existing > 0) { return $existing; }
        }
        $amount = $this->positiveAmount($data['amount'] ?? 0, 'amount');
        $currency = $this->currency($data['currency'] ?? 'EUR');
        [$sourceComponent,$sourceEntity,$sourceId] = $this->optionalReference($data, 'source');
        [$debtorComponent,$debtorEntity,$debtorId] = $this->optionalReference($data, 'debtor');
        $kind = $this->token((string) ($data['kind'] ?? ''), 64, 'kind');
        $description = $this->nullableText($data['description'] ?? null, 500);
        $dueDate = $this->nullableDate($data['due_date'] ?? null);
        $row = (object) [
            'external_key'=>$externalKey,'source_component'=>$sourceComponent,'source_entity'=>$sourceEntity,'source_id'=>$sourceId,
            'debtor_component'=>$debtorComponent,'debtor_entity'=>$debtorEntity,'debtor_id'=>$debtorId,
            'kind'=>$kind,'description'=>$description,'amount'=>$amount,'currency'=>$currency,'due_date'=>$dueDate,'status'=>'open',
            'created'=>Factory::getDate()->toSql(),'created_by'=>max(0,$actorUserId),
        ];
        try { $this->db->insertObject('#__decarofinance_obligations', $row); }
        catch (Throwable $e) {
            if ($externalKey !== null) { $existing=$this->findExternal('#__decarofinance_obligations',$externalKey); if ($existing>0) { return $existing; } }
            throw $e;
        }
        $id=(int) $this->db->insertid(); if ($id<1) { throw new RuntimeException('Obligation was not created.'); }
        return $id;
    }

    public function cancelObligation(int $id): void
    {
        $obligation=$this->getObligation($id); if ($obligation===null) { throw new RuntimeException('Obligation not found.'); }
        if ($obligation['status']==='paid') { throw new RuntimeException('A paid obligation cannot be cancelled.'); }
        $row=(object)['id'=>$id,'status'=>'cancelled']; $this->db->updateObject('#__decarofinance_obligations',$row,'id');
    }

    public function recordPayment(array $data, int $actorUserId = 0): int
    {
        $externalKey=$this->externalKey($data['external_key'] ?? null);
        if ($externalKey!==null) { $existing=$this->findExternal('#__decarofinance_payments',$externalKey); if ($existing>0) { return $existing; } }
        $amount=$this->positiveAmount($data['amount'] ?? 0,'amount'); $currency=$this->currency($data['currency'] ?? 'EUR');
        [$payerComponent,$payerEntity,$payerId]=$this->optionalReference($data,'payer');
        $method=$this->nullableToken($data['method'] ?? null,64,'method'); $reference=$this->nullableText($data['reference'] ?? null,191);
        $paidAt=$this->nullableDateTime($data['paid_at'] ?? null) ?? Factory::getDate()->toSql();
        $row=(object)['external_key'=>$externalKey,'payer_component'=>$payerComponent,'payer_entity'=>$payerEntity,'payer_id'=>$payerId,'amount'=>$amount,'currency'=>$currency,'paid_at'=>$paidAt,'method'=>$method,'reference'=>$reference,'created'=>Factory::getDate()->toSql(),'created_by'=>max(0,$actorUserId)];
        try { $this->db->insertObject('#__decarofinance_payments',$row); }
        catch (Throwable $e) { if ($externalKey!==null) { $existing=$this->findExternal('#__decarofinance_payments',$externalKey); if ($existing>0) { return $existing; } } throw $e; }
        $id=(int)$this->db->insertid(); if ($id<1) { throw new RuntimeException('Payment was not created.'); } return $id;
    }

    public function allocatePayment(int $paymentId, int $obligationId, float|string $amount): void
    {
        $amount=$this->positiveAmount($amount,'amount');
        $this->db->transactionStart();
        try {
            $payment=$this->getPayment($paymentId); $obligation=$this->getObligation($obligationId);
            if ($payment===null || $obligation===null) { throw new RuntimeException('Payment or obligation not found.'); }
            if ($obligation['status']==='cancelled') { throw new RuntimeException('Cannot allocate to a cancelled obligation.'); }
            if ($payment['currency']!==$obligation['currency']) { throw new RuntimeException('Payment and obligation currencies differ.'); }
            $existing=$this->allocationAmount($paymentId,$obligationId); if ($existing>0) { throw new RuntimeException('Payment is already allocated to this obligation.'); }
            $available=(float)$payment['amount']-$this->sumAllocationsForPayment($paymentId);
            $outstanding=(float)$obligation['amount']-$this->sumAllocationsForObligation($obligationId);
            if ($amount>$available+0.0001 || $amount>$outstanding+0.0001) { throw new RuntimeException('Allocation exceeds available payment or obligation balance.'); }
            $allocation=(object)['payment_id'=>$paymentId,'obligation_id'=>$obligationId,'amount'=>$amount];
            $this->db->insertObject('#__decarofinance_payment_allocations',$allocation);
            $paid=$this->sumAllocationsForObligation($obligationId); $status=$paid+0.0001 >= (float)$obligation['amount'] ? 'paid' : ($paid>0 ? 'partial' : 'open');
            $statusRow=(object)['id'=>$obligationId,'status'=>$status];
            $this->db->updateObject('#__decarofinance_obligations',$statusRow,'id');
            $this->db->transactionCommit();
        } catch (Throwable $e) { $this->db->transactionRollback(); throw $e; }
    }

    public function getOrCreateDepositAccount(string $ownerComponent, string $ownerEntity, int|string $ownerId, string $currency='EUR'): int
    {
        $ownerComponent=$this->component($ownerComponent); $ownerEntity=$this->token($ownerEntity,100,'owner_entity'); $ownerId=$this->identifier($ownerId,'owner_id'); $currency=$this->currency($currency);
        $query=$this->db->getQuery(true)->select($this->db->quoteName('id'))->from($this->db->quoteName('#__decarofinance_deposit_accounts'))->where($this->db->quoteName('owner_component').' = :c')->where($this->db->quoteName('owner_entity').' = :e')->where($this->db->quoteName('owner_id').' = :i')->where($this->db->quoteName('currency').' = :cur')->bind(':c',$ownerComponent)->bind(':e',$ownerEntity)->bind(':i',$ownerId)->bind(':cur',$currency);
        $id=(int)$this->db->setQuery($query,0,1)->loadResult(); if ($id>0) { return $id; }
        $row=(object)['owner_component'=>$ownerComponent,'owner_entity'=>$ownerEntity,'owner_id'=>$ownerId,'currency'=>$currency,'state'=>1];
        try { $this->db->insertObject('#__decarofinance_deposit_accounts',$row); }
        catch (Throwable $e) { $id=(int)$this->db->setQuery($query,0,1)->loadResult(); if ($id>0) { return $id; } throw $e; }
        return (int)$this->db->insertid();
    }

    public function postDepositMovement(int $accountId, string $movementType, float|string $amount, array $meta=[], int $actorUserId=0): int
    {
        if ($accountId<1) { throw new InvalidArgumentException('Invalid deposit account.'); }
        $movementType=strtolower(trim($movementType)); if (!in_array($movementType,self::DEPOSIT_TYPES,true)) { throw new InvalidArgumentException('Invalid deposit movement type.'); }
        $amount=$this->nonZeroAmount($amount,'amount'); $externalKey=$this->externalKey($meta['external_key'] ?? null);
        if ($externalKey!==null) { $existing=$this->findExternal('#__decarofinance_deposit_movements',$externalKey); if ($existing>0) { return $existing; } }
        [$sourceComponent,$sourceEntity,$sourceId]=$this->optionalReference($meta,'source');
        $row=(object)['account_id'=>$accountId,'external_key'=>$externalKey,'movement_type'=>$movementType,'amount'=>$amount,'description'=>$this->nullableText($meta['description'] ?? null,500),'source_component'=>$sourceComponent,'source_entity'=>$sourceEntity,'source_id'=>$sourceId,'created'=>Factory::getDate()->toSql(),'created_by'=>max(0,$actorUserId)];
        try { $this->db->insertObject('#__decarofinance_deposit_movements',$row); }
        catch (Throwable $e) { if ($externalKey!==null) { $existing=$this->findExternal('#__decarofinance_deposit_movements',$externalKey); if ($existing>0) { return $existing; } } throw $e; }
        return (int)$this->db->insertid();
    }

    public function getDepositBalance(int $accountId): float
    {
        $id=$accountId; $q=$this->db->getQuery(true)->select('COALESCE(SUM('.$this->db->quoteName('amount').'),0)')->from($this->db->quoteName('#__decarofinance_deposit_movements'))->where($this->db->quoteName('account_id').' = :id')->bind(':id',$id,ParameterType::INTEGER); return (float)$this->db->setQuery($q)->loadResult();
    }

    public function recordTransaction(array $data): int
    {
        $externalKey=$this->externalKey($data['external_key'] ?? null); if ($externalKey!==null) { $existing=$this->findExternal('#__decarofinance_transactions',$externalKey); if ($existing>0) { return $existing; } }
        $direction=strtolower(trim((string)($data['direction'] ?? ''))); if (!in_array($direction,self::DIRECTIONS,true)) { throw new InvalidArgumentException('Invalid transaction direction.'); }
        [$sourceComponent,$sourceEntity,$sourceId]=$this->optionalReference($data,'source');
        $row=(object)['external_key'=>$externalKey,'direction'=>$direction,'amount'=>$this->positiveAmount($data['amount'] ?? 0,'amount'),'currency'=>$this->currency($data['currency'] ?? 'EUR'),'occurred_at'=>$this->nullableDateTime($data['occurred_at'] ?? null) ?? Factory::getDate()->toSql(),'source_component'=>$sourceComponent,'source_entity'=>$sourceEntity,'source_id'=>$sourceId,'description'=>$this->nullableText($data['description'] ?? null,500)];
        try { $this->db->insertObject('#__decarofinance_transactions',$row); } catch (Throwable $e) { if ($externalKey!==null) { $existing=$this->findExternal('#__decarofinance_transactions',$externalKey); if ($existing>0) { return $existing; } } throw $e; }
        return (int)$this->db->insertid();
    }

    public function createBudget(string $title, ?string $start=null, ?string $end=null, int $actorUserId=0): int
    {
        $title=trim($title); if ($title==='' || mb_strlen($title)>255) { throw new InvalidArgumentException('Budget title is required.'); }
        $row=(object)['title'=>$title,'period_start'=>$this->nullableDate($start),'period_end'=>$this->nullableDate($end),'state'=>1,'created'=>Factory::getDate()->toSql(),'created_by'=>max(0,$actorUserId)];
        $this->db->insertObject('#__decarofinance_budgets',$row); return (int)$this->db->insertid();
    }

    public function addBudgetLine(int $budgetId, string $kind, string $title, float|string $plannedAmount): int
    {
        if ($budgetId<1) { throw new InvalidArgumentException('Invalid budget.'); } $kind=strtolower(trim($kind)); if (!in_array($kind,['income','expense'],true)) { throw new InvalidArgumentException('Invalid budget line kind.'); }
        $title=trim($title); if ($title==='') { throw new InvalidArgumentException('Budget line title is required.'); }
        $amount=$this->nonNegativeAmount($plannedAmount,'planned_amount');
        $row=(object)['budget_id'=>$budgetId,'kind'=>$kind,'title'=>mb_substr($title,0,255),'planned_amount'=>$amount];
        $this->db->insertObject('#__decarofinance_budget_lines',$row); return (int)$this->db->insertid();
    }

    public function getObligation(int $id): ?array { return $this->findById('#__decarofinance_obligations',$id); }
    public function getPayment(int $id): ?array { return $this->findById('#__decarofinance_payments',$id); }

    private function findById(string $table,int $id): ?array { if ($id<1) return null; $v=$id; $q=$this->db->getQuery(true)->select('*')->from($this->db->quoteName($table))->where($this->db->quoteName('id').' = :id')->bind(':id',$v,ParameterType::INTEGER); $r=$this->db->setQuery($q,0,1)->loadAssoc(); return $r ?: null; }
    private function findExternal(string $table,string $key): int { $q=$this->db->getQuery(true)->select($this->db->quoteName('id'))->from($this->db->quoteName($table))->where($this->db->quoteName('external_key').' = :k')->bind(':k',$key); return (int)$this->db->setQuery($q,0,1)->loadResult(); }
    private function allocationAmount(int $p,int $o): float { $q=$this->db->getQuery(true)->select('COALESCE(SUM('.$this->db->quoteName('amount').'),0)')->from($this->db->quoteName('#__decarofinance_payment_allocations'))->where($this->db->quoteName('payment_id').' = '.(int)$p)->where($this->db->quoteName('obligation_id').' = '.(int)$o); return (float)$this->db->setQuery($q)->loadResult(); }
    private function sumAllocationsForPayment(int $id): float { $q=$this->db->getQuery(true)->select('COALESCE(SUM('.$this->db->quoteName('amount').'),0)')->from($this->db->quoteName('#__decarofinance_payment_allocations'))->where($this->db->quoteName('payment_id').' = '.(int)$id); return (float)$this->db->setQuery($q)->loadResult(); }
    private function sumAllocationsForObligation(int $id): float { $q=$this->db->getQuery(true)->select('COALESCE(SUM('.$this->db->quoteName('amount').'),0)')->from($this->db->quoteName('#__decarofinance_payment_allocations'))->where($this->db->quoteName('obligation_id').' = '.(int)$id); return (float)$this->db->setQuery($q)->loadResult(); }
    private function optionalReference(array $data,string $prefix): array { $c=trim((string)($data[$prefix.'_component'] ?? '')); $e=trim((string)($data[$prefix.'_entity'] ?? '')); $i=trim((string)($data[$prefix.'_id'] ?? '')); if ($c===''&&$e===''&&$i==='') return [null,null,null]; if ($c===''||$e===''||$i==='') throw new InvalidArgumentException($prefix.' reference must be complete.'); return [$this->component($c),$this->token($e,100,$prefix.'_entity'),$this->identifier($i,$prefix.'_id')]; }
    private function component(string $v): string { $v=strtolower(trim($v)); if (!preg_match('/^com_[a-z0-9][a-z0-9_]*$/',$v)) throw new InvalidArgumentException('Invalid component reference.'); return $v; }
    private function token(string $v,int $max,string $name): string { $v=trim($v); if ($v===''||strlen($v)>$max||!preg_match('/^[A-Za-z0-9_.:-]+$/',$v)) throw new InvalidArgumentException('Invalid '.$name.'.'); return $v; }
    private function nullableToken(mixed $v,int $max,string $name): ?string { $v=trim((string)$v); return $v===''?null:$this->token($v,$max,$name); }
    private function identifier(int|string $v,string $name): string { $v=trim((string)$v); if ($v===''||strlen($v)>191||!preg_match('/^[A-Za-z0-9_.:@-]+$/',$v)) throw new InvalidArgumentException('Invalid '.$name.'.'); return $v; }
    private function externalKey(mixed $v): ?string { $v=trim((string)$v); if ($v==='') return null; if (strlen($v)>191) throw new InvalidArgumentException('external_key too long.'); return $v; }
    private function currency(mixed $v): string { $v=strtoupper(trim((string)$v)); if (!preg_match('/^[A-Z]{3}$/',$v)) throw new InvalidArgumentException('Invalid currency.'); return $v; }
    private function positiveAmount(mixed $v,string $name): float { $n=$this->number($v,$name); if ($n<=0) throw new InvalidArgumentException($name.' must be greater than zero.'); return round($n,2); }
    private function nonZeroAmount(mixed $v,string $name): float { $n=$this->number($v,$name); if (abs($n)<0.005) throw new InvalidArgumentException($name.' must be non-zero.'); return round($n,2); }
    private function nonNegativeAmount(mixed $v,string $name): float { $n=$this->number($v,$name); if ($n<0) throw new InvalidArgumentException($name.' must not be negative.'); return round($n,2); }
    private function number(mixed $v,string $name): float { if (!is_numeric($v)) throw new InvalidArgumentException('Invalid '.$name.'.'); return (float)$v; }
    private function nullableText(mixed $v,int $max): ?string { $v=trim((string)$v); if ($v==='') return null; if (mb_strlen($v)>$max) $v=mb_substr($v,0,$max); return $v; }
    private function nullableDate(mixed $v): ?string { $v=trim((string)$v); if ($v==='') return null; $d=\DateTimeImmutable::createFromFormat('!Y-m-d',$v); if (!$d||$d->format('Y-m-d')!==$v) throw new InvalidArgumentException('Invalid date.'); return $v; }
    private function nullableDateTime(mixed $v): ?string { $v=trim((string)$v); if ($v==='') return null; try { return Factory::getDate($v)->toSql(); } catch (Throwable) { throw new InvalidArgumentException('Invalid date/time.'); } }
}
