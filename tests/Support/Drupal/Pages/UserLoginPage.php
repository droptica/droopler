<?php

/**
 * @file
 * Defines elements of various user account pages, such as login and register.
 */

namespace Tests\Support\Drupal\Pages;

/**
 * Class UserLoginPage
 * @package Tests\Support\Drupal\Pages
 */
class UserLoginPage extends Page
{
    /**
     * @var string
     *   URL/path to this page.
     */
    protected static $URL = '/user/login';

    /**
     * @var string
     *   CSS Id of the username form field.
     */
    public static $loginFormUsername = '#edit-name';

    /**
     * @var string
     *   CSS Id of the password form field.
     */
    public static $loginFormPassword = '#edit-pass';

    /**
     * @var string
     *   CSS Id of the login form submit button..
     */
    public static $loginFormSubmit = '#edit-submit';

    /**
     * @var string
     *   The message displayed when a login fails.
     */
    public static $loginFormCredentialsErrorMessage = 'Sorry, unrecognized username or password.';
}
