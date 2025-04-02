<?php

namespace Tests\Support\Drupal\Pages;

/**
 * Class AccessDenied
 * @package Tests\Support\Drupal\Pages
 */
class AccessDenied extends NodePage
{
    /**
     * Declare UI map for this page here. CSS or XPath allowed.
     * public static $usernameField = '#username';
     * public static $formSubmitButton = "#mainForm input[type=submit]";
     */

    /**
     * @var string
     */
    protected static $URL = '/access-denied';

    /**
     * @var string
     */
    public static $accessDeniedMessage = 'Access denied';
}
