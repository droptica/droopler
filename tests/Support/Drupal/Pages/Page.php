<?php

/**
 * @file
 * Base class to represent a page on a
 */

namespace Tests\Support\Drupal\Pages;

/**
 * Class Page
 * @package Tests\Support\Drupal\Pages
 */
class Page
{
    /**
     * Navigation bar selector.
     *
     * @var string
     */
    public static $navbar
      = '#navbar';

    /**
     * CSS selector for the breadcrumb.
     *
     * @var string
     */
    public static $breadcrumb
      = '.breadcrumb';

    /**
     * Page title selector.
     *
     * @var string
     */
    public static $pageTitle
      = 'h1, .page-header, .pane-title, .panel-title';

    /**
     * Selector for Drupal messages set by drupal_set_message().
     *
     * @var string
     */
    public static $drupalMessage
      = '.messages.status';

    /**
     * Selector for Drupal error messages set by drupal_set_message().
     *
     * @var string
     */
    public static $drupalWarningMessage
      = '.messages.warning';

    /**
     * Selector for Drupal error messages set by drupal_set_message().
     *
     * @var string
     */
    public static $drupalErrorMessage
      = '.messages.error';

    /**
     * @var string
     */
    public static $sidebarPrimary
      = '#page .main-container .sidebar-primary';

    /**
     * @var string
     */
    public static $sidebarPrimaryBlockTitle
      = '#page .main-container .sidebar-primary .block-title';

    /**
     * @var string
     */
    public static $mainContent
      = '#page .main-container .section-content';

    /**
     * CSS selector for the footer element or region.
     *
     * @var string
     */
    public static $footerRegion
      = '.footer';

    /**
     * CSS selector for the primary tabs.
     *
     * @var string
     */
    public static $editTab
      = '.tabs--primary';

    /**
     * XPath selector used to grab the anchor link within a node's edit tab.
     *
     * @var string
     */
    public static $nodeEditTabLinkSelector
      = 'ul.tabs--primary > li:nth-child(2) > a';

    /**
     * Basic route example for your current URL
     * You can append any additional parameter to URL
     * and use it in tests like: Page\Edit::route('/123-post');
     */
    public static function route($param = '')
    {
        return static::$URL . $param;
    }
}
