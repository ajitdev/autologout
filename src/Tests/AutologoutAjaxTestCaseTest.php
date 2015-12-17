<?php

/**
 * @file
 * Simpletest tests for autologout.
 */
namespace Drupal\autologout\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test the Autologout ajax endpoints.
 *
 * @description Ensure the AJAX endpoints work as expected
 *
 * @group autologout
 */
class AutologoutAjaxTestCaseTest extends WebTestBase {
  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'system_test', 'views', 'user', 'autologout');

  /**
   * User with admin rights.
   */
  protected $privilegedUser;

  /**
   * SetUp() performs any pre-requisite tasks that need to happen.
   */
  public function setUp() {
    parent::setUp();
    // Create and log in our privileged user.
    $this->privilegedUser = $this->drupalCreateUser(array(
      'access content',
      'administer site configuration',
      'access site reports',
      'access administration pages',
      'bypass node access',
      'administer content types',
      'administer nodes',
      'administer autologout',
      'change own logout threshold',
    ));

    $this->drupalLogin($this->privilegedUser);

  }

  /**
   * Test ajax logout callbacks work as expected.
   */
  public function testAutologoutByAjax() {

    $config = \Drupal::configFactory()->getEditable('autologout.settings');
    $config->set('timeout', 100)
      ->set('padding', 10)
      ->save();

    // Check that the user can access the page after login.
    $this->drupalGet('node');
    $this->assertResponse(200, t('Homepage is accessible'));
    $this->assertText(t('Log out'), t('User is still logged in.'));

    // Test the time remaining callback works as expected.
    $result = $this->drupalGet('autologout_ajax_get_time_left');
    $this->assertResponse(200, t('autologout_ajax_get_time_left is accessible when logged in'));
    $result = json_decode($result);
    $this->assertEqual('insert', $result[0]->command, t('autologout_ajax_get_time_left returns an insert command for adding the jstimer onto the page'));
    $this->assertEqual('#timer', $result[0]->selector, t('autologout_ajax_get_time_left specifies the #timer selector.'));
    $this->assert(!empty($result[1]->settings->time) && is_int($result[1]->settings->time) && $result[1]->settings->time > 0, t('autologout_ajax_get_time_left returns the remaining time as a positive integer'));

    // Test that ajax logout works as expected.
    $this->assertResponse(200, t('autologout_ahah_logout is accessible when logged in'));
    $this->drupalGet('autologout_ahah_logout');


    // Check we are now logged out.
    $this->drupalGet('node');
    $this->assertResponse(200, t('Homepage is accessible'));
    $this->assertNoText(t('Log out'), t('User is no longer logged in.'));

    // Check further get time remaining requests return access denied.
    $result = $this->drupalGet('autologout_ajax_get_time_left');
    $result = json_decode($result);
    $this->assertEqual($result[0]->command, 'alert', t('When logged out, autologout_ajax_get_time_left returns the normal Drupal ajax alert.'));

    // Check further logout requests result in access denied.
    $this->drupalGet('autologout_ahah_logout');
    $this->assertResponse(403, t('autologout_ahah logout is not accessible when logged out.'));
  }

  /**
   * Test ajax stay logged in callbacks work as expected.
   */
  public function testStayloggedInByAjax() {
    $config = \Drupal::configFactory()->getEditable('autologout.settings');
    $config->set('timeout', 20)
      ->set('padding', 5)
      ->save();

    // Check that the user can access the page after login.
    $this->drupalGet('node');
    $this->assertResponse(200, t('Homepage is accessible'));
    $this->assertText(t('Log out'), t('User is still logged in.'));

    // Sleep for half the timeout.
    sleep(14);

    // Test that ajax stay logged in works.
    $result = $this->drupalGet('autologout_ahah_set_last');
    $this->assertResponse(200, t('autologout_ahah_set_last is accessible when logged in.'));
    $result = json_decode($result);
    $this->assertEqual('insert', $result[0]->command, t('autologout_ajax_set_last returns an insert command for adding the jstimer onto the page'));
    $this->assertEqual('#timer', $result[0]->selector, t('autologout_ajax_set_last specifies the #timer selector.'));

    // Sleep for half the timeout again.
    sleep(14);

    // Check we are still logged in.
    $this->drupalGet('node');
    $this->assertResponse(200, t('Homepage is accessible'));
    $this->assertText(t('Log out'), t('User is still logged in.'));

    // Logout.
    $this->assertResponse(200, t('autologout_ahah_logout is accessible when logged in.'));
    $this->drupalGet('autologout_ahah_logout');

    // Check further requests to set last result in 403.
    $result = $this->drupalGet('autologout_ahah_set_last');
    $result = json_decode($result);
    $this->assertEqual($result[0]->command, 'alert', t('When logged out, autologout_ajax_set_last returns the normal Drupal ajax alert.'));
  }

}
