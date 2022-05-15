<?php 

namespace App\Services;

use App\Services\Utilities;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class KernelSubscriber implements EventSubscriberInterface {

    /*
        Hook into Kernel event (fired on every request)

        The following actions are hooked:
            - no hooked actions
    */

    private $event, $u;

    public function __construct(Utilities $u) {

        $this->u = $u;
    }

    public static function getSubscribedEvents(): array {

        return [ KernelEvents::REQUEST => 'onRequest' ];
    }

    public function onRequest(GetResponseEvent $event): void {
        
        $this->event = $event;
        
        if(!$event->isMasterRequest()) return;
    }   

}
?>