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
  public function autologoutAhahLogout() {
    _autologout_logout();
    $url = Url::fromRoute('user.login');
    return new RedirectResponse($url->toString());
  }

  /**
   * Ajax callback to reset the last access session variable.
   */
  public function autologoutAhahSetLast() {
    $_SESSION['autologout_last'] = REQUEST_TIME;

    // Reset the timer.
    $response = new AjaxResponse();
    $markup = autologout_create_timer();
    $response->addCommand(new Ajax\ReplaceCommand('#timer', $markup));

    return $response;
  }

  /**
   * AJAX callback that returns the time remaining for this user is logged out.
   */
  public function autologoutAhahGetRemainingTime() {
    $time_remaining_ms = _autologout_get_remaining_time() * 1000;

    // Reset the timer.
    $response = new AjaxResponse();
    $markup = autologout_create_timer();
    $response->addCommand(new Ajax\ReplaceCommand('#timer', $markup));
    $response->addCommand(new Ajax\SettingsCommand(array('time' => $time_remaining_ms)));

    return $response;
  }

}
