<?php
namespace Xdecaro\Component\Decarofinance\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Throwable;
use Xdecaro\Component\Decarofinance\Administrator\Extension\DecarofinanceComponent;
use Xdecaro\Component\Decarofinance\Administrator\Service\FinanceService;
use Xdecaro\Component\Decarofinance\Administrator\Service\ReferenceLookupService;

final class FinanceController extends BaseController
{
    public function createObligation(): void
    {
        $this->checkToken(); $this->authorise('core.create'); $input=$this->input;
        try {
            [$debtorComponent,$debtorEntity,$debtorId]=$this->selectedParty('debtor_ref','debtor');
            $id=$this->service()->createObligation([
                'external_key'=>$input->getString('external_key'),'source_component'=>$input->getString('source_component'),'source_entity'=>$input->getString('source_entity'),'source_id'=>$input->getString('source_id'),
                'debtor_component'=>$debtorComponent,'debtor_entity'=>$debtorEntity,'debtor_id'=>$debtorId,
                'kind'=>$input->getString('kind'),'description'=>$input->getString('description'),'amount'=>$input->getString('amount'),'currency'=>$input->getString('currency','EUR'),'due_date'=>$input->getString('due_date'),
            ],$this->userId());
            $this->message(Text::sprintf('COM_DECAROFINANCE_OBLIGATION_CREATED',$id),'message','obligations');
        } catch (Throwable $e) { $this->message($e->getMessage(),'error','obligations'); }
    }

    public function recordPayment(): void
    {
        $this->checkToken(); $this->authorise('core.create'); $input=$this->input;
        try {
            $amount=$input->getString('amount');
            [$payerComponent,$payerEntity,$payerId]=$this->selectedParty('payer_ref','payer');
            $data=[
                'external_key'=>$input->getString('external_key'),'payer_component'=>$payerComponent,'payer_entity'=>$payerEntity,'payer_id'=>$payerId,
                'amount'=>$amount,'currency'=>$input->getString('currency','EUR'),'paid_at'=>$input->getString('paid_at'),'method'=>$input->getString('method'),'reference'=>$input->getString('reference')
            ];
            $obligationId=$input->getInt('obligation_id');
            $id=$obligationId>0
                ? $this->service()->recordPaymentAndAllocate($data,$obligationId,$this->userId())
                : $this->service()->recordPayment($data,$this->userId());
            $this->message(Text::sprintf('COM_DECAROFINANCE_PAYMENT_CREATED',$id),'message','payments');
        } catch (Throwable $e) { $this->message($e->getMessage(),'error','payments'); }
    }

    public function depositMovement(): void
    {
        $this->checkToken(); $this->authorise('core.create'); $input=$this->input;
        try {
            [$ownerComponent,$ownerEntity,$ownerId]=$this->selectedParty('owner_ref','owner',true);
            if ($ownerComponent===null || $ownerEntity===null || $ownerId===null) { throw new \InvalidArgumentException(Text::_('COM_DECAROFINANCE_OWNER_REQUIRED')); }
            $account=$this->service()->getOrCreateDepositAccount($ownerComponent,$ownerEntity,$ownerId,$input->getString('currency','EUR'));
            $id=$this->service()->postDepositMovement($account,$input->getString('movement_type'),$input->getString('amount'),[
                'external_key'=>$input->getString('external_key'),'description'=>$input->getString('description'),
                'source_component'=>$input->getString('source_component'),'source_entity'=>$input->getString('source_entity'),'source_id'=>$input->getString('source_id')
            ],$this->userId());
            $this->message(Text::sprintf('COM_DECAROFINANCE_DEPOSIT_MOVEMENT_CREATED',$id),'message','deposits');
        } catch (Throwable $e) { $this->message($e->getMessage(),'error','deposits'); }
    }

    public function createBudget(): void
    {
        $this->checkToken(); $this->authorise('core.create');
        try {
            [$ownerComponent,$ownerEntity,$ownerId]=$this->selectedOrganization('owner_organization_uuid','owner');
            $id=$this->service()->createBudget(
                $this->input->getString('title'),
                $this->input->getString('period_start'),
                $this->input->getString('period_end'),
                $this->userId(),
                [
                    'owner_component'=>$ownerComponent,
                    'owner_entity'=>$ownerEntity,
                    'owner_id'=>$ownerId,
                    'currency'=>$this->input->getString('currency','EUR'),
                ]
            );
            $this->message(Text::sprintf('COM_DECAROFINANCE_BUDGET_CREATED',$id),'message','budgets');
        } catch (Throwable $e) { $this->message($e->getMessage(),'error','budgets'); }
    }

    public function addBudgetLine(): void
    {
        $this->checkToken(); $this->authorise('core.create');
        try {
            $this->service()->addBudgetLine(
                $this->input->getInt('budget_id'),
                $this->input->getString('kind'),
                $this->input->getString('title'),
                $this->input->getString('planned_amount'),
                ['code'=>$this->input->getString('code'),'category'=>$this->input->getString('category'),'cost_center_id'=>$this->input->getInt('cost_center_id')]
            );
            $this->message(Text::_('COM_DECAROFINANCE_BUDGET_LINE_CREATED'),'message','budgets');
        } catch (Throwable $e) { $this->message($e->getMessage(),'error','budgets'); }
    }

    public function createCostCenter(): void
    {
        $this->checkToken(); $this->authorise('core.create'); $input=$this->input;
        try {
            [$ownerComponent,$ownerEntity,$ownerId]=$this->selectedOrganization('owner_organization_uuid','owner');
            $id=$this->service()->createCostCenter([
                'code'=>$input->getString('code'),
                'title'=>$input->getString('title'),
                'owner_component'=>$ownerComponent,'owner_entity'=>$ownerEntity,'owner_id'=>$ownerId,
            ],$this->userId());
            $this->message(Text::sprintf('COM_DECAROFINANCE_COST_CENTER_CREATED',$id),'message','costcenters');
        } catch (Throwable $e) { $this->message($e->getMessage(),'error','costcenters'); }
    }

    public function createAccount(): void
    {
        $this->checkToken(); $this->authorise('core.create'); $input=$this->input;
        try {
            [$ownerComponent,$ownerEntity,$ownerId]=$this->selectedOrganization('owner_organization_uuid','owner');
            $id=$this->service()->createAccount([
                'external_key'=>$input->getString('external_key'),'code'=>$input->getString('code'),
                'owner_component'=>$ownerComponent,'owner_entity'=>$ownerEntity,'owner_id'=>$ownerId,
                'name'=>$input->getString('name'),'account_type'=>$input->getString('account_type','bank'),'identifier'=>$input->getString('identifier'),
                'currency'=>$input->getString('currency','EUR'),'opening_balance'=>$input->getString('opening_balance','0'),
            ],$this->userId());
            $this->message(Text::sprintf('COM_DECAROFINANCE_ACCOUNT_CREATED',$id),'message','accounts');
        } catch (Throwable $e) { $this->message($e->getMessage(),'error','accounts'); }
    }

    public function recordTransaction(): void
    {
        $this->checkToken(); $this->authorise('core.create'); $input=$this->input;
        try {
            [$counterpartyComponent,$counterpartyEntity,$counterpartyId]=$this->selectedParty('counterparty_ref','counterparty');
            $id=$this->service()->recordTransaction([
                'external_key'=>$input->getString('external_key'),'account_id'=>$input->getInt('account_id'),'budget_line_id'=>$input->getInt('budget_line_id'),'cost_center_id'=>$input->getInt('cost_center_id'),
                'direction'=>$input->getString('direction'),'category'=>$input->getString('category'),'amount'=>$input->getString('amount'),'currency'=>$input->getString('currency','EUR'),
                'occurred_at'=>$input->getString('occurred_at'),'description'=>$input->getString('description'),
                'source_component'=>$input->getString('source_component'),'source_entity'=>$input->getString('source_entity'),'source_id'=>$input->getString('source_id'),
                'counterparty_component'=>$counterpartyComponent,'counterparty_entity'=>$counterpartyEntity,'counterparty_id'=>$counterpartyId,
                'evidence_component'=>$input->getString('evidence_component'),'evidence_entity'=>$input->getString('evidence_entity'),'evidence_id'=>$input->getString('evidence_id'),
            ],$this->userId());
            $this->message(Text::sprintf('COM_DECAROFINANCE_TRANSACTION_CREATED',$id),'message','transactions');
        } catch (Throwable $e) { $this->message($e->getMessage(),'error','transactions'); }
    }

    public function createOrder(): void
    {
        $this->checkToken(); $this->authorise('core.create'); $input=$this->input;
        try {
            [$ownerComponent,$ownerEntity,$ownerId]=$this->selectedOrganization('owner_organization_uuid','owner');
            [$counterpartyComponent,$counterpartyEntity,$counterpartyId]=$this->selectedParty('counterparty_ref','counterparty');
            $id=$this->service()->createOrder([
                'external_key'=>$input->getString('external_key'),'direction'=>$input->getString('direction'),'account_id'=>$input->getInt('account_id'),'budget_line_id'=>$input->getInt('budget_line_id'),'cost_center_id'=>$input->getInt('cost_center_id'),
                'owner_component'=>$ownerComponent,'owner_entity'=>$ownerEntity,'owner_id'=>$ownerId,
                'counterparty_component'=>$counterpartyComponent,'counterparty_entity'=>$counterpartyEntity,'counterparty_id'=>$counterpartyId,
                'category'=>$input->getString('category'),'description'=>$input->getString('description'),'amount'=>$input->getString('amount'),'currency'=>$input->getString('currency','EUR'),
                'due_date'=>$input->getString('due_date'),'required_approvals'=>$input->getInt('required_approvals',2),
                'source_component'=>$input->getString('source_component'),'source_entity'=>$input->getString('source_entity'),'source_id'=>$input->getString('source_id'),
                'evidence_component'=>$input->getString('evidence_component'),'evidence_entity'=>$input->getString('evidence_entity'),'evidence_id'=>$input->getString('evidence_id'),
            ],$this->userId());
            $this->message(Text::sprintf('COM_DECAROFINANCE_ORDER_CREATED',$id),'message','orders');
        } catch (Throwable $e) { $this->message($e->getMessage(),'error','orders'); }
    }

    public function approveOrder(): void
    {
        $this->checkToken(); $this->authorise('finance.approve');
        try {
            $this->service()->approveOrder($this->input->getInt('order_id'),$this->input->getInt('step'),$this->input->getString('role'),$this->userId(),$this->input->getString('note'));
            $this->message(Text::_('COM_DECAROFINANCE_ORDER_APPROVED'),'message','orders');
        } catch (Throwable $e) { $this->message($e->getMessage(),'error','orders'); }
    }

    public function executeOrder(): void
    {
        $this->checkToken(); $this->authorise('finance.execute');
        try {
            $transactionId=$this->service()->executeOrder($this->input->getInt('order_id'),$this->userId());
            $this->message(Text::sprintf('COM_DECAROFINANCE_ORDER_EXECUTED',$transactionId),'message','orders');
        } catch (Throwable $e) { $this->message($e->getMessage(),'error','orders'); }
    }

    public function cancelOrder(): void
    {
        $this->checkToken(); $this->authorise('core.edit');
        try {
            $this->service()->cancelOrder($this->input->getInt('order_id'));
            $this->message(Text::_('COM_DECAROFINANCE_ORDER_CANCELLED'),'message','orders');
        } catch (Throwable $e) { $this->message($e->getMessage(),'error','orders'); }
    }

    public function transfer(): void
    {
        $this->checkToken(); $this->authorise('core.create'); $input=$this->input;
        try {
            $id=$this->service()->transferBetweenAccounts([
                'external_key'=>$input->getString('external_key'),'from_account_id'=>$input->getInt('from_account_id'),'to_account_id'=>$input->getInt('to_account_id'),
                'amount'=>$input->getString('amount'),'currency'=>$input->getString('currency','EUR'),'occurred_at'=>$input->getString('occurred_at'),'description'=>$input->getString('description'),
                'source_component'=>$input->getString('source_component'),'source_entity'=>$input->getString('source_entity'),'source_id'=>$input->getString('source_id'),
            ],$this->userId());
            $this->message(Text::sprintf('COM_DECAROFINANCE_TRANSFER_CREATED',$id),'message','transfers');
        } catch (Throwable $e) { $this->message($e->getMessage(),'error','transfers'); }
    }

    public function cashCheck(): void
    {
        $this->checkToken(); $this->authorise('finance.reconcile'); $input=$this->input;
        try {
            $id=$this->service()->recordCashCheck([
                'external_key'=>$input->getString('external_key'),'account_id'=>$input->getInt('account_id'),'checked_at'=>$input->getString('checked_at'),
                'actual_balance'=>$input->getString('actual_balance'),'note'=>$input->getString('note'),
                'evidence_component'=>$input->getString('evidence_component'),'evidence_entity'=>$input->getString('evidence_entity'),'evidence_id'=>$input->getString('evidence_id'),
            ],$this->userId());
            $this->message(Text::sprintf('COM_DECAROFINANCE_CASH_CHECK_CREATED',$id),'message','cashchecks');
        } catch (Throwable $e) { $this->message($e->getMessage(),'error','cashchecks'); }
    }

    public function createStatement(): void
    {
        $this->checkToken(); $this->authorise('core.create'); $input=$this->input;
        try {
            [$ownerComponent,$ownerEntity,$ownerId]=$this->selectedOrganization('owner_organization_uuid','owner');
            $id=$this->service()->createStatement([
                'external_key'=>$input->getString('external_key'),'statement_type'=>$input->getString('statement_type'),'title'=>$input->getString('title'),
                'period_start'=>$input->getString('period_start'),'period_end'=>$input->getString('period_end'),'currency'=>$input->getString('currency','EUR'),
                'owner_component'=>$ownerComponent,'owner_entity'=>$ownerEntity,'owner_id'=>$ownerId,
                'source_component'=>$input->getString('source_component'),'source_entity'=>$input->getString('source_entity'),'source_id'=>$input->getString('source_id'),
                'document_component'=>$input->getString('document_component'),'document_entity'=>$input->getString('document_entity'),'document_id'=>$input->getString('document_id'),
            ],$this->userId());
            $this->message(Text::sprintf('COM_DECAROFINANCE_STATEMENT_CREATED',$id),'message','statements');
        } catch (Throwable $e) { $this->message($e->getMessage(),'error','statements'); }
    }

    public function addStatementLine(): void
    {
        $this->checkToken(); $this->authorise('core.create'); $input=$this->input;
        try {
            $id=$this->service()->addStatementLine($input->getInt('statement_id'),[
                'section_code'=>$input->getString('section_code'),'line_code'=>$input->getString('line_code'),'label'=>$input->getString('label'),
                'line_type'=>$input->getString('line_type','amount'),'amount'=>$input->getString('amount'),'text_value'=>$input->getString('text_value'),'sort_order'=>$input->getInt('sort_order'),
                'source_component'=>$input->getString('source_component'),'source_entity'=>$input->getString('source_entity'),'source_id'=>$input->getString('source_id'),
            ],$this->userId());
            $this->message(Text::sprintf('COM_DECAROFINANCE_STATEMENT_LINE_CREATED',$id),'message','statements');
        } catch (Throwable $e) { $this->message($e->getMessage(),'error','statements'); }
    }

    public function finaliseStatement(): void
    {
        $this->checkToken(); $this->authorise('finance.reconcile');
        try {
            $this->service()->finaliseStatement($this->input->getInt('statement_id'),$this->userId());
            $this->message(Text::_('COM_DECAROFINANCE_STATEMENT_FINALISED'),'message','statements');
        } catch (Throwable $e) { $this->message($e->getMessage(),'error','statements'); }
    }

    public function approveStatement(): void
    {
        $this->checkToken(); $this->authorise('finance.approve');
        try {
            $this->service()->approveStatement($this->input->getInt('statement_id'),$this->userId());
            $this->message(Text::_('COM_DECAROFINANCE_STATEMENT_APPROVED'),'message','statements');
        } catch (Throwable $e) { $this->message($e->getMessage(),'error','statements'); }
    }

    private function references(): ReferenceLookupService
    {
        $component=Factory::getApplication()->bootComponent('com_decarofinance');
        if (!$component instanceof DecarofinanceComponent) {
            throw new \RuntimeException('Finance component is unavailable.');
        }
        return $component->getReferenceLookupService();
    }

    private function selectedOrganization(string $selectionField,string $legacyPrefix): array
    {
        $selected=$this->input->getString($selectionField);
        if ($selected!=='') { return $this->references()->organizationReference($selected); }

        $c=$this->input->getString($legacyPrefix.'_component');
        $e=$this->input->getString($legacyPrefix.'_entity');
        $i=$this->input->getString($legacyPrefix.'_id');
        return ($c==='' && $e==='' && $i==='') ? [null,null,null] : [$c,$e,$i];
    }

    private function selectedParty(string $selectionField,string $legacyPrefix,bool $organizationOnly=false): array
    {
        $selected=$this->input->getString($selectionField);
        if ($selected!=='') {
            $reference=$this->references()->partyReference($selected);
            if ($organizationOnly && $reference[0]!==ReferenceLookupService::ORGANIZATIONS) {
                throw new \InvalidArgumentException(Text::_('COM_DECAROFINANCE_OWNER_MUST_BE_ORGANIZATION'));
            }
            return $reference;
        }

        $c=$this->input->getString($legacyPrefix.'_component');
        $e=$this->input->getString($legacyPrefix.'_entity');
        $i=$this->input->getString($legacyPrefix.'_id');
        return ($c==='' && $e==='' && $i==='') ? [null,null,null] : [$c,$e,$i];
    }

    private function service(): FinanceService
    {
        $component=Factory::getApplication()->bootComponent('com_decarofinance');
        if (!$component instanceof DecarofinanceComponent) {
            throw new \RuntimeException('Finance component is unavailable.');
        }
        return $component->getFinanceService();
    }
    private function userId(): int { return (int)Factory::getApplication()->getIdentity()->id; }
    private function authorise(string $action): void { if (!Factory::getApplication()->getIdentity()->authorise($action,'com_decarofinance')) throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403); }
    private function message(string $message,string $type,string $view): void { Factory::getApplication()->enqueueMessage($message,$type); $this->setRedirect(Route::_('index.php?option=com_decarofinance&view='.$view,false)); }
}
