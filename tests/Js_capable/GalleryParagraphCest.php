<?php

/**
 * @file
 * Text Paged Paragraph tests.
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
 * Class GalleryParagraphCest
 *
 * @package Tests\Js_capable
 */
class GalleryParagraphCest
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
     * Test if I can add Text Paged to Content Page.
     *
     * @param JSCapableTester $I
     *
     * @throws Exception
     * @before login
     */
    public function addGalleryPagedToContentPage(JSCapableTester $I)
    {
        $I->wantTo('Add Gallery Paged to new content page.');
        $I->amOnPage('/node/add/content_page/');
        $I->seeVar(MTOFormField::title());
        $I->fillTextField(FormField::title(), 'Los galleros');
        $I->click(Locator::contains('strong', 'Page Sections'));
        $I->click('.dropbutton-toggle button');
        $page_elements = ParagraphFormField::field_page_section();
        $I->seeVar($page_elements);
        $I->addNewParagraph('d_p_gallery', $page_elements);
        $I->fillTextField(FormField::field_d_main_title($page_elements), 'title');
        $I->click(MTOFormField::field_d_media_icon($page_elements)->__get('open-button'));
        $I->attachImage($I, 'mask.png');
        $I->fillCk5WysiwygEditor(FormField::field_d_long_text($page_elements), 'Loremlorem');
        $I->fillLinkField(FormField::field_d_cta_link($page_elements), 'http://example.com', 'Example');
        $I->click(Locator::contains('strong', 'Items'));
        $I->click(MTOFormField::field_d_media_image($page_elements)->__get('open-button'));
        $I->attachImage($I, 'test.png');
        $I->click(MTOFormField::field_d_media_image($page_elements)->__get('open-button'));
        $I->attachImage($I, 'test.jpeg');
        $I->click(MTOFormField::field_d_media_image($page_elements)->__get('open-button'));
        $I->attachImage($I, 'test.jpeg');
        $I->click(MTOFormField::field_d_media_image($page_elements)->__get('open-button'));
        $I->attachImage($I, 'test.jpeg');
        $I->clickOn(FormField::submit());
        $I->waitPageLoad(30);
        $I->makeScreenshot('dodanie-nodea.png');
        $url = $I->grabFromCurrentUrl();
        $I->seeVar($url);
        Fixtures::add('text_url', $url);
    }

    /**
     * Test if I can see the added Text Paged
     *
     * @param JSCapableTester $I
     *
     */
    public function seeCreatedTextPagedAsRandomUser(JSCapableTester $I)
    {
        $I->wantTo('See if the Text Paged is created');
        $I->amOnPage(Fixtures::get('text_url'));
        $I->see('title');
        $src_icon = $I->grabAttributeFrom('.d-p-gallery__content-column .media-icon img', 'src');
        $I->seeVar($src_icon);
        $I->assertStringContainsString('mask', $src_icon);
        $I->see('Example');
        $I->click('Example');
        $I->moveBack();
        $I->see('title');
    }

    /**
     * Removing added Text Paged and checking if it's deleted
     *
     * @param JSCapableTester $I
     * @before login
     */
    public function removeTextPaged(JSCapableTester $I)
    {
        $I->wantTo('Clear up after the test');
        $I->amOnPage(Fixtures::get('text_url'));
        $node_nr = $I->grabNodeOfTypeAndTitleNoUser('content_page', 'Los galleros');
        $str = "/node/{$node_nr}/delete";
        $I->seeVar($str);
        $I->click("//a[contains(@href,'$str')]");
        $I->click('#edit-submit');
        $I->seeElement("//*[contains(@class, 'alert-success')]");
    }
}
