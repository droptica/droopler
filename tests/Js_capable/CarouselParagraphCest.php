<?php

/**
 * @file
 * Carousel Paragraph tests.
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
 * Class CarouselParagraphCest
 *
 * @package Tests\Js_capable
 */
class CarouselParagraphCest
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
     * Test if I can add Carousel to Content Page.
     *
     * @param JSCapableTester $I
     * @throws Exception
     * @before login
     */
    public function addCarouselToContentPage(JSCapableTester $I)
    {
        $I->wantTo('Add Carousel to new content page.');
        $I->amOnPage('/node/add/content_page/');
        $I->seeVar(MTOFormField::title());
        $I->fillTextField(FormField::title(), 'Mans nosukums');
        $I->click(Locator::contains('strong', 'Page Sections'));
        $page_elements = ParagraphFormField::field_page_section();
        $I->seeVar($page_elements);
        $I->click('.dropbutton-toggle button');
        $I->addNewParagraph('d_p_carousel', $page_elements);
        $I->fillTextField(FormField::field_d_main_title($page_elements), 'title');
        $I->click(MTOFormField::field_d_media_icon($page_elements)->__get('open-button'));
        $I->attachImage($I, 'mask.png');
        $I->fillCk5WysiwygEditor(FormField::field_d_long_text($page_elements), 'LoremLorem');
        $I->fillLinkField(FormField::field_d_cta_link($page_elements), 'http://example.com', 'Example');
        $I->click(
            "//*[contains(@data-drupal-selector, 'edit-field-page-section-0')]
             //*[contains(@class, 'horizontal-tab-button-1')] //a[contains(@href, '#edit-group-items')]"
        );
        $page_item = ParagraphFormField::field_d_p_cs_item_reference($page_elements);
        $I->seeVar($page_item);
        $I->fillTextField(FormField::field_d_main_title($page_item), 'title');
        $I->click(MTOFormField::field_d_media_icon($page_item)->__get('open-button'));
        $I->attachImage($I, 'mask.png');
        $I->fillCk5WysiwygEditor(FormField::field_d_long_text($page_item), 'LoremLorem');
        $I->fillSingleLinkField(FormField::field_d_cta_single_link($page_item), 'http://en.droptica.localhost/blog');
        $page_item = ParagraphFormField::field_d_p_cs_item_reference($page_elements)->next();
        $I->seeVar($page_item);
        $I->click('.dropbutton-toggle button');
        $I->addParagraph('d_p_carousel_item', $page_item);
        $I->fillTextField(FormField::field_d_main_title($page_item), 'title');
        $I->click(MTOFormField::field_d_media_icon($page_item)->__get('open-button'));
        $I->attachImage($I, 'mask.png');
        $I->fillCk5WysiwygEditor(FormField::field_d_long_text($page_item), 'LoremLorem');
        $I->fillSingleLinkField(FormField::field_d_cta_single_link($page_item), 'http://en.droptica.localhost/blog');
        $I->clickOn(FormField::submit());
        $I->waitPageLoad(30);
        $url = $I->grabFromCurrentUrl();
        Fixtures::add('carousel_url', $url);
    }

    /**
     * Test if I can see the added Carousel
     *
     * @param JSCapableTester $I
     *
     */
    public function seeCreatedCarouselAsRandomUser(JSCapableTester $I)
    {
        $I->wantTo('see if the banner is created');
        $I->amOnPage(Fixtures::get('carousel_url'));
        $I->see('title');
        $src_icon = $I->grabAttributeFrom('.d-p-carousel-item__content .media-icon img', 'src');
        $I->seeVar($src_icon);
        $I->assertStringContainsString('mask', $src_icon);
        $I->see('Example');
        $I->click('Example');
        $I->see('Example Domain');
        $I->moveBack();
        $I->see('title');
    }

    /**
     * Removing added Carousel and checking if it's deleted
     *
     * @param JSCapableTester $I
     * @before login
     */
    public function removeCarousel(JSCapableTester $I)
    {
        $I->wantTo('clear up after the test');
        $I->amOnPage(Fixtures::get('carousel_url'));
        $node_nr = $I->grabNodeOfTypeAndTitleNoUser('content_page', 'Mans nosukums');
        $str = "/node/{$node_nr}/delete";
        $I->seeVar($str);
        $I->click("//a[contains(@href,'$str')]");
        $I->click('#edit-submit');
        $I->seeElement("//*[contains(@class, 'alert-success')]");
    }
}
