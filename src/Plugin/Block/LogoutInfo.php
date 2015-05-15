<?php

/**
 * @file
 * Contains \Drupal\autologout\Plugin\Block.
 */

namespace Drupal\autologout\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a block to view automated logout info.
 *
 * @Block(
 *   id = "automated_logout_info",
 * )
 */
class LogoutInfo extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Don't display the block if the user is not going to be logged out.
    if (_autologout_prevent()) {
      return array();
    }

    $block = array();
    if (_autologout_refresh_only()) {
      $block['content'] = t('Autologout does not apply on the current page, you will be kept logged in whilst this page remains open.');
    }
    elseif (\Drupal::moduleHandler()->moduleExists('jstimer') && \Drupal::moduleHandler()->moduleExists('jst_timer')) {
      $block['content'] = array(\Drupal::formBuilder()->getForm('autologout_reset_timeout_form'));
    }
    else {
      $timeout = (int) \Drupal::config('autologout.settings')->get('autologout_timeout');
      $block['content'] = t('You will be logged out in !time if this page is not refreshed before then.',
        array(
          '!time' => \Drupal::service('date.formatter')->formatInterval($timeout)
        )
      );
    }

    return $block;
  }
}
