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
 * Class SideBySideParagraphCest
 *
 * @package Tests\Js_capable
 */
class SideBySideParagraphCest
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
     * @throws Exception
     * @before login
     */
    public function addSideBySideToContentPage(JSCapableTester $I)
    {
        $I->wantTo('Add Side by Side to new content page.');
        $I->amOnPage('/node/add/content_page/');
        $I->seeVar(MTOFormField::title());
        $I->fillTextField(FormField::title(), 'Side by sider');
        $I->click(Locator::contains('strong', 'Page Sections'));
        $I->click('.dropbutton-toggle button');
        $page_elements = ParagraphFormField::field_page_section();
        $I->seeVar($page_elements);
        $I->addNewParagraph('d_p_side_by_side', $page_elements);
        $I->fillTextField(FormField::field_d_main_title($page_elements), 'Sidebyside-title');
        $I->click(MTOFormField::field_d_media_icon($page_elements)->__get('open-button'));
        $I->attachImage($I, 'mask.png');
        $I->fillCk5WysiwygEditor(FormField::field_d_long_text($page_elements), 'Loremlorem');
        $I->fillLinkField(FormField::field_d_cta_link($page_elements), 'http://example.com', 'Example');
        $I->click("//*[contains(@data-drupal-selector, 'edit-field-page-section-0')]  
        //*[contains(@class, 'horizontal-tab-button-1')] //a[contains(@href, '#edit-group-items')]");
        $I->click('.dropbutton-toggle button');
        $page_item = ParagraphFormField::field_d_p_sbs_items($page_elements);
        $I->addParagraph('d-p-single-text-block', $page_item);
        $I->fillCk5WysiwygEditor(FormField::field_d_long_text($page_item), 'LoremLorem-left');
        $I->click('.dropbutton-toggle button');
        $I->seeVar($page_item);
        $I->addParagraph('d-p-single-text-block', $page_item);
        $I->waitPageLoad(30);
        $I->scrollTo('#edit-submit');
        $I->fillCk5WysiwygEditor(FormField::field_d_long_text($page_item->next()), 'LoremLorem-right');
        $I->makeScreenshot();
        $I->clickOn(FormField::submit());
        $I->waitPageLoad(30);
        $url = $I->grabFromCurrentUrl();
        Fixtures::add('text_url', $url);
    }

    /**
     * Test if I can see the added Text Paged
     *
     * @param JSCapableTester $I
     *
     */
    public function seeCreatedSideBySideAsRandomUser(JSCapableTester $I)
    {
        $I->wantTo('See if the Text Paged is created');
        $I->amOnPage(Fixtures::get('text_url'));
        $I->see('Sidebyside-title');
        $src_icon = $I->grabAttributeFrom('.d-p-side-by-side__header-row .media-icon img', 'src');
        $I->seeVar($src_icon);
        $I->assertStringContainsString('mask', $src_icon);
        $I->see('Example');
        $I->click('Example');
        $I->see('Example Domain');
        $I->moveBack();
        $I->see('Sidebyside-title');
    }

    /**
     * Removing added Text Paged and checking if it's deleted
     *
     * @param JSCapableTester $I
     * @before login
     */
    public function removeSideBySidePage(JSCapableTester $I)
    {
        $I->wantTo('Clear up after the test');
        $I->amOnPage(Fixtures::get('text_url'));
        $node_nr = $I->grabNodeOfTypeAndTitleNoUser('content_page', 'Side by sider');
        $str = "/node/{$node_nr}/delete";
        $I->seeVar($str);
        $I->click("//a[contains(@href,'$str')]");
        $I->click('#edit-submit');
        $I->seeElement("//*[contains(@class, 'alert-success')]");
    }
}
