<?php
namespace Xdecaro\Component\Decarofinance\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Throwable;
use Xdecaro\Component\Decarofinance\Administrator\Service\FinanceService;

final class FinanceController extends BaseController
{
    public function createObligation(): void
    {
        $this->checkToken(); $this->authorise('core.create'); $input=$this->input;
        try {
            $id=$this->service()->createObligation([
                'external_key'=>$input->getString('external_key'),'source_component'=>$input->getString('source_component'),'source_entity'=>$input->getString('source_entity'),'source_id'=>$input->getString('source_id'),
                'debtor_component'=>$input->getString('debtor_component'),'debtor_entity'=>$input->getString('debtor_entity'),'debtor_id'=>$input->getString('debtor_id'),
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
            $id=$this->service()->recordPayment([
                'external_key'=>$input->getString('external_key'),'payer_component'=>$input->getString('payer_component'),'payer_entity'=>$input->getString('payer_entity'),'payer_id'=>$input->getString('payer_id'),
                'amount'=>$amount,'currency'=>$input->getString('currency','EUR'),'paid_at'=>$input->getString('paid_at'),'method'=>$input->getString('method'),'reference'=>$input->getString('reference')
            ],$this->userId());
            $obligationId=$input->getInt('obligation_id'); if ($obligationId>0) { $this->service()->allocatePayment($id,$obligationId,$amount); }
            $this->message(Text::sprintf('COM_DECAROFINANCE_PAYMENT_CREATED',$id),'message','payments');
        } catch (Throwable $e) { $this->message($e->getMessage(),'error','payments'); }
    }

    public function depositMovement(): void
    {
        $this->checkToken(); $this->authorise('core.create'); $input=$this->input;
        try {
            $account=$this->service()->getOrCreateDepositAccount($input->getString('owner_component'),$input->getString('owner_entity'),$input->getString('owner_id'),$input->getString('currency','EUR'));
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
            $id=$this->service()->createBudget(
                $this->input->getString('title'),
                $this->input->getString('period_start'),
                $this->input->getString('period_end'),
                $this->userId(),
                [
                    'owner_component'=>$this->input->getString('owner_component'),
                    'owner_entity'=>$this->input->getString('owner_entity'),
                    'owner_id'=>$this->input->getString('owner_id'),
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
                ['code'=>$this->input->getString('code'),'category'=>$this->input->getString('category')]
            );
            $this->message(Text::_('COM_DECAROFINANCE_BUDGET_LINE_CREATED'),'message','budgets');
        } catch (Throwable $e) { $this->message($e->getMessage(),'error','budgets'); }
    }

    public function createAccount(): void
    {
        $this->checkToken(); $this->authorise('core.create'); $input=$this->input;
        try {
            $id=$this->service()->createAccount([
                'external_key'=>$input->getString('external_key'),
                'owner_component'=>$input->getString('owner_component'),'owner_entity'=>$input->getString('owner_entity'),'owner_id'=>$input->getString('owner_id'),
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
            $id=$this->service()->recordTransaction([
                'external_key'=>$input->getString('external_key'),'account_id'=>$input->getInt('account_id'),'budget_line_id'=>$input->getInt('budget_line_id'),
                'direction'=>$input->getString('direction'),'category'=>$input->getString('category'),'amount'=>$input->getString('amount'),'currency'=>$input->getString('currency','EUR'),
                'occurred_at'=>$input->getString('occurred_at'),'description'=>$input->getString('description'),
                'source_component'=>$input->getString('source_component'),'source_entity'=>$input->getString('source_entity'),'source_id'=>$input->getString('source_id'),
                'counterparty_component'=>$input->getString('counterparty_component'),'counterparty_entity'=>$input->getString('counterparty_entity'),'counterparty_id'=>$input->getString('counterparty_id'),
                'evidence_component'=>$input->getString('evidence_component'),'evidence_entity'=>$input->getString('evidence_entity'),'evidence_id'=>$input->getString('evidence_id'),
            ],$this->userId());
            $this->message(Text::sprintf('COM_DECAROFINANCE_TRANSACTION_CREATED',$id),'message','transactions');
        } catch (Throwable $e) { $this->message($e->getMessage(),'error','transactions'); }
    }

    public function createOrder(): void
    {
        $this->checkToken(); $this->authorise('core.create'); $input=$this->input;
        try {
            $id=$this->service()->createOrder([
                'external_key'=>$input->getString('external_key'),'direction'=>$input->getString('direction'),'account_id'=>$input->getInt('account_id'),'budget_line_id'=>$input->getInt('budget_line_id'),
                'owner_component'=>$input->getString('owner_component'),'owner_entity'=>$input->getString('owner_entity'),'owner_id'=>$input->getString('owner_id'),
                'counterparty_component'=>$input->getString('counterparty_component'),'counterparty_entity'=>$input->getString('counterparty_entity'),'counterparty_id'=>$input->getString('counterparty_id'),
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

    private function service(): FinanceService { return Factory::getContainer()->get(FinanceService::class); }
    private function userId(): int { return (int)Factory::getApplication()->getIdentity()->id; }
    private function authorise(string $action): void { if (!Factory::getApplication()->getIdentity()->authorise($action,'com_decarofinance')) throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403); }
    private function message(string $message,string $type,string $view): void { Factory::getApplication()->enqueueMessage($message,$type); $this->setRedirect(Route::_('index.php?option=com_decarofinance&view='.$view,false)); }
}
