<?php
namespace Drupal\autologout;

use Drupal\autologout\AutologoutHelperInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AutologoutManager implements AutologoutManagerInterface {
  function _autologout_prevent() {
    dsm('in');
    foreach (\Drupal::moduleHandler()->invokeAll('autologout_prevent') as $prevent) {
      if (!empty($prevent)) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
?>
