<?php
namespace Drupal\autologout\Tests;
use Drupal\simpletest\WebTestBase;

/**
 * Test session cleanup on login.
 *
 * @description Ensure that the autologout module cleans up stale sessions at login
 * @group autologout
 */

class AutologoutTestSessionCleanupOnLoginTest extends WebTestBase {
  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('autologout');
  /**
  * A store references to different sessions.
  */
  protected $curlHandles = array();
  protected $loggedInUsers = array();

  /**
  * setUp() performs any pre-requisite tasks that need to happen.
  */
  public function setUp() {
    parent::setUp();
  // Create and log in our privileged user.
  $this->privileged_user = $this->drupalCreateUser(array('access content', 'administer site configuration', 'access site reports', 'access administration pages', 'bypass node access', 'administer content types', 'administer nodes', 'administer autologout', 'change own logout threshold'));
  }

  /**
  * Test that stale sessions are cleaned up at login.
  */
  public function testSessionCleanupAtLogin() {
  // For the purposes of the test, set the timeout periods to 5 seconds.
    $autologout_settings = \Drupal::config('autologout.settings');
    $autologout_settings->set('timeout', 5)
    ->set('padding', 0)
    ->save();


  // Login in session 1.
  $this->drupalLogin($this->privileged_user);

  // Check one active session.
  $sessions = $this->getSessions($this->privileged_user);
  $this->assertEqual(1, count($sessions), t('After initial login there is one active session'));

  // Switch sessions.
  $session1 = $this->stashSession();

  // Login to session 2.
  $this->drupalLogin($this->privileged_user);

  // Check two active sessions.
  $sessions = $this->getSessions($this->privileged_user);
  $this->assertEqual(2, count($sessions), t('After second login there is now two active session'));

  // Switch sessions.
  $session2 = $this->stashSession();

  // Wait for sessions to expire.
  sleep(6);

  // Login to session 3.
  $this->drupalLogin($this->privileged_user);

  // Check one active session.
  $sessions = $this->getSessions($this->privileged_user);
  $this->assertEqual(1, count($sessions), t('After third login, there is 1 active session, two stale sessions were cleaned up.'));

  // Switch back to session 1 and check no longer logged in.
  $this->restoreSession($session1);
  $this->drupalGet('node');
  $this->assertNoText(t('Log out'), t('User is no longer logged in on session 1.'));

  $this->closeAllSessions();
  }

  /**
  * Get active sessions for given user.
  */
  public function getSessions($account) {
  // Check there is one session in the sessions table.
  $result = db_select('sessions', 's')
  ->fields('s')
  ->condition('uid', $account->uid)
  ->orderBy('timestamp', 'DESC')
  ->execute();

  $sessions = array();
  foreach ($result as $session) {
  $sessions[] = $session;
  }

  return $sessions;
  }

  /**
  * Initialise a new unique session.
  *
  * @return string
  *   Unique identifier for the session just stored.
  *   It is the cookiefile name.
  */
  public function stashSession() {
  if (empty($this->cookieFile)) {
  // No session to stash.
  return;
  }

  // The session_id is the current cookieFile.
  $session_id = $this->cookieFile;

  $this->curlHandles[$session_id] = $this->curlHandle;
  $this->loggedInUsers[$session_id] = $this->loggedInUser;

  // Reset Curl.
  unset($this->curlHandle);
  $this->loggedInUser = FALSE;

  // Set a new unique cookie filename.
  do {
  $this->cookieFile = $this->public_files_directory . '/' . $this->randomName() . '.jar';
  }
  while (isset($this->curlHandles[$this->cookieFile]));

  return $session_id;
  }

  /**
  * Restore a previously stashed session.
  *
  * @param string $session_id
  *   The session to restore as returned by stashSession();
  *   This is also the path to the cookie file.
  *
  * @return string
  *   The old session id that was replaced.
  */
  public function restoreSession($session_id) {
  $old_session_id = NULL;

  if (isset($this->curlHandle)) {
  $old_session_id = $this->stashSession();
  }

  // Restore the specified session.
  $this->curlHandle = $this->curlHandles[$session_id];
  $this->cookieFile = $session_id;
  $this->loggedInUser = $this->loggedInUsers[$session_id];

  return $old_session_id;
  }

  /**
  * Close all stashed sessions and the current session.
  */
  public function closeAllSessions() {
  foreach ($this->curlHandles as $cookie_file => $curl_handle) {
  if (isset($curl_handle)) {
  curl_close($curl_handle);
  }
  }

  // Make the server forget all sessions.
  db_truncate('sessions')->execute();

  $this->curlHandles = array();
  $this->loggedInUsers = array();
  $this->loggedInUser = FALSE;
  $this->cookieFile = $this->public_files_directory . '/' . $this->randomName() . '.jar';
  unset($this->curlHandle);
  }

}
