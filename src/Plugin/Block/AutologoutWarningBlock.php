<?php

/**
 * @file
 * Contains \Drupal\autologout\Plugin\Block\AutologoutWarningBlock.
 */

namespace Drupal\autologout\Plugin\Block;

use Drupal;
use Drupal\Core\Block\BlockBase;

/**
 * Provides an 'Automated Logout info' block.
 *
 * @Block(
 *   id = "autologout_warning_block",
 *   admin_label = @Translation("Automated Logout info")
 * )
 */
class AutologoutWarningBlock extends BlockBase {


  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {

    $return = array();
    if (Drupal::moduleHandler()->moduleExists('jstimer')) {
      if (!Drupal::moduleHandler()->moduleExists('jst_timer')) {
        drupal_set_message($this->t('The "Widget: timer" module must also be enabled for the dynamic countdown to work in the automated logout block.'), 'error');
      }

      if (Drupal::config('autologout.settings')->get('jstimer_js_load_option', 0) != 1) {
        drupal_set_message($this->t("The Javascript timer module's 'Javascript load options' setting should be set to 'Every page' for the dynamic countdown to work in the automated logout block."), 'error');
      }
    }

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    if (_autologout_prevent()) {
      // Don't display the block if the user is not going
      // to be logged out on this page.
      return;
    }

    if (_autologout_refresh_only()) {
      $markup = $this->t('Autologout does not apply on the current page, you will be kept logged in whilst this page remains open.');
    }
    elseif (Drupal::moduleHandler()->moduleExists('jstimer') && Drupal::moduleHandler()->moduleExists('jst_timer')) {
      $markup = array(drupal_get_form('autologout_create_block_form'));
    }
    else {
      $timeout = (int) Drupal::config('autologout.settings')->get('autologout_timeout', 1800);
      $markup = $this->t('You will be logged out in !time if this page is not refreshed before then.', array('!time' => Drupal::service('date.formatter')->formatInterval($timeout)));
    }
    return array(
      '#type' => 'markup',
      '#markup' => $markup,
    );
  }

}
