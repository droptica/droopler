<?php

/**
 * @file
 * Helper to improve waiting to load page.
 */

namespace Tests\Support\Helper;

use Codeception\TestInterface;

/**
 * here you can define custom actions
 * all public methods declared in helper class will be available in $I
 *
 * Class PageLoad
 */
class PageLoad extends \Codeception\Module
{
    /**
     * @var null
     */
    private $webDriver = null;

    /**
     * @var null
     */
    private $webDriverModule = null;

    /**
     * Event hook before a test starts.
     *
     * @param \Codeception\TestInterface $test
     *
     * @throws \Exception
     */
    public function _before(TestInterface $test)
    {
        if (!$this->hasModule('WebDriver') && !$this->hasModule('Selenium2')) {
            throw new \Exception('PageWait uses the WebDriver. Please be sure that this module is activated.');
        }
        // Use WebDriver
        if ($this->hasModule('WebDriver')) {
            $this->webDriverModule = $this->getModule('WebDriver');
            $this->webDriver = $this->webDriverModule->webDriver;
        }
    }

    /**
     * Wait for load all ajax.
     *
     * @param $timeout
     */
    public function waitAjaxLoad($timeout = 10)
    {
        $this->webDriverModule->waitForJS('return !!window.jQuery && window.jQuery.active == 0;', $timeout);
        $this->webDriverModule->wait(3);
    }

    /**
     * Wait for load page.
     *
     * @param $timeout
     */
    public function waitPageLoad($timeout = 10)
    {
        $this->webDriverModule->waitForJs('return document.readyState == "complete"', $timeout);
        $this->waitAjaxLoad($timeout);
    }

    /**
     * Wait for load page with lazy load.
     *
     * @param $timeout
     */
    public function waitPageLoadLazyLoad($timeout = 10)
    {
        $this->webDriverModule->waitForJs('return document.readyState == "complete"', $timeout);
        $this->waitAjaxLoad($timeout);
        $this->webDriverModule->wait(10);
    }
}
