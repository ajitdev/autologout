<?php
/**
 * @file
 * Contains \Drupal\autologout\Controller\AutologouteController.
 */

namespace Drupal\autologout\Controller;

use Drupal\Core\Ajax;
use Drupal\Core\Ajax\AjaxResponse;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

/**
 * Example page controller.
 */
class AutologoutController {

  /**
   * AJAX callback that performs the actual logout and redirects the user.
   */
  public function autologoutAhahLogout() {
    _autologout_logout();
    exit();
  }

  /**
   * Ajax callback to reset the last access session variable.
   */
  public function autologoutAhahSetLast() {
    $_SESSION['autologout_last'] = time();

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
