<?php
namespace Xdecaro\Component\Decarofinance\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Throwable;

final class CrossProductIntegrationService
{
    public function notifyUser(int $userId,array $data): int
    {
        if ($userId<1) return 0;
        try {
            $component=Factory::getApplication()->bootComponent('com_xdecaronotifications');
            if (!is_object($component)||!method_exists($component,'getNotificationService')) return 0;
            return (int)$component->getNotificationService()->create([
                'recipient_type'=>'user','recipient_id'=>(string)$userId,'category'=>'finance','priority'=>(string)($data['priority'] ?? 'normal'),
                'title'=>(string)($data['title'] ?? 'Finance'),'message'=>(string)($data['message'] ?? ''),'source_component'=>'com_decarofinance',
                'source_entity'=>(string)($data['source_entity'] ?? 'obligation'),'source_id'=>(string)($data['source_id'] ?? ''),'external_key'=>(string)($data['external_key'] ?? ''),
                'action_url'=>(string)($data['action_url'] ?? ''),'payload'=>$data['payload'] ?? null,
            ]);
        } catch (Throwable $e) { Factory::getApplication()->getLogger()->warning('Finance notification integration skipped: '.$e->getMessage(),['category'=>'com_decarofinance']); return 0; }
    }

    public function createManagerTask(int $managerUserId,array $data): int
    {
        if ($managerUserId<1) return 0;
        try {
            $component=Factory::getApplication()->bootComponent('com_xdecarotasks');
            if (!is_object($component)||!method_exists($component,'getTaskService')) return 0;
            $service=$component->getTaskService();
            $id=(int)$service->create([
                'title'=>(string)($data['title'] ?? 'Finance'),'description'=>(string)($data['description'] ?? ''),'priority'=>(string)($data['priority'] ?? 'normal'),
                'due_at'=>$data['due_at'] ?? null,'source_component'=>'com_decarofinance','source_entity'=>(string)($data['source_entity'] ?? 'obligation'),
                'source_id'=>(string)($data['source_id'] ?? ''),'external_key'=>(string)($data['external_key'] ?? ''),
            ],$managerUserId);
            if ($id>0) $service->assign($id,'user',(string)$managerUserId,$managerUserId,true);
            return $id;
        } catch (Throwable $e) { Factory::getApplication()->getLogger()->warning('Finance task integration skipped: '.$e->getMessage(),['category'=>'com_decarofinance']); return 0; }
    }
}
