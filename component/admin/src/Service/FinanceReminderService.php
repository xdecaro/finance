<?php
namespace Xdecaro\Component\Decarofinance\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Router\Route;

final class FinanceReminderService
{
    public function __construct(private FinanceQueryService $query,private CrossProductIntegrationService $integration) {}

    public function run(): array
    {
        $params=ComponentHelper::getParams('com_decarofinance'); $days=max(0,min(90,(int)$params->get('reminder_days',7))); $manager=(int)$params->get('manager_user_id',0);
        $notify=(bool)$params->get('enable_notifications',1); $tasks=(bool)$params->get('enable_tasks',1); $stats=['checked'=>0,'notifications'=>0,'tasks'=>0];
        foreach ($this->query->getDueObligations($days) as $row) {
            $stats['checked']++; $id=(int)$row['id']; $outstanding=max(0,(float)$row['amount']-(float)$row['allocated_amount']); if ($outstanding<=0.004) continue;
            $overdue=!empty($row['due_date']) && $row['due_date'] < date('Y-m-d'); $priority=$overdue?'high':'normal'; $label=$overdue?'Obbligazione scaduta':'Obbligazione in scadenza';
            $amount=number_format($outstanding,2,',','.').' '.(string)$row['currency']; $message=$label.' #'.$id.': '.$amount.(!empty($row['description'])?' — '.$row['description']:'');
            $key='finance-obligation-'.$id.'-'.($overdue?'overdue':'due').'-'.($row['due_date'] ?: 'none');
            if ($notify && $manager>0) { $n=$this->integration->notifyUser($manager,['priority'=>$priority,'title'=>$label,'message'=>$message,'source_entity'=>'obligation','source_id'=>(string)$id,'external_key'=>$key,'action_url'=>'index.php?option=com_decarofinance&view=obligations']); if ($n>0) $stats['notifications']++; }
            if ($tasks && $manager>0) { $t=$this->integration->createManagerTask($manager,['priority'=>$overdue?'high':'normal','title'=>$label.' #'.$id,'description'=>$message,'due_at'=>!empty($row['due_date'])?$row['due_date'].' 23:59:59':null,'source_entity'=>'obligation','source_id'=>(string)$id,'external_key'=>$key.'-task']); if ($t>0) $stats['tasks']++; }
        }
        return $stats;
    }
}
