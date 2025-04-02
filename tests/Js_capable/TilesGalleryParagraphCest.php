<?php

/**
 * @file
 * Tiles Gallery Paragraph tests.
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
 * Class TilesGalleryParagraphCest
 *
 * @package Tests\Js_capable
 */
class TilesGalleryParagraphCest
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
     * Test if I can add tiles gallery paragraph to Content Page.
     *
     * @param JSCapableTester $I
     * @throws Exception
     * @before login
     */
    public function addTilesGalleryToContentPage(JSCapableTester $I)
    {
        $I->wantTo('Add tiles gallery paragraph to a new content page.');
        $I->amOnPage('/node/add/content_page/');
        $I->seeVar(MTOFormField::title());
        $I->fillTextField(FormField::title(), 'Mans nosukums');
        $I->click(Locator::contains('strong', 'Page Sections'));
        $page_elements = ParagraphFormField::field_page_section();
        $I->seeVar($page_elements);
        $I->click('.dropbutton-toggle button');
        $I->addNewParagraph('d_p_tiles', $page_elements);
        $I->fillTextField(FormField::field_d_main_title($page_elements), 'title');
        $I->click(MTOFormField::field_d_media_icon($page_elements)->__get('open-button'));
        $I->attachImage($I, 'mask.png');
        try {
            $I->fillTextField(FormField::field_d_long_text($page_elements), 'Lorem');
        } catch (Exception $e) {
            $I->fillCk5WysiwygEditor(FormField::field_d_long_text($page_elements), 'Lorem');
        }
        $I->fillLinkField(FormField::field_d_cta_link($page_elements), 'http://example.com', 'Example');
        $I->click(Locator::contains('strong', 'Items'));
        $I->click(MTOFormField::field_d_media_image($page_elements)->__get('open-button'));
        $I->attachImage($I, 'test.png');
        $I->click(MTOFormField::field_d_media_image($page_elements)->__get('open-button'));
        $I->attachImage($I, 'test.jpeg');
        $I->click(MTOFormField::field_d_media_image($page_elements)->__get('open-button'));
        $I->attachImage($I, 'test.png');
        $I->click(MTOFormField::field_d_media_image($page_elements)->__get('open-button'));
        $I->attachImage($I, 'test.jpeg');
        $I->clickOn(FormField::submit());
        $I->waitPageLoad(30);
        $url = $I->grabFromCurrentUrl();
        Fixtures::add('tilesgallery_url', $url);
    }

    /**
     * Test if I can see the added tiles gallery paragraph
     *
     * @param JSCapableTester $I
     *
     */
    public function seeCreatedTilesGalleryAsRandomUser(JSCapableTester $I)
    {
        $I->wantTo('see if the tiles gallery paragraph is created');
        $I->amOnPage(Fixtures::get('tilesgallery_url'));
        $I->waitPageLoad(30);
        $I->see('title');
        $I->see('Lorem');
        $src_icon = $I->grabAttributeFrom('.d-p-tiles__header-column .media-icon img', 'src');
        $I->seeVar($src_icon);
        $I->assertStringContainsString('mask', $src_icon);
        $src_media = $I->grabAttributeFrom('.tiles-gallery-item__item-content img', 'src');
        $I->seeVar($src_media);
        $I->assertStringContainsString('test', $src_media);
        $src_media = $I->grabAttributeFrom(Locator::elementAt('.tiles-gallery-item__item-content img', 2), 'src');
        $I->seeVar($src_media);
        $I->assertStringContainsString('test', $src_media);
        $src_media = $I->grabAttributeFrom(Locator::elementAt('.tiles-gallery-item__item-content img', 3), 'src');
        $I->seeVar($src_media);
        $I->assertStringContainsString('test', $src_media);
        $src_media = $I->grabAttributeFrom(Locator::lastElement('.tiles-gallery-item__item-content img'), 'src');
        $I->seeVar($src_media);
        $I->assertStringContainsString('test', $src_media);
        $I->see('Example');
        $I->click('Example');
        $I->see('Example Domain');
    }

    /**
     * Removing added tiles gallery paragraph and checking if it's deleted
     *
     * @param JSCapableTester $I
     * @before login
     */
    public function removeTilesGallery(JSCapableTester $I)
    {
        $I->wantTo('clear up after the test');
        $I->amOnPage(Fixtures::get('tilesgallery_url'));
        $node_nr = $I->grabNodeOfTypeAndTitleNoUser('content_page', 'Mans nosukums');
        $str = "/node/{$node_nr}/delete";
        $I->seeVar($str);
        $I->click("//a[contains(@href,'$str')]");
        $I->click('#edit-submit');
        $I->dontSee('title');
    }
}
