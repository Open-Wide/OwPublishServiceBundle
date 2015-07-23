<?php

namespace OpenWide\Publish\ServiceBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use eZ\Bundle\EzPublishCoreBundle\Controller;

class SubscriptionController extends Controller {

    public function addSubscriptionAction($userId,$serviceLinkId,$status) {

        $container = $this->container;
        
        $content = array(
            'userId' => $userId,
            'serviceLinkId' => $serviceLinkId,
            'status' => $status,
            );
                
        // On vérifie le status de l'abonnement actif ou pas
        $subscription =  $container->get('open_wide_service.fetch_by_legacy')->fetchByUserAndServiceLink($userId, $serviceLinkId,true,true);
        
        if(!$subscription && $status==1){
            $newSubscription = $container->get('open_wide_service.fetch_by_legacy')->addServiceLink($userId,$serviceLinkId);
            $container->get('open_wide_service.fetch_by_legacy')->debug($newSubscription);
            $content['action'] = 'add';
        }

        if($subscription && $status==0){
            $subscription->remove();
            $content['action'] = 'remove';
        }
        
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Expose-Headers', 'Cache-Control,Content-Encoding');
        $response->setContent(json_encode($content));
        return $response;
    }

}
