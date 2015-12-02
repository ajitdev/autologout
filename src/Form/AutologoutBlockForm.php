<?php

/**
 * @file
 * Contains \Drupal\autologout\Form\AutologoutBlockForm.
 */

namespace Drupal\autologout\Form;

use Drupal;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a settings for autologout modle.
 */
class AutologoutBlockForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return [
      'autologout.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'autologout_block_settings';
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
    //print_r($form);
    return parent::buildForm($form, $form_state);
  }
}
