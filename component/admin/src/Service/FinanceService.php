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
    private const ACCOUNT_TYPES = ['bank','cash','payment','other'];
    private const ORDER_STATUSES = ['draft','pending','approved','executed','cancelled'];

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

    /**
     * Create or update an obligation identified by external_key.
     *
     * An unchanged replay is always safe. Mutable fields may be updated only
     * while the obligation is open and has no payment allocation; once money
     * has been allocated or the obligation is closed, changed financial data is
     * rejected instead of silently rewriting history.
     */
    public function upsertObligation(array $data, int $actorUserId = 0): int
    {
        $externalKey = $this->externalKey($data['external_key'] ?? null);
        if ($externalKey === null) { throw new InvalidArgumentException('external_key is required for obligation upsert.'); }

        $id = $this->findExternal('#__decarofinance_obligations', $externalKey);
        if ($id < 1) { $id = $this->createObligation($data, $actorUserId); }

        $current = $this->getObligation($id);
        if ($current === null) { throw new RuntimeException('Obligation not found after upsert.'); }

        $amount = $this->positiveAmount($data['amount'] ?? 0, 'amount');
        $currency = $this->currency($data['currency'] ?? 'EUR');
        [$sourceComponent,$sourceEntity,$sourceId] = $this->optionalReference($data, 'source');
        [$debtorComponent,$debtorEntity,$debtorId] = $this->optionalReference($data, 'debtor');
        $kind = $this->token((string) ($data['kind'] ?? ''), 64, 'kind');
        $description = $this->nullableText($data['description'] ?? null, 500);
        $dueDate = $this->nullableDate($data['due_date'] ?? null);

        if ($this->obligationMatches($current, $sourceComponent, $sourceEntity, $sourceId, $debtorComponent, $debtorEntity, $debtorId, $kind, $description, $amount, $currency, $dueDate)) {
            return $id;
        }

        if (($current['status'] ?? '') !== 'open' || $this->sumAllocationsForObligation($id) > 0.0001) {
            throw new RuntimeException('Allocated or closed obligation cannot be changed by upsert.');
        }

        $row = (object) [
            'id'=>$id,
            'source_component'=>$sourceComponent,'source_entity'=>$sourceEntity,'source_id'=>$sourceId,
            'debtor_component'=>$debtorComponent,'debtor_entity'=>$debtorEntity,'debtor_id'=>$debtorId,
            'kind'=>$kind,'description'=>$description,'amount'=>$amount,'currency'=>$currency,'due_date'=>$dueDate,
        ];
        $this->db->updateObject('#__decarofinance_obligations', $row, 'id');
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

    /** Replay-safe payment upsert. Changed payment data is rejected after allocation. */
    public function upsertPayment(array $data, int $actorUserId = 0): int
    {
        $externalKey = $this->externalKey($data['external_key'] ?? null);
        if ($externalKey === null) { throw new InvalidArgumentException('external_key is required for payment upsert.'); }

        $id = $this->findExternal('#__decarofinance_payments', $externalKey);
        if ($id < 1) { $id = $this->recordPayment($data, $actorUserId); }

        $current = $this->getPayment($id);
        if ($current === null) { throw new RuntimeException('Payment not found after upsert.'); }

        $amount=$this->positiveAmount($data['amount'] ?? 0,'amount');
        $currency=$this->currency($data['currency'] ?? 'EUR');
        [$payerComponent,$payerEntity,$payerId]=$this->optionalReference($data,'payer');
        $method=$this->nullableToken($data['method'] ?? null,64,'method');
        $reference=$this->nullableText($data['reference'] ?? null,191);
        $paidAt=$this->nullableDateTime($data['paid_at'] ?? null) ?? (string) ($current['paid_at'] ?? Factory::getDate()->toSql());

        if ($this->paymentMatches($current, $payerComponent, $payerEntity, $payerId, $amount, $currency, $paidAt, $method, $reference)) {
            return $id;
        }

        if ($this->sumAllocationsForPayment($id) > 0.0001) {
            throw new RuntimeException('Allocated payment cannot be changed by upsert.');
        }

        $row=(object)['id'=>$id,'payer_component'=>$payerComponent,'payer_entity'=>$payerEntity,'payer_id'=>$payerId,'amount'=>$amount,'currency'=>$currency,'paid_at'=>$paidAt,'method'=>$method,'reference'=>$reference];
        $this->db->updateObject('#__decarofinance_payments',$row,'id');
        return $id;
    }

    public function allocatePayment(int $paymentId, int $obligationId, float|string $amount): void
    {
        $this->allocatePaymentInternal($paymentId, $obligationId, $amount, false);
    }

    /**
     * Allocate a payment exactly once. Replaying the same payment/obligation/
     * amount tuple is a no-op; a conflicting amount remains an error.
     */
    public function allocatePaymentIdempotent(int $paymentId, int $obligationId, float|string $amount): void
    {
        $this->allocatePaymentInternal($paymentId, $obligationId, $amount, true);
    }

    private function allocatePaymentInternal(int $paymentId, int $obligationId, float|string $amount, bool $idempotent): void
    {
        $amount=$this->positiveAmount($amount,'amount');
        $this->db->transactionStart();
        try {
            $payment=$this->getPayment($paymentId); $obligation=$this->getObligation($obligationId);
            if ($payment===null || $obligation===null) { throw new RuntimeException('Payment or obligation not found.'); }
            if ($obligation['status']==='cancelled') { throw new RuntimeException('Cannot allocate to a cancelled obligation.'); }
            if ($payment['currency']!==$obligation['currency']) { throw new RuntimeException('Payment and obligation currencies differ.'); }
            $existing=$this->allocationAmount($paymentId,$obligationId);
            if ($existing>0) {
                if (!$idempotent) { throw new RuntimeException('Payment is already allocated to this obligation.'); }
                if (abs($existing - $amount) > 0.0001) { throw new RuntimeException('Existing payment allocation has a different amount.'); }
                $this->refreshObligationStatus($obligationId, $obligation);
                $this->db->transactionCommit();
                return;
            }
            $available=(float)$payment['amount']-$this->sumAllocationsForPayment($paymentId);
            $outstanding=(float)$obligation['amount']-$this->sumAllocationsForObligation($obligationId);
            if ($amount>$available+0.0001 || $amount>$outstanding+0.0001) { throw new RuntimeException('Allocation exceeds available payment or obligation balance.'); }
            $allocation=(object)['payment_id'=>$paymentId,'obligation_id'=>$obligationId,'amount'=>$amount];
            $this->db->insertObject('#__decarofinance_payment_allocations',$allocation);
            $this->refreshObligationStatus($obligationId, $obligation);
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

    public function createAccount(array $data, int $actorUserId=0): int
    {
        $externalKey=$this->externalKey($data['external_key'] ?? null);
        if ($externalKey!==null) { $existing=$this->findExternal('#__decarofinance_accounts',$externalKey); if ($existing>0) { return $existing; } }
        [$ownerComponent,$ownerEntity,$ownerId]=$this->optionalReference($data,'owner');
        $name=trim((string)($data['name'] ?? '')); if ($name==='' || mb_strlen($name)>255) { throw new InvalidArgumentException('Account name is required.'); }
        $type=strtolower(trim((string)($data['account_type'] ?? 'bank'))); if (!in_array($type,self::ACCOUNT_TYPES,true)) { throw new InvalidArgumentException('Invalid account type.'); }
        $row=(object)[
            'external_key'=>$externalKey,'owner_component'=>$ownerComponent,'owner_entity'=>$ownerEntity,'owner_id'=>$ownerId,
            'name'=>$name,'account_type'=>$type,'identifier'=>$this->nullableText($data['identifier'] ?? null,191),
            'currency'=>$this->currency($data['currency'] ?? 'EUR'),'opening_balance'=>round($this->number($data['opening_balance'] ?? 0,'opening_balance'),2),
            'state'=>1,'created'=>Factory::getDate()->toSql(),'created_by'=>max(0,$actorUserId),
        ];
        try { $this->db->insertObject('#__decarofinance_accounts',$row); }
        catch (Throwable $e) { if ($externalKey!==null) { $existing=$this->findExternal('#__decarofinance_accounts',$externalKey); if ($existing>0) { return $existing; } } throw $e; }
        $id=(int)$this->db->insertid(); if ($id<1) { throw new RuntimeException('Financial account was not created.'); } return $id;
    }

    public function getAccount(int $id): ?array { return $this->findById('#__decarofinance_accounts',$id); }

    public function recordTransaction(array $data, int $actorUserId=0): int
    {
        $externalKey=$this->externalKey($data['external_key'] ?? null); if ($externalKey!==null) { $existing=$this->findExternal('#__decarofinance_transactions',$externalKey); if ($existing>0) { return $existing; } }
        $direction=strtolower(trim((string)($data['direction'] ?? ''))); if (!in_array($direction,self::DIRECTIONS,true)) { throw new InvalidArgumentException('Invalid transaction direction.'); }
        $currency=$this->currency($data['currency'] ?? 'EUR');
        $accountId=max(0,(int)($data['account_id'] ?? 0));
        if ($accountId>0) {
            $account=$this->getAccount($accountId);
            if ($account===null || (int)($account['state'] ?? 0)!==1) { throw new InvalidArgumentException('Financial account is unavailable.'); }
            if ((string)$account['currency']!==$currency) { throw new InvalidArgumentException('Transaction currency differs from account currency.'); }
        }
        $budgetLineId=max(0,(int)($data['budget_line_id'] ?? 0));
        if ($budgetLineId>0) {
            $ctx=$this->budgetLineContext($budgetLineId);
            if ($ctx===null) { throw new InvalidArgumentException('Budget line not found.'); }
            if ((string)$ctx['kind']!==$direction) { throw new InvalidArgumentException('Transaction direction differs from budget line kind.'); }
            if ((string)$ctx['currency']!==$currency) { throw new InvalidArgumentException('Transaction currency differs from budget currency.'); }
        }
        [$sourceComponent,$sourceEntity,$sourceId]=$this->optionalReference($data,'source');
        [$counterpartyComponent,$counterpartyEntity,$counterpartyId]=$this->optionalReference($data,'counterparty');
        [$evidenceComponent,$evidenceEntity,$evidenceId]=$this->optionalReference($data,'evidence');
        $row=(object)[
            'external_key'=>$externalKey,'account_id'=>$accountId?:null,'budget_line_id'=>$budgetLineId?:null,'direction'=>$direction,
            'category'=>$this->nullableToken($data['category'] ?? null,100,'category'),'amount'=>$this->positiveAmount($data['amount'] ?? 0,'amount'),
            'currency'=>$currency,'occurred_at'=>$this->nullableDateTime($data['occurred_at'] ?? null) ?? Factory::getDate()->toSql(),
            'source_component'=>$sourceComponent,'source_entity'=>$sourceEntity,'source_id'=>$sourceId,
            'counterparty_component'=>$counterpartyComponent,'counterparty_entity'=>$counterpartyEntity,'counterparty_id'=>$counterpartyId,
            'evidence_component'=>$evidenceComponent,'evidence_entity'=>$evidenceEntity,'evidence_id'=>$evidenceId,
            'description'=>$this->nullableText($data['description'] ?? null,500),'created'=>Factory::getDate()->toSql(),'created_by'=>max(0,$actorUserId),
        ];
        try { $this->db->insertObject('#__decarofinance_transactions',$row); } catch (Throwable $e) { if ($externalKey!==null) { $existing=$this->findExternal('#__decarofinance_transactions',$externalKey); if ($existing>0) { return $existing; } } throw $e; }
        $id=(int)$this->db->insertid(); if ($id<1) { throw new RuntimeException('Transaction was not created.'); } return $id;
    }

    public function createOrder(array $data, int $actorUserId=0): int
    {
        $externalKey=$this->externalKey($data['external_key'] ?? null);
        if ($externalKey!==null) { $existing=$this->findExternal('#__decarofinance_orders',$externalKey); if ($existing>0) { return $existing; } }
        $direction=strtolower(trim((string)($data['direction'] ?? ''))); if (!in_array($direction,self::DIRECTIONS,true)) { throw new InvalidArgumentException('Invalid order direction.'); }
        $currency=$this->currency($data['currency'] ?? 'EUR');
        $accountId=max(0,(int)($data['account_id'] ?? 0));
        if ($accountId>0) { $account=$this->getAccount($accountId); if ($account===null || (int)($account['state'] ?? 0)!==1) { throw new InvalidArgumentException('Financial account is unavailable.'); } if ((string)$account['currency']!==$currency) { throw new InvalidArgumentException('Order currency differs from account currency.'); } }
        $budgetLineId=max(0,(int)($data['budget_line_id'] ?? 0));
        if ($budgetLineId>0) { $ctx=$this->budgetLineContext($budgetLineId); if ($ctx===null) { throw new InvalidArgumentException('Budget line not found.'); } if ((string)$ctx['kind']!==$direction) { throw new InvalidArgumentException('Order direction differs from budget line kind.'); } if ((string)$ctx['currency']!==$currency) { throw new InvalidArgumentException('Order currency differs from budget currency.'); } }
        [$ownerComponent,$ownerEntity,$ownerId]=$this->optionalReference($data,'owner');
        [$counterpartyComponent,$counterpartyEntity,$counterpartyId]=$this->optionalReference($data,'counterparty');
        [$sourceComponent,$sourceEntity,$sourceId]=$this->optionalReference($data,'source');
        $required=(int)($data['required_approvals'] ?? 2); if ($required<1 || $required>5) { throw new InvalidArgumentException('required_approvals must be between 1 and 5.'); }
        $row=(object)[
            'external_key'=>$externalKey,'direction'=>$direction,'account_id'=>$accountId?:null,'budget_line_id'=>$budgetLineId?:null,
            'owner_component'=>$ownerComponent,'owner_entity'=>$ownerEntity,'owner_id'=>$ownerId,
            'counterparty_component'=>$counterpartyComponent,'counterparty_entity'=>$counterpartyEntity,'counterparty_id'=>$counterpartyId,
            'category'=>$this->nullableToken($data['category'] ?? null,100,'category'),'description'=>$this->nullableText($data['description'] ?? null,500),
            'amount'=>$this->positiveAmount($data['amount'] ?? 0,'amount'),'currency'=>$currency,'due_date'=>$this->nullableDate($data['due_date'] ?? null),
            'required_approvals'=>$required,'status'=>'draft','source_component'=>$sourceComponent,'source_entity'=>$sourceEntity,'source_id'=>$sourceId,
            'approved_at'=>null,'executed_at'=>null,'transaction_id'=>null,'created'=>Factory::getDate()->toSql(),'created_by'=>max(0,$actorUserId),
        ];
        try { $this->db->insertObject('#__decarofinance_orders',$row); }
        catch (Throwable $e) { if ($externalKey!==null) { $existing=$this->findExternal('#__decarofinance_orders',$externalKey); if ($existing>0) { return $existing; } } throw $e; }
        $id=(int)$this->db->insertid(); if ($id<1) { throw new RuntimeException('Financial order was not created.'); } return $id;
    }

    public function getOrder(int $id): ?array { return $this->findById('#__decarofinance_orders',$id); }

    public function approveOrder(int $orderId, int $step, ?string $role, int $userId, ?string $note=null): void
    {
        if ($userId<1) { throw new InvalidArgumentException('A valid approving user is required.'); }
        $order=$this->getOrder($orderId); if ($order===null) { throw new RuntimeException('Financial order not found.'); }
        if (!in_array((string)$order['status'],['draft','pending'],true)) { throw new RuntimeException('Financial order cannot be approved in its current state.'); }
        $count=$this->approvalCount($orderId); $expected=$count+1;
        if ($step!==$expected || $step<1 || $step>(int)$order['required_approvals']) { throw new InvalidArgumentException('Approval step is not valid for this order.'); }
        if ($step===(int)$order['required_approvals'] && (string)$order['direction']==='expense' && (int)($order['budget_line_id'] ?? 0)>0) {
            $usage=$this->getBudgetLineAvailability((int)$order['budget_line_id'],$orderId);
            if ((float)$order['amount']>$usage['available']+0.0001) { throw new RuntimeException('Insufficient budget coverage for this expense order.'); }
        }
        $approval=(object)['order_id'=>$orderId,'step'=>$step,'role'=>$this->nullableToken($role,64,'role'),'user_id'=>$userId,'decision'=>'approved','note'=>$this->nullableText($note,500),'decided_at'=>Factory::getDate()->toSql()];
        $this->db->insertObject('#__decarofinance_order_approvals',$approval);
        $final=$step===(int)$order['required_approvals'];
        $row=(object)['id'=>$orderId,'status'=>$final?'approved':'pending','approved_at'=>$final?Factory::getDate()->toSql():null];
        $this->db->updateObject('#__decarofinance_orders',$row,'id');
    }

    public function executeOrder(int $orderId, int $actorUserId=0): int
    {
        $order=$this->getOrder($orderId); if ($order===null) { throw new RuntimeException('Financial order not found.'); }
        if ((string)$order['status']==='executed') { return (int)($order['transaction_id'] ?? 0); }
        if ((string)$order['status']!=='approved') { throw new RuntimeException('Only approved financial orders can be executed.'); }
        $accountId=(int)($order['account_id'] ?? 0); if ($accountId<1) { throw new RuntimeException('Financial order has no account.'); }
        $sourceComponent=(string)($order['source_component'] ?? ''); $sourceEntity=(string)($order['source_entity'] ?? ''); $sourceId=(string)($order['source_id'] ?? '');
        if ($sourceComponent==='' || $sourceEntity==='' || $sourceId==='') { $sourceComponent='com_decarofinance'; $sourceEntity='order'; $sourceId=(string)$orderId; }
        $transactionId=$this->recordTransaction([
            'external_key'=>'finance:order:'.$orderId,'account_id'=>$accountId,'budget_line_id'=>(int)($order['budget_line_id'] ?? 0),
            'direction'=>$order['direction'],'category'=>$order['category'],'amount'=>$order['amount'],'currency'=>$order['currency'],
            'source_component'=>$sourceComponent,'source_entity'=>$sourceEntity,'source_id'=>$sourceId,
            'counterparty_component'=>$order['counterparty_component'],'counterparty_entity'=>$order['counterparty_entity'],'counterparty_id'=>$order['counterparty_id'],
            'description'=>$order['description'],
        ],$actorUserId);
        $row=(object)['id'=>$orderId,'status'=>'executed','executed_at'=>Factory::getDate()->toSql(),'transaction_id'=>$transactionId];
        $this->db->updateObject('#__decarofinance_orders',$row,'id');
        return $transactionId;
    }

    public function cancelOrder(int $orderId): void
    {
        $order=$this->getOrder($orderId); if ($order===null) { throw new RuntimeException('Financial order not found.'); }
        if ((string)$order['status']==='executed') { throw new RuntimeException('Executed financial orders cannot be cancelled.'); }
        if ((string)$order['status']==='cancelled') { return; }
        $row=(object)['id'=>$orderId,'status'=>'cancelled']; $this->db->updateObject('#__decarofinance_orders',$row,'id');
    }

    public function getBudgetLineAvailability(int $budgetLineId, int $excludeOrderId=0): array
    {
        $ctx=$this->budgetLineContext($budgetLineId); if ($ctx===null) { throw new InvalidArgumentException('Budget line not found.'); }
        $id=$budgetLineId;
        $q=$this->db->getQuery(true)->select('COALESCE(SUM('.$this->db->quoteName('amount').'),0)')->from($this->db->quoteName('#__decarofinance_transactions'))->where($this->db->quoteName('budget_line_id').' = :id')->bind(':id',$id,ParameterType::INTEGER);
        $realized=(float)$this->db->setQuery($q)->loadResult();
        $q=$this->db->getQuery(true)->select('COALESCE(SUM('.$this->db->quoteName('amount').'),0)')->from($this->db->quoteName('#__decarofinance_orders'))->where($this->db->quoteName('budget_line_id').' = :id')->where($this->db->quoteName('status')." IN ('pending','approved')")->bind(':id',$id,ParameterType::INTEGER);
        if ($excludeOrderId>0) { $q->where($this->db->quoteName('id').' <> '.(int)$excludeOrderId); }
        $committed=(float)$this->db->setQuery($q)->loadResult();
        $planned=(float)$ctx['planned_amount'];
        return ['planned'=>$planned,'realized'=>$realized,'committed'=>$committed,'available'=>round($planned-$realized-$committed,2),'currency'=>(string)$ctx['currency']];
    }

    public function createBudget(string $title, ?string $start=null, ?string $end=null, int $actorUserId=0, array $meta=[]): int
    {
        $title=trim($title); if ($title==='' || mb_strlen($title)>255) { throw new InvalidArgumentException('Budget title is required.'); }
        [$ownerComponent,$ownerEntity,$ownerId]=$this->optionalReference($meta,'owner');
        $row=(object)['title'=>$title,'period_start'=>$this->nullableDate($start),'period_end'=>$this->nullableDate($end),'owner_component'=>$ownerComponent,'owner_entity'=>$ownerEntity,'owner_id'=>$ownerId,'currency'=>$this->currency($meta['currency'] ?? 'EUR'),'state'=>1,'created'=>Factory::getDate()->toSql(),'created_by'=>max(0,$actorUserId)];
        $this->db->insertObject('#__decarofinance_budgets',$row); return (int)$this->db->insertid();
    }

    public function addBudgetLine(int $budgetId, string $kind, string $title, float|string $plannedAmount, array $meta=[]): int
    {
        if ($budgetId<1) { throw new InvalidArgumentException('Invalid budget.'); } $kind=strtolower(trim($kind)); if (!in_array($kind,['income','expense'],true)) { throw new InvalidArgumentException('Invalid budget line kind.'); }
        $title=trim($title); if ($title==='') { throw new InvalidArgumentException('Budget line title is required.'); }
        $amount=$this->nonNegativeAmount($plannedAmount,'planned_amount');
        $row=(object)['budget_id'=>$budgetId,'kind'=>$kind,'code'=>$this->nullableToken($meta['code'] ?? null,64,'code'),'category'=>$this->nullableToken($meta['category'] ?? null,100,'category'),'title'=>mb_substr($title,0,255),'planned_amount'=>$amount];
        $this->db->insertObject('#__decarofinance_budget_lines',$row); return (int)$this->db->insertid();
    }

    public function getObligation(int $id): ?array { return $this->findById('#__decarofinance_obligations',$id); }
    public function getPayment(int $id): ?array { return $this->findById('#__decarofinance_payments',$id); }

    private function approvalCount(int $orderId): int
    {
        $id=$orderId; $q=$this->db->getQuery(true)->select('COUNT(*)')->from($this->db->quoteName('#__decarofinance_order_approvals'))->where($this->db->quoteName('order_id').' = :id')->where($this->db->quoteName('decision').' = '.$this->db->quote('approved'))->bind(':id',$id,ParameterType::INTEGER);
        return (int)$this->db->setQuery($q)->loadResult();
    }

    private function budgetLineContext(int $budgetLineId): ?array
    {
        if ($budgetLineId<1) { return null; }
        $id=$budgetLineId;
        $q=$this->db->getQuery(true)->select(['l.*','b.currency'])->from($this->db->quoteName('#__decarofinance_budget_lines','l'))->innerJoin($this->db->quoteName('#__decarofinance_budgets','b').' ON b.id = l.budget_id')->where('l.id = :id')->bind(':id',$id,ParameterType::INTEGER);
        $row=$this->db->setQuery($q,0,1)->loadAssoc();
        return $row ?: null;
    }

    private function refreshObligationStatus(int $obligationId, array $obligation): void
    {
        $paid=$this->sumAllocationsForObligation($obligationId);
        $status=$paid+0.0001 >= (float)$obligation['amount'] ? 'paid' : ($paid>0 ? 'partial' : 'open');
        if (($obligation['status'] ?? '') === $status) { return; }
        $statusRow=(object)['id'=>$obligationId,'status'=>$status];
        $this->db->updateObject('#__decarofinance_obligations',$statusRow,'id');
    }

    private function obligationMatches(array $current, ?string $sourceComponent, ?string $sourceEntity, ?string $sourceId, ?string $debtorComponent, ?string $debtorEntity, ?string $debtorId, string $kind, ?string $description, float $amount, string $currency, ?string $dueDate): bool
    {
        return (string)($current['source_component'] ?? '') === (string)$sourceComponent
            && (string)($current['source_entity'] ?? '') === (string)$sourceEntity
            && (string)($current['source_id'] ?? '') === (string)$sourceId
            && (string)($current['debtor_component'] ?? '') === (string)$debtorComponent
            && (string)($current['debtor_entity'] ?? '') === (string)$debtorEntity
            && (string)($current['debtor_id'] ?? '') === (string)$debtorId
            && (string)($current['kind'] ?? '') === $kind
            && (string)($current['description'] ?? '') === (string)$description
            && abs((float)($current['amount'] ?? 0) - $amount) <= 0.0001
            && (string)($current['currency'] ?? '') === $currency
            && (string)($current['due_date'] ?? '') === (string)$dueDate;
    }

    private function paymentMatches(array $current, ?string $payerComponent, ?string $payerEntity, ?string $payerId, float $amount, string $currency, string $paidAt, ?string $method, ?string $reference): bool
    {
        return (string)($current['payer_component'] ?? '') === (string)$payerComponent
            && (string)($current['payer_entity'] ?? '') === (string)$payerEntity
            && (string)($current['payer_id'] ?? '') === (string)$payerId
            && abs((float)($current['amount'] ?? 0) - $amount) <= 0.0001
            && (string)($current['currency'] ?? '') === $currency
            && (string)($current['paid_at'] ?? '') === $paidAt
            && (string)($current['method'] ?? '') === (string)$method
            && (string)($current['reference'] ?? '') === (string)$reference;
    }

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
