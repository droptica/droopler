<?php

/**
 * @file
 * Text with image background tests.
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
 * Class TextWithImageBackgroundCest
 *
 * @package Tests\Js_capable
 */
class TextWithImageBackgroundCest
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
     * Test if I can add Text with image background to Content Page.
     *
     * @param JSCapableTester $I
     * @throws Exception
     * @before login
     */
    public function addTextWithImageBackgroundToContentPage(JSCapableTester $I)
    {
        $I->wantTo('Add Text with image background to new content page.');
        $I->amOnPage('/node/add/content_page/');
        $I->seeVar(MTOFormField::title());
        $I->fillTextField(FormField::title(), 'KrzysiekTest');
        $I->click(Locator::contains('strong', 'Page Sections'));
        $page_elements = ParagraphFormField::field_page_section();
        $I->seeVar($page_elements);
        $I->click('.dropbutton-toggle button');
        $I->addNewParagraph('d_p_text_paged', $page_elements);
        $I->fillTextField(FormField::field_d_main_title($page_elements), 'TytulKrzysiek');
        $I->fillTextField(FormField::field_d_subtitle($page_elements), 'Loremlorem');
        $I->click(MTOFormField::field_d_media_icon($page_elements)->__get('open-button'));
        $I->attachImage($I, 'mask.png');
        $I->click(MTOFormField::field_d_media_background_image($page_elements)->__get('open-button'));
        $I->attachImage($I, 'test.jpeg');
        $I->fillCk5WysiwygEditor(FormField::field_d_long_text($page_elements), 'LongTextKrzysiek');
        $I->fillLinkField(FormField::field_d_cta_link($page_elements), 'http://example.com', 'Example');
        $I->clickOn(FormField::submit());
        $I->waitPageLoad(30);
        $url = $I->grabFromCurrentUrl();
        Fixtures::add('TextWithImageBackground_url', $url);
    }

    /**
     * Test if I can see the added Text with image background
     *
     * @param JSCapableTester $I
     *
     */
    public function seeCreatedTextWithImageBackgroundAsRandomUser(JSCapableTester $I)
    {
        $I->wantTo('see if the TextWithImageBackground is created');
        $I->amOnPage(Fixtures::get('TextWithImageBackground_url'));
        $I->see('TytulKrzysiek');
        $I->see('Loremlorem');
        $src_icon = $I->grabAttributeFrom('.d-p-text-paged__content-column .media-icon img', 'src');
        $I->seeVar($src_icon);
        $I->assertStringContainsString('mask', $src_icon);
        $src_background = $I->grabAttributeFrom('.d-p-text-paged__background .media--background img', 'src');
        $I->seeVar($src_background);
        $I->assertStringContainsString('test', $src_background);
        $I->see('Example');
        $I->click('Example');
        $I->see('Example Domain');
        $I->moveBack();
        $I->see('lorem');
    }

    /**
     * Removing added Text with image background and checking if it's deleted
     *
     * @param JSCapableTester $I
     * @before login
     */
    public function removeTextWithImageBackground(JSCapableTester $I)
    {
        $I->wantTo('clear up after the test');
        $I->amOnPage(Fixtures::get('TextWithImageBackground_url'));
        $node_nr = $I->grabNodeOfTypeAndTitleNoUser('content_page', 'KrzysiekTest');
        $str = "/node/{$node_nr}/delete";
        $I->seeVar($str);
        $I->click("//a[contains(@href,'$str')]");
        $I->click('#edit-submit');
        $I->seeElement("//*[contains(@class, 'alert-success')]");
    }
}
