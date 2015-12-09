<?php
/**
 * @file
 * Contains \Drupal\autologout\EventSubscriber\AutologoutSubscriber.
 */

namespace Drupal\autologout\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AutologoutSubscriber implements EventSubscriberInterface {

  /**
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   */
  public function checkForAutologoutjs(GetResponseEvent $event) {
    if (_autologout_prevent()) {
      return;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('checkForAutologoutjs');
    return $events;
  }

}
