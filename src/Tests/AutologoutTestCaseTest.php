<?php

/**
 * @file
 */
namespace Drupal\autologout\Tests;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the autologout's features.
 *
 * @description Ensure that the autologout module functions as expected
 *
 * @group autologout
 */
class AutologoutTestCaseTest extends WebTestBase {
  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'system_test', 'views', 'user', 'autologout', 'menu_ui','block');
  /**
   * User with admin rights.
   */
  protected $privilegedUser;

  /**
   * The config object for 'autologout.settings'.
   */
  protected $config;

  /**
   * SetUp() performs any pre-requisite tasks that need to happen.
   */
  public function setUp() {
    parent::setUp();
    // Create and log in our privileged user.
    $this->privilegedUser = $this->drupalCreateUser(array('access content', 'administer site configuration', 'access site reports', 'access administration pages', 'bypass node access', 'administer content types', 'administer nodes', 'administer autologout', 'change own logout threshold'));
    $this->drupalLogin($this->privilegedUser);
    $this->config = \Drupal::configFactory()->getEditable('autologout.settings');
    // For the purposes of the test, set the timeout periods to 10 seconds.
    $this->config->set('timeout', 10)
      ->save();

    $this->drupalLogin($this->privilegedUser);

    // Make node page default.
    $this->config('system.site')->set('page.front', 'node')->save();
    // Place the User login block on the home page to verify Log out text.
    $this->drupalPlaceBlock('system_menu_block:account');
  }

  /**
   * Test the precedence of the timeouts.
   *
   * This tests the following function:
   * _autologout_get_user_timeout();
   */
  public function testAutologoutTimeoutPrecedence() {
    $uid = $this->privilegedUser->uid;
    // Default used if no role is specified.
    $this->config->set('timeout', 100)
      ->set('role_logout', FALSE)
      ->set('role_authenticated', FALSE)
      ->set('role_authenticated_timeout', 200)
      ->save();
    $this->assertAutotimeout($uid, 100, t('User timeout uses default if no other option set'));

    // Default used if role selected but no user's role is selected.
    $this->config->set('role_logout', TRUE)
      ->set('role_authenticated', FALSE)
      ->set('role_authenticated_timeout', 200)
      ->save();
    $this->assertAutotimeout($uid, 100, t('User timeout uses default if  role timeouts are used but not one of the current user.'));

    // Role timeout is used if user's role is selected.
    $this->config->set('role_logout', TRUE)
      ->set('role_authenticated', TRUE)
      ->set('role_authenticated_timeout', 200)
      ->save();
    $this->assertAutotimeout($uid, 200, t('User timeout uses role value'));

    // Role timeout is used if user's role is selected.
    $this->config->set('role_logout', TRUE)
      ->set('role_authenticated', TRUE)
      ->set('role_authenticated_timeout', 0)
      ->save();
    $this->assertAutotimeout($uid, 0, t('User timeout uses role value of 0 if set for one of the user roles.'));

    // Role timeout used if personal timeout is empty string.
    $this->config->set('role_logout', TRUE)
      ->set('role_authenticated', TRUE)
      ->set('role_authenticated_timeout', 200)
      ->set('user_' . $uid, '')
      ->save();
    $this->assertAutotimeout($uid, 200, t('User timeout uses role value if personal value is the empty string.'));

    // Default timeout used if personal timeout is empty string.
    $this->config->set('role_logout', TRUE)
      ->set('role_authenticated', FALSE)
      ->set('role_authenticated_timeout', 200)
      ->set('user_' . $uid, '')
      ->save();
    $this->assertAutotimeout($uid, 100, t('User timeout uses default value if personal value is the empty string and no role timeout is specified.'));

    // Personal timeout used if set.
    $this->config->set('role_logout', TRUE)
      ->set('role_authenticated', FALSE)
      ->set('role_authenticated_timeout', 200)
      ->set('user_' . $uid, 300)
      ->save();
    $this->assertAutotimeout($uid, 300, t('User timeout uses default value if personal value is the empty string and no role timeout is specified.'));
  }

  /**
   * Test a user is logged out after the default timeout period.
   */
  public function testAutologoutDefaultTimeout() {
    // Check that the user can access the page after login.
    $this->drupalGet('node');
    $this->assertResponse(200, t('Homepage is accessible'));
    $this->assertText(t('Log out'), t('User is still logged in.'));

    // Wait for timeout period to elapse.
    sleep(20);

    // Check we are now logged out.
    $this->drupalGet('node');
    $this->assertResponse(200, t('Homepage is accessible'));
    $this->assertNoText(t('Log out'), t('User is no longer logged in.'));
    $this->assertText(t('You have been logged out due to inactivity.'), t('User sees inactivity message.'));
  }

  /**
   * Test a user is not logged out within the default timeout period.
   */
  public function testAutologoutNoLogoutInsideTimeout() {
    // Check that the user can access the page after login.
    $this->drupalGet('node');
    $this->assertResponse(200, t('Homepage is accessible'));
    $this->assertText(t('Log out'), t('User is still logged in.'));

    // Wait within the timeout period.
    sleep(10);

    // Check we are still logged in.
    $this->drupalGet('node');
    $this->assertResponse(200, t('Homepage is accessible'));
    $this->assertText(t('Log out'), t('User is still logged in.'));
    $this->assertNoText(t('You have been logged out due to inactivity.'), t('User does not see inactivity message.'));
  }

  /**
   * Test the behaviour of the settings for submission.
   */
  public function testAutologoutSettingsForm() {
    $edit = array();
    $this->config->set('max_timeout', 1000)
      ->save();

    // Test that it is possible to set a value above the max_timeout
    // threshold.
    $edit['autologout_timeout'] = 1500;
    $edit['autologout_max_timeout'] = 2000;
    $edit['autologout_padding'] = 60;
    $edit['autologout_role_logout'] = TRUE;
    $edit['autologout_redirect_url'] = '/user/login';

    $this->drupalPostForm('admin/config/people/autologout', $edit, t('Save configuration'));
    $this->assertText(t('The configuration options have been saved.'), t('Unable to save autologout config when modifying the max timeout.'));

    // Test that out of range values are picked up.
    $edit['autologout_timeout'] = 2500;
    $edit['autologout_max_timeout'] = 2000;
    $edit['autologout_padding'] = 60;
    $edit['autologout_redirect_url'] = TRUE;

    $this->drupalPostForm('admin/config/people/autologout', $edit, t('Save configuration'));
    $this->assertNoText(t('The configuration options have been saved.'), t('Saved configuration despite the autologout_timeout being too large.'));

    // Test that out of range values are picked up.
    $edit['autologout_timeout'] = 1500;
    $edit['autologout_max_timeout'] = 2000;
    $edit['autologout_padding'] = 60;
    $edit['autologout_redirect_url'] = TRUE;

    $this->drupalPostForm('admin/config/people/autologout', $edit, t('Save configuration'));
    $this->assertNoText(t('The configuration options have been saved.'), t('Saved configuration despite a role timeout being too large.'));

    // Test that role timeouts are not validated for
    // disabled roles.
    $edit['autologout_timeout'] = 1500;
    $edit['autologout_max_timeout'] = 2000;
    $edit['autologout_padding'] = 60;
    $edit['autologout_role_logout'] = TRUE;
    $edit['autologout_redirect_url'] = '/user/login';

    $this->drupalPostForm('admin/config/people/autologout', $edit, t('Save configuration'));
    $this->assertText(t('The configuration options have been saved.'), t('Unable to save autologout due to out of range role timeout for a role which is not enabled..'));
  }

  /**
   * Test enforce logout on admin page settings.
   */
  public function testAutlogoutOfAdminPages() {

    // Set an admin page path.
    $_GET['q'] = 'admin';

    // Check if user will be kept logged in on admin paths with enforce dsabled.
    $this->config->set('enforce_admin', FALSE)
      ->save();
    $this->assertEqual(autologout_autologout_refresh_only(), TRUE, t('Autologout does logout of admin pages without enforce on admin checked.'));

    // Check if user will not be kept logged in on admin paths if enforce enabled.
    $this->config->set('enforce_admin', TRUE)
      ->save();
    $this->assertEqual(autologout_autologout_refresh_only(), FALSE, t('Autologout does not logout of admin pages with enforce on admin not checked.'));

    // Set a non admin page path.
    $_GET['q'] = 'node';

    $this->config->set('enforce_admin', FALSE)
      ->save();
    $this->assertEqual(autologout_autologout_refresh_only(), FALSE, t('autologout_autologout_refresh_only() returns FALSE on non admin page when enforce is disabled.'));
    $this->config->set('enforce_admin', TRUE)
      ->save();
    $this->assertEqual(autologout_autologout_refresh_only(), FALSE, t('autologout_autologout_refresh_only() returns FALSE on non admin page when enforce is enabled.'));
  }

  /**
   * Test a user is logged out and denied access to admin pages.
   */
  public function testAutologoutDefaultTimeoutAccessDeniedToAdmin() {
    // Enforce auto logout of admin pages.
    $this->config->set('enforce_admin', FALSE)
      ->save();

    // Check that the user can access the page after login.
    $this->drupalGet('admin/reports/status');
    $this->assertResponse(200, t('Admin page is accessible'));
    $this->assertText(t('Log out'), t('User is still logged in.'));
    $this->assertText(t("Here you can find a short overview of your site's parameters as well as any problems detected with your installation."), t('User can access elements of the admin page.'));

    // Wait for timeout period to elapse.
    sleep(20);

    // Check we are now logged out.
    $this->drupalGet('admin/reports/status');
    $this->assertResponse(403, t('Admin page returns 403 access denied.'));
    $this->assertNoText(t('Log out'), t('User is no longer logged in.'));
    $this->assertNoText(t("Here you can find a short overview of your site's parameters as well as any problems detected with your installation."), t('User cannot access elements of the admin page.'));
    $this->assertText(t('You have been logged out due to inactivity.'), t('User sees inactivity message.'));
  }

  /**
   * Test integration with the remember me module.
   *
   * Users who checked remember_me on login should never be logged out.
   */
  public function testNoAutologoutWithRememberMe() {
    // Set the remember_me module data bit to TRUE.
    $this->privilegedUser->data['remember_me'] = TRUE;
    $this->privilegedUser->save();

    // Check that the user can access the page after login.
    $this->drupalGet('node');
    $this->assertResponse(200, t('Homepage is accessible'));
    $this->assertText(t('Log out'), t('User is still logged in.'));

    // Wait for timeout period to elapse.
    sleep(20);

    // Check we are still logged in.
    $this->drupalGet('node');
    $this->assertResponse(200, t('Homepage is accessible'));
    $this->assertText(t('Log out'), t('User is still logged in after timeout with remember_me on.'));
  }

  /**
   * Assert the timeout for a particular user.
   *
   * @param int $uid
   *   User uid to assert the timeout for.
   * @param int $expected_timeout
   *   The expected timeout.
   * @param string $message
   *   The test message
   * @param string $group
   *   The test grouping
   */
  public function assertAutotimeout($uid, $expected_timeout, $message = '', $group = '') {
    return $this->assertEqual(\Drupal::service('autologout.manager')->autologoutGetUserTimeout($uid), $expected_timeout, $message, $group);
  }

}
