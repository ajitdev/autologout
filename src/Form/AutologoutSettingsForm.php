<?php

/**
 * @file
 * Contains \Drupal\autologout\Form\AutologoutSettingsForm.
 */

namespace Drupal\autologout\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides settings for autologout module.
 */
class AutologoutSettingsForm extends ConfigFormBase {

  /**
   * The module manager service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new \Drupal\autologout\Form\AutologoutSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *    The module manager service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler) {
    parent::__construct($config_factory);
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['autologout.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'autologout_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('autologout.settings');
    $form['autologout_timeout'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Timeout value in seconds'),
      '#default_value' => $config->get('timeout'),
      '#size' => 8,
      '#weight' => -10,
      '#description' => $this->t('The length of inactivity time, in seconds, before automated log out.  Must be 60 seconds or greater. Will not be used if role timeout is activated.'),
    );

    $form['autologout_max_timeout'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Max timeout setting'),
      '#default_value' => $config->get('max_timeout'),
      '#size' => 10,
      '#maxlength' => 12,
      '#weight' => -8,
      '#description' => $this->t('The maximum logout threshold time that can be set by users who have the permission to set user level timeouts.'),
    );

    $form['autologout_padding'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Timeout padding'),
      '#default_value' => $config->get('padding'),
      '#size' => 8,
      '#weight' => -6,
      '#description' => $this->t('How many seconds to give a user to respond to the logout dialog before ending their session.'),
    );

    $form['autologout_role_logout'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Role Timeout'),
      '#default_value' => $config->get('role_logout'),
      '#weight' => -4,
      '#description' => $this->t('Enable each role to have its own timeout threshold, a refresh maybe required for changes to take effect. Any role not ticked will use the default timeout value. Any role can have a value of 0 which means that they will never be logged out.'),
    );

    $form['autologout_redirect_url']  = array(
      '#type' => 'textfield',
      '#title' => $this->t('Redirect URL at logout'),
      '#default_value' => $config->get('redirect_url'),
      '#size' => 40,
      '#description' => $this->t('Send users to this internal page when they are logged out.'),
    );

    $form['autologout_no_dialog'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Do not display the logout dialog'),
      '#default_value' => $config->get('no_dialog'),
      '#description' => $this->t('Enable this if you want users to logout right away and skip displaying the logout dialog.'),
    );

    $form['autologout_use_alt_logout_method'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Use alternate logout method'),
      '#default_value' => $config->get('use_alt_logout_method'),
      '#description' => $this->t('Normally when auto logout is triggered, it is done via an AJAX service call. Sites that use an SSO provider, such as CAS, are likely to see this request fail with the error "Origin is not allowed by Access-Control-Allow-Origin". The alternate appraoch is to have the auto logout trigger a page redirect to initiate the logout process instead.'),
    );

    $form['autologout_message']  = array(
      '#type' => 'textarea',
      '#title' => $this->t('Message to display in the logout dialog'),
      '#default_value' => $config->get('message'),
      '#size' => 40,
      '#description' => $this->t('This message must be plain text as it might appear in a JavaScript confirm dialog.'),
    );

    $form['autologout_inactivity_message']  = array(
      '#type' => 'textarea',
      '#title' => $this->t('Message to display to the user after they are logged out.'),
      '#default_value' => $config->get('inactivity_message'),
      '#size' => 40,
      '#description' => $this->t('This message is displayed after the user was logged out due to inactivity. You can leave this blank to show no message to the user.'),
    );

    $form['autologout_use_watchdog'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable watchdog Automated Logout logging'),
      '#default_value' => $config->get('use_watchdog'),
      '#description' => $this->t('Enable logging of automatically logged out users'),
    );

    $form['autologout_enforce_admin'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enforce auto logout on admin pages'),
      '#default_value' => $config->get('enforce_admin'),
      '#description' => $this->t('If checked, then users will be automatically logged out when administering the site.'),
    );

    if ($this->moduleHandler->moduleExists('jstimer') && $this->moduleHandler->moduleExists('jst_timer')) {
      $form['autologout_jstimer_format']  = array(
        '#type' => 'textfield',
        '#title' => $this->t('Autologout block time format'),
        '#default_value' => $config->get('jstimer_format'),
        '#description' => $this->t('Change the display of the dynamic timer.  Available replacement values are: %day%, %month%, %year%, %dow%, %moy%, %years%, %ydays%, %days%, %hours%, %mins%, and %secs%.'),
      );
    }

    $form['table'] = array(
      '#type' => 'table',
      '#weight' => -2,
      '#header' => array(
        'enable' => $this->t('Enable'),
        'name' => $this->t('Role Name'),
        'timeout' => $this->t('Timeout (seconds)'),
      ),
      '#title' => $this->t('If Enabled every user in role will be logged out based on that roles timeout, unless the user has an indivual timeout set.'),
    );

    foreach (user_roles(TRUE) as $key => $role) {
      $form['table'][] = array(
        'autologout_role_' . $key => array(
          '#type' => 'checkbox',
          '#default_value' => $config->get('role_' . $key),
        ),
        'autologout_role' => array(
          '#markup' => $key,
        ),
        'autologout_role_' . $key . '_timeout' => array(
          '#type' => 'textfield',
          '#default_value' => $config->get('role_' . $key . '_timeout'),
          '#size' => 8,
        ),
      );
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $max_timeout = $values['autologout_max_timeout'];

    // Validate timeouts for each role.
    foreach (user_roles(TRUE) as $key => $role) {
      if (empty($values['autologout_role_' . $key])) {
        // Don't validate role timeouts for non enabled roles.
        continue;
      }

      $timeout = $values['autologout_role_' . $key . '_timeout'];
      $validate = autologout_timeout_validate($timeout, $max_timeout);

      if (!$validate) {
        $form_state->setErrorByName('role_' . $key . '_timeout', $this->t('%role role timeout must be an integer greater than 60, less then %max or 0 to disable autologout for that role.', array('%role' => $role, '%max' => $max_timeout)));
      }
    }

    $timeout = $values['autologout_timeout'];

    // Validate timeout.
    if (!is_numeric($timeout) || ((int) $timeout != $timeout) || $timeout < 60 || $timeout > $max_timeout) {
      $form_state->setErrorByName('timeout', $this->t('The timeout must be an integer greater than 60 and less then %max.', array('%max' => $max_timeout)));
    }

    $autologout_redirect_url = $values['autologout_redirect_url'];

    // Validate redirect url.
    if (strpos($autologout_redirect_url, '/') !== 0) {
      $form_state->setErrorByName('redirect_url', $this->t("The user-entered string :autologout_redirect_url must begin with a '/'", array(':autologout_redirect_url' => $autologout_redirect_url)));
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $auto_logout_settings = $this->config('autologout.settings');

    $auto_logout_settings->set('timeout', $values['autologout_timeout'])
      ->set('max_timeout', $values['autologout_max_timeout'])
      ->set('padding', $values['autologout_padding'])
      ->set('role_logout', $values['autologout_role_logout'])
      ->set('redirect_url', $values['autologout_redirect_url'])
      ->set('no_dialog', $values['autologout_no_dialog'])
      ->set('use_alt_logout_method', $values['autologout_use_alt_logout_method'])
      ->set('message', $values['autologout_message'])
      ->set('inactivity_message', $values['autologout_inactivity_message'])
      ->set('use_watchdog', $values['autologout_use_watchdog'])
      ->set('enforce_admin', $values['autologout_enforce_admin'])
      ->save();

    foreach ($values['table'] as $user) {
      foreach ($user as $key => $value) {
        $auto_logout_settings->set($key, $value)->save();
      }
    }

    if (isset($values['autologout_jstimer_format'])) {
      $auto_logout_settings->set('jstimer_format', $values['autologout_jstimer_format'])->save();
    }

    parent::submitForm($form, $form_state);
  }

}
