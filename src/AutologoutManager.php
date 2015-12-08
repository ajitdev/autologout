<?php
namespace Drupal\autologout;

use Drupal;
use Drupal\autologout\AutologoutHelperInterface;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Session\AnonymousUserSession;

class AutologoutManager implements AutologoutManagerInterface {

  /**
   * {@inheritdoc}
   */
  function autologoutPreventJs() {
    foreach (Drupal::moduleHandler()->invokeAll('autologout_prevent') as $prevent) {
      if (!empty($prevent)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  function autologoutRefreshOnly() {
    foreach (Drupal::moduleHandler()->invokeAll('autologout_refresh_only') as $module_refresh_only) {
      if (!empty($module_refresh_only)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  function autologoutInactivityMessage() {
    $message = Drupal::config('autologout.settings')->get('inactivity_message');
    if (!empty($message)) {
      drupal_set_message($message);
    }
  }

  /**
   * {@inheritdoc}
   */
  function autologoutLogout() {
    $user = Drupal::currentUser();

    if (Drupal::config('autologout.settings')->get('use_watchdog')) {
      Drupal::logger('user')->info('Session automatically closed for %name by autologout.', array('%name' => $user->getUsername()));
    }

    // Destroy the current session.
    Drupal::moduleHandler()->invokeAll('user_logout', array($user));
    Drupal::service('session_manager')->destroy();
    $user->setAccount(new AnonymousUserSession());


  }

  /**
   * {@inheritdoc}
   */
  function autologoutGetRoleTimeout() {
    $roles = user_roles(TRUE);
    $role_timeout = array();

    // Go through roles, get timeouts for each and return as array.
    foreach ($roles as $rid => $role) {
      if (Drupal::config('autologout.settings')->get('role_' . $rid)) {
        $timeout_role = Drupal::config('autologout.settings')->get('role_' . $rid . '_timeout');
        $role_timeout[$rid] = $timeout_role;
      }
    }
    return $role_timeout;
  }

  /**
   * {@inheritdoc}
   */
  function autologoutGetRemainingTime() {
    $timeout = Drupal::service('autologout.manager')->autologoutGetUserTimeout();
    $time_passed = isset($_SESSION['autologout_last']) ? REQUEST_TIME - $_SESSION['autologout_last'] : 0;
    return $timeout - $time_passed;
  }


  /**
   * {@inheritdoc}
   */
  function autologoutCreateTimer() {
    return Drupal::service('autologout.manager')->autologoutGetRemainingTime();
  }

  /**
   * {@inheritdoc}
   */
  function autologoutGetUserTimeout($uid = NULL) {

    if (is_null($uid)) {
      // If $uid is not provided, use the logged in user.
      $user = Drupal::currentUser();
    }
    else {
      $user = User::load($uid);
    }

    $uid = $user->id();

    if ($user->id() == 0) {
      // Anonymous doesn't get logged out.
      return 0;
    }

    if (is_numeric($user_timeout = Drupal::config('autologout.settings')->get('user_' . $user->id()))) {
      // User timeout takes precedence.
      return $user_timeout;
    }

    // Get role timeouts for user.
    if (Drupal::config('autologout.settings')->get('role_logout')) {
      $user_roles = $user->getRoles();
      $output = array();
      $timeouts = Drupal::service('autologout.manager')->autologoutGetRoleTimeout();
      foreach ($user_roles as $rid => $role) {
        if (isset($timeouts[$role])) {
          $output[$rid] = $timeouts[$role];
        }
      }

      // Assign the lowest timeout value to be session timeout value.
      if (!empty($output)) {
        // If one of the user's roles has a unique timeout, use this.
        return min($output);
      }
    }

    // If no user or role override exists, return the default timeout.
    return Drupal::config('autologout.settings')->get('timeout');
  }

  /**
   * {@inheritdoc}
   */
  function autologoutLogoutRole($user) {
    if (Drupal::config('autologout.settings')->get('role_logout')) {
      foreach ($user->roles as $key => $role) {
        if (Drupal::config('autologout.settings')->get('role_' . $key)) {
          return TRUE;
        }
      }
    }

    return FALSE;
  }
}
?>
