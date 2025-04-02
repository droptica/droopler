<?php

/**
 * @file
 * Visualception tests to compare dev pages to production.
 */

namespace Tests\Compare;

use Codeception\Util\Shared\Asserts;
use Tests\Support\CompareTester;

/**
 * Class ComparePagestoProductionCest
 *
 * @package compare
 */
class ComparePagestoProductionCest
{
    use Asserts;

    /**
     * @var string list of pages
     */
    private $list_pages;

    /**
     * @var string link to sitemap.xml
     */
    private $xml_sitemap;

    /**
     * @var string on/off checking links from sitemap
     */
    private $xml_checking;

    /**
     * @var string my URL
     */
    private $my_url;

    /**
     * @var array no compare elements
     */
    private $no_compare;

    /**
     * @var array no compare elements on production site
     */
    private $no_compare_prod;

    /**
     * @var string production URL
     */
    private $prod_url;

    /**
     * ComparePagestoProductionCest constructor.
     */
    public function __construct()
    {
        $this->my_url = getenv('DEV_URI');
        $this->prod_url = getenv('PROD_URI');
        $this->list_pages = getenv('LIST_PAGES');
        $this->xml_checking = getenv('CHECKING_FROM_XML_SITE_MAP');
        $this->xml_sitemap = getenv('XML_SITE_MAP_URL');
        $this->no_compare = getenv('DEV_NO_COMPARE');
        $this->no_compare_prod = getenv('PROD_NO_COMPARE');
    }

    /**
     * Provide pages list to compare with production.
     *
     * @return array
     */
    protected function pageProvider()
    {
        $pages = array_map(function ($page) {
            return array_combine(['url', 'title'], explode(',', $page));
        }, explode(';', substr($this->list_pages, 1, -1)));

        if (!empty($this->xml_checking)) {
            $pages_sitemap = $this->parseXmlSiteMapToPageList();
            return array_merge($pages_sitemap, $pages);
        }
        return $pages;
    }

    /**
     * Compares the screenshots Production Desktop.
     * @dataProvider pageProvider
     * @env desktop
     * @param CompareTester $I
     * @param $page - page from provider
     */
    public function screenshotProductionDesktop(CompareTester $I, \Codeception\Example $page)
    {
        $I->wantTo('Compare screenshots on ' . $page['title']);
        $I->compareToProduction(
            $I,
            $page['url'],
            $page['title'],
            $this->my_url,
            $this->prod_url,
            $this->no_compare,
            $this->no_compare_prod
        );
    }

    /**
     * Compares the screenshots Production Tablet.
     * @dataProvider pageProvider
     * @env tablet
     * @param CompareTester $I
     * @param $page - page from provider
     */
    public function screenshotProductionTablet(CompareTester $I, \Codeception\Example $page)
    {
        $I->wantTo('Compare screenshots on tablet ' . $page['title']);
        $I->compareToProduction(
            $I,
            $page['url'],
            $page['title'] . " tablet",
            $this->my_url,
            $this->prod_url,
            $this->no_compare,
            $this->no_compare_prod
        );
    }

    /**
     * Compares the screenshots Production mobile.
     * @dataProvider pageProvider
     * @env mobile
     * @param CompareTester $I
     * @param $page - page from provider
     */
    public function screenshotProductionMobile(CompareTester $I, \Codeception\Example $page)
    {
        $I->wantTo('Compare screenshots on mobile ' . $page['title']);
        $I->compareToProduction(
            $I,
            $page['url'],
            $page['title'] . " mobile",
            $this->my_url,
            $this->prod_url,
            $this->no_compare,
            $this->no_compare_prod
        );
    }

    /**
     * Off/ON Checking links from sitemap.
     *
     * @return string
     */
    protected function getXmlChecking()
    {
        return $this->xml_checking;
    }

    /**
     * Get url to sitemap.
     *
     * @return string
     */
    protected function getXmlSitemap()
    {
        return $this->xml_sitemap;
    }

    /**
     * Parse xml sitemap to pages list array.
     * @return array
     */
    protected function parseXmlSiteMapToPageList()
    {
        $pages_sitemap = [];
        $url = file_get_contents($this->xml_sitemap);
        if ($url) {
            $xml = new SimpleXMLElement($url);
            foreach ($xml->url as $item) {
                $url = parse_url($item->loc);
                $pages_sitemap[] = [
                    'url' => $url["path"],
                    'title' => 'Sitemap@' . str_replace('/', '@', $url["path"]),
                ];
            }
        }

        return $pages_sitemap;
    }
}
