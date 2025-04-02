<?php

namespace Tests\Support\Step;

use Tests\Support\AcceptanceTester;
use Tests\Support\Drupal\Pages\HomePage;
use Tests\Support\Drupal\Pages\UserLoginPage;

/**
 * Class UserCommonSteps
 * @package Tests\Support\Step
 */
trait UserCommonSteps
{
  /**
   * Login user.
   *
   * @param string $username
   * @param string $password
   */
    public function login($username = 'admin', $password = '123')
    {
      /** @var AcceptanceTester $I */
        $I = $this;
        $I->amOnPage(UserLoginPage::route());
        $url = $I->grabFromCurrentUrl();
        $I->seeVar($url);
        if ($url != '/user/login') {
            $this->logout();
            $I->amOnPage(UserLoginPage::route());
        }
        $I->fillField(UserLoginPage::$loginFormUsername, $username);
        $I->fillField(UserLoginPage::$loginFormPassword, $password);
        $I->click('Log in');
        $I->see('Log out');
    }

  /**
   * Logout user.
   */
    public function logout()
    {
      /** @var AcceptanceTester $I */
        $I = $this;
        $I->amOnPage('/user/logout');
    }

  /**
   * Check if user is logged in.
   */
    public function userIsLoggedIn()
    {
      /** @var AcceptanceTester $I */
        $I = $this;
        $I->amOnPage(HomePage::route());
        $I->see('Log out');
    }
}
