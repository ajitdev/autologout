<?php
/**
 * @file
 * Contains \Drupal\autologout\EventSubscriber\AutologoutSubscriber.
 */

namespace Drupal\autologout\EventSubscriber;

use Drupal\autologout\AutologoutManagerInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Defines autologout Subscriber.
 */
class AutologoutSubscriber implements EventSubscriberInterface {

  /**
   * The autologout manager service.
   *
   * @var \Drupal\autologout\AutologoutManagerInterface
   */
  protected $autoLogoutManager;

  /**
   * Constructs an AutologoutSubscriber object.
   *
   * @param \Drupal\autologout\AutologoutManagerInterface $autologout
   *   The autologout manager service.
   */
  public function __construct(AutologoutManagerInterface $autologout) {
    $this->autoLogoutManager = $autologout;
  }

  /**
   * Check for autologout JS.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The request event.
   */
  public function checkForAutologoutjs(GetResponseEvent $event) {
    if ($this->autoLogoutManager->autologoutPreventJs()) {
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
