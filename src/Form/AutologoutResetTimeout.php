<?php

/**
 * @file
 * Contains \Drupal\autologout\Form\AutologoutResetTimeout.
 */

namespace Drupal\autologout\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Drupal reset timer form on timer block.
 */
class AutologoutResetTimeout extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'autologout_reset_timeout_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
   $markup = autologout_create_timer();

   $form['autologout_reset'] = array(
     '#type' => 'button',
     '#value' => t('Reset Timeout'),
     '#weight' => 1,
     '#limit_validation_errors' => FALSE,
     '#executes_submit_callback' => FALSE,
     '#ajax' => array(
       'callback' => 'autologout_ahah_set_last',
     ),
   );

   $form['timer'] = array(
     '#markup' => $markup,
   );

   return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
  }
}
