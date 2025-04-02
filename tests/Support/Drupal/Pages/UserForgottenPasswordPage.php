<?php

/**
 * @file
 * Defines elements of various user account pages, such as login and register.
 */

namespace Tests\Support\Drupal\Pages;

/**
 * Class UserForgottenPasswordPage
 * @package Tests\Support\Drupal\Pages
 */
class UserForgottenPasswordPage extends Page
{
    /**
     * @var string
     *   URL/path to this page.
     */
    protected static $URL = '/user/password';

    /**
     * Declare UI map for this page here. CSS or XPath allowed.
     * public static $usernameField = '#username';
     * public static $formSubmitButton = "#mainForm input[type=submit]";
     */

    /**
     * @var string
     */
    public static $forgottenPasswordFormUsernameSelector = '#edit-name';

    /**
     * @var string
     */
    public static $forgottenPasswordFormSubmitSelector = '#edit-submit';

    /**
     * @var string
     */
    public static $forgottenPasswordFormCredentialsErrorMessage =
      'Sorry, %s is not recognized as a user name or an e-mail address.';
}
