<?php

/**
 * @file
 * Banner Paragraph tests.
 */

namespace Tests\Js_capable;

use Codeception\Util\Drupal\FormField;
use Codeception\Util\Drupal\ParagraphFormField;
use Codeception\Util\Drupal\MTOFormField;
use Codeception\Util\Fixtures;
use Codeception\Util\Locator;
use Exception;
use Tests\Support\JSCapableTester;

/**
 * Class BannerParagraphCest
 *
 * @package Tests\Js_capable
 */
class BannerParagraphCest
{
    /**
     * Setup environment before each test.
     *
     * @param JSCapableTester $I
     */
    protected function login(JSCapableTester $I)
    {
        $I->amOnPage('/user');
        $url = $I->getLoginUri('user_admin');
        $I->amOnPage($url);
    }

    /**
     * Test if I can add banner to Content Page.
     *
     * @param JSCapableTester $I
     * @throws Exception
     * @before login
     */
    public function addBannerToContentPage(JSCapableTester $I)
    {
        $I->wantTo('Add banner to new content page.');
        $I->amOnPage('/node/add/content_page/');
        $I->seeVar(MTOFormField::title());
        $I->fillTextField(FormField::title(), 'Mans nosukums');
        $I->click(Locator::contains('strong', 'Page Sections'));
        $page_elements = ParagraphFormField::field_page_section();
        $I->seeVar($page_elements);
        $I->addNewParagraph('d_p_banner', $page_elements);
        $I->fillTextField(FormField::field_d_main_title($page_elements), 'title');
        $I->fillTextField(FormField::field_d_subtitle($page_elements), 'Loremlorem');
        $I->click(MTOFormField::field_d_media_background($page_elements)->__get('open-button'));
        $I->attachImage($I, 'test.jpeg');
        $I->click(MTOFormField::field_d_media_icon($page_elements)->__get('open-button'));
        $I->attachImage($I, 'mask.png');
        $I->fillCk5WysiwygEditor(FormField::field_d_long_text($page_elements), 'Lorem');
        $I->fillLinkField(FormField::field_d_cta_link($page_elements), 'http://example.com', 'Example');
        $I->click('#gin-sticky-edit-submit');
        $I->waitPageLoad(30);
        $url = $I->grabFromCurrentUrl();
        Fixtures::add('banner_url', $url);
    }

    /**
     * Test if I can see the added banner
     *
     * @param JSCapableTester $I
     *
     */
    public function seeCreatedBannerAsRandomUser(JSCapableTester $I)
    {
        $I->wantTo('see if the banner is created');
        $I->amOnPage(Fixtures::get('banner_url'));
        $I->see('title');
        $I->see('Loremlorem');
        $src_icon = $I->grabAttributeFrom('.media-icon img', 'src');
        $I->seeVar($src_icon);
        $I->assertStringContainsString('mask', $src_icon);
        $src_background = $I->grabAttributeFrom('.d-p-banner__background-media img', 'src');
        $I->seeVar($src_background);
        $I->assertStringContainsString('test', $src_background);
        $I->see('Example');
        $I->click('Example');
        $I->see('Example Domain');
        $I->moveBack();
        $I->see('lorem');
    }

    /**
     * Removing added banner and checking if it's deleted
     *
     * @param JSCapableTester $I
     * @before login
     */
    public function removeBanner(JSCapableTester $I)
    {
        $I->wantTo('clear up after the test');
        $I->amOnPage(Fixtures::get('banner_url'));
        $node_nr = $I->grabNodeOfTypeAndTitleNoUser('content_page', 'Mans nosukums');
        $str = "/node/{$node_nr}/delete";
        $I->seeVar($str);
        $I->click("//a[contains(@href,'$str')]");
        $I->click('#edit-submit');
        $I->seeElement("//*[contains(@class, 'alert-success')]");
    }
}
