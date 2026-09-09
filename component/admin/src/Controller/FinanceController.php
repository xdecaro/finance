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
            ],(int)Factory::getApplication()->getIdentity()->id);
            $this->message(Text::sprintf('COM_DECAROFINANCE_OBLIGATION_CREATED',$id),'message','obligations');
        } catch (Throwable $e) { $this->message($e->getMessage(),'error','obligations'); }
    }

    public function recordPayment(): void
    {
        $this->checkToken(); $this->authorise('core.create'); $input=$this->input;
        try {
            $amount=$input->getString('amount'); $id=$this->service()->recordPayment(['external_key'=>$input->getString('external_key'),'payer_component'=>$input->getString('payer_component'),'payer_entity'=>$input->getString('payer_entity'),'payer_id'=>$input->getString('payer_id'),'amount'=>$amount,'currency'=>$input->getString('currency','EUR'),'paid_at'=>$input->getString('paid_at'),'method'=>$input->getString('method'),'reference'=>$input->getString('reference')],(int)Factory::getApplication()->getIdentity()->id);
            $obligationId=$input->getInt('obligation_id'); if ($obligationId>0) { $this->service()->allocatePayment($id,$obligationId,$amount); }
            $this->message(Text::sprintf('COM_DECAROFINANCE_PAYMENT_CREATED',$id),'message','payments');
        } catch (Throwable $e) { $this->message($e->getMessage(),'error','payments'); }
    }

    public function depositMovement(): void
    {
        $this->checkToken(); $this->authorise('core.create'); $input=$this->input;
        try {
            $account=$this->service()->getOrCreateDepositAccount($input->getString('owner_component'),$input->getString('owner_entity'),$input->getString('owner_id'),$input->getString('currency','EUR'));
            $id=$this->service()->postDepositMovement($account,$input->getString('movement_type'),$input->getString('amount'),['external_key'=>$input->getString('external_key'),'description'=>$input->getString('description'),'source_component'=>$input->getString('source_component'),'source_entity'=>$input->getString('source_entity'),'source_id'=>$input->getString('source_id')],(int)Factory::getApplication()->getIdentity()->id);
            $this->message(Text::sprintf('COM_DECAROFINANCE_DEPOSIT_MOVEMENT_CREATED',$id),'message','deposits');
        } catch (Throwable $e) { $this->message($e->getMessage(),'error','deposits'); }
    }

    public function createBudget(): void
    {
        $this->checkToken(); $this->authorise('core.create');
        try { $id=$this->service()->createBudget($this->input->getString('title'),$this->input->getString('period_start'),$this->input->getString('period_end'),(int)Factory::getApplication()->getIdentity()->id); $this->message(Text::sprintf('COM_DECAROFINANCE_BUDGET_CREATED',$id),'message','budgets'); }
        catch (Throwable $e) { $this->message($e->getMessage(),'error','budgets'); }
    }

    public function addBudgetLine(): void
    {
        $this->checkToken(); $this->authorise('core.create');
        try { $this->service()->addBudgetLine($this->input->getInt('budget_id'),$this->input->getString('kind'),$this->input->getString('title'),$this->input->getString('planned_amount')); $this->message(Text::_('COM_DECAROFINANCE_BUDGET_LINE_CREATED'),'message','budgets'); }
        catch (Throwable $e) { $this->message($e->getMessage(),'error','budgets'); }
    }

    private function service(): FinanceService { return Factory::getContainer()->get(FinanceService::class); }
    private function authorise(string $action): void { if (!Factory::getApplication()->getIdentity()->authorise($action,'com_decarofinance')) throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403); }
    private function message(string $message,string $type,string $view): void { Factory::getApplication()->enqueueMessage($message,$type); $this->setRedirect(Route::_('index.php?option=com_decarofinance&view='.$view,false)); }
}
