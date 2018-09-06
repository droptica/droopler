<?php

namespace Drupal\smtp\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the Drupal 8 SMTP module functionality
 *
 * @group SMTP
 */
class SmtpTest extends WebTestBase {

  /**
   * Modules to install
   *
   * @var array
   */
  public static $modules = ['smtp'];

  /**
   * Perform any initial set up tasks that run before every test method
   */
  public function setUp() {
    parent::setUp();
  }

  /**
   * Tests that the '/' path returns 200
   */
  public function testSiteIsLive() {
    $this->drupalGet('');
    $this->assertResponse(200);
  }
}
