<?php
/**
 * @file
 * Contains \Drupal\autologout\AutologoutManager.
 */

namespace Drupal\autologout;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\user\Entity\User;
use Drupal\Core\Session\AnonymousUserSession;

/**
 * Defines an AutologoutManager service.
 */
class AutologoutManager implements AutologoutManagerInterface {

  /**
   * The module manager service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The config object for 'autologout.settings'.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $autoLogoutSettings;

  /**
   * Constructs an AutologoutManager object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config_factory) {
    $this->moduleHandler = $module_handler;
    $this->autoLogoutSettings = $config_factory->get('autologout.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function autologoutPreventJs() {
    foreach ($this->moduleHandler->invokeAll('autologout_prevent') as $prevent) {
      if (!empty($prevent)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function autologoutRefreshOnly() {
    foreach ($this->moduleHandler->invokeAll('autologout_refresh_only') as $module_refresh_only) {
      if (!empty($module_refresh_only)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function autologoutInactivityMessage() {
    $message = $this->autoLogoutSettings->get('inactivity_message');
    if (!empty($message)) {
      drupal_set_message($message);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function autologoutLogout() {
    $user = \Drupal::currentUser();

    if ($this->autoLogoutSettings->get('use_watchdog')) {
      \Drupal::logger('user')->info('Session automatically closed for %name by autologout.', array('%name' => $user->getAccountName()));
    }

    // Destroy the current session.
    $this->moduleHandler->invokeAll('user_logout', array($user));
    \Drupal::service('session_manager')->destroy();
    $user->setAccount(new AnonymousUserSession());
  }

  /**
   * {@inheritdoc}
   */
  public function autologoutGetRoleTimeout() {
    $roles = user_roles(TRUE);
    $role_timeout = array();

    // Go through roles, get timeouts for each and return as array.
    foreach ($roles as $rid => $role) {
      if ($this->autoLogoutSettings->get('role_' . $rid)) {
        $timeout_role = $this->autoLogoutSettings->get('role_' . $rid . '_timeout');
        $role_timeout[$rid] = $timeout_role;
      }
    }
    return $role_timeout;
  }

  /**
   * {@inheritdoc}
   */
  public function autologoutGetRemainingTime() {
    $timeout = $this->autologoutGetUserTimeout();
    $time_passed = isset($_SESSION['autologout_last']) ? REQUEST_TIME - $_SESSION['autologout_last'] : 0;
    return $timeout - $time_passed;
  }


  /**
   * {@inheritdoc}
   */
  public function autologoutCreateTimer() {
    return $this->autologoutGetRemainingTime();
  }

  /**
   * {@inheritdoc}
   */
  public function autologoutGetUserTimeout($uid = NULL) {
    if (is_null($uid)) {
      // If $uid is not provided, use the logged in user.
      $user = \Drupal::currentUser();
    }
    else {
      $user = User::load($uid);
    }

    if ($user->id() == 0) {
      // Anonymous doesn't get logged out.
      return 0;
    }

    if (is_numeric($user_timeout = $this->autoLogoutSettings->get('user_' . $user->id()))) {
      // User timeout takes precedence.
      return $user_timeout;
    }

    // Get role timeouts for user.
    if ($this->autoLogoutSettings->get('role_logout')) {
      $user_roles = $user->getRoles();
      $output = array();
      $timeouts = $this->autologoutGetRoleTimeout();
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
    return $this->autoLogoutSettings->get('timeout');
  }

  /**
   * {@inheritdoc}
   */
  public function autologoutLogoutRole($user) {
    if ($this->autoLogoutSettings->get('role_logout')) {
      foreach ($user->roles as $key => $role) {
        if ($this->autoLogoutSettings->get('role_' . $key)) {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

}
