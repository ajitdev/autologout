<?php
/**
 * @file
 * Contains \Drupal\autologout\Controller\AutologouteController.
 */

namespace Drupal\autologout\Controller;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Ajax;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Controller\ControllerBase;

/**
 * Example page controller.
 */
class AutologoutController extends ControllerBase {

  /**
   * AJAX callback that performs the actual logout and redirects the user.
   */
  public function ahahLogout() {
    \Drupal::service('autologout.manager')->autologoutLogout();
    $url = Url::fromRoute('user.login');
    return new RedirectResponse($url->toString());
  }

  /**
   * Ajax callback to reset the last access session variable.
   */
  public function ahahSetLast() {
    $_SESSION['autologout_last'] = REQUEST_TIME;

    // Reset the timer.
    $response = new AjaxResponse();
    $markup = \Drupal::service('autologout.manager')->autologoutCreateTimer();
    $response->addCommand(new Ajax\ReplaceCommand('#timer', $markup));

    return $response;
  }

  /**
   * AJAX callback that returns the time remaining for this user is logged out.
   */
  public function ahahGetRemainingTime() {
    $autologout_manager = \Drupal::service('autologout.manager');
    $time_remaining_ms = $autologout_manager->autologoutGetRemainingTime() * 1000;

    // Reset the timer.
    $response = new AjaxResponse();
    $markup = $autologout_manager->autologoutCreateTimer();

    $response->addCommand(new Ajax\ReplaceCommand('#timer', $markup));
    $response->addCommand(new Ajax\SettingsCommand(array('time' => $time_remaining_ms)));

    return $response;
  }

}
