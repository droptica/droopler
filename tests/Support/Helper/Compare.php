<?php

/**
 * @file
 * Helper to compare test suite.
 */

namespace Tests\Support\Helper;

/**
 * here you can define custom actions
 * all public methods declared in helper class will be available in $I
 *
 * Class Compare
 */
class Compare extends \Codeception\Module
{
    /**
     * Get WebDriver url.
     *
     * @return mixed
     */
    public function getWebDriverUrl()
    {
        return $this->getModule('WebDriver')->_getConfig('url');
    }

    /**
     * Get height of a page.
     *
     * @param $I
     * @return mixed
     */
    public function getPageHeight($I)
    {
        $height = $I->executeJS(
            'var body = document.body,
             html = document.documentElement;
    		     var height = Math.max( window.innerHeight, window.outerHeight, body.scrollHeight, body.offsetHeight,
    		        html.clientHeight, html.scrollHeight, html.offsetHeight )+150;
             return height'
        );
        return $height;
    }

    /**
     * Go to page and compare this page to production.
     *
     * @param \CompareTester $I
     * @param $page - address to page
     * @param $screen_name - name on the screenshot file
     * @param $my_url - url to dev site
     * @param $prod_url - url to prod site
     * @param $no_compare - css elemnets to no compare on dev site
     * @param $no_compare_prod - css elemnets to no compare on prod site
     */
    public function compareToProduction($I, $page, $screen_name, $my_url, $prod_url, $no_compare, $no_compare_prod)
    {
        $I->amOnPage($page);
        $I->wait(3);
        if ($no_compare == "") {
            $I->dontSeeVisualChanges($screen_name, "body");
        } elseif (strpos($no_compare, ",") !== false) {
            $I->dontSeeVisualChanges($screen_name, "body", explode(",", $no_compare));
        } else {
            $I->dontSeeVisualChanges($screen_name, "body", $no_compare);
        }
        $I->amOnUrl($prod_url . $page);
        $I->wait(3);
        if ($no_compare_prod == "") {
            $I->dontSeeVisualChanges($screen_name, "body");
        } elseif (strpos($no_compare_prod, ",") !== false) {
            $I->dontSeeVisualChanges($screen_name, "body", explode(",", $no_compare_prod));
        } else {
            $I->dontSeeVisualChanges($screen_name, "body", $no_compare_prod);
        }
    }
}
