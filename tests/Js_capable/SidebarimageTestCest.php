<?php

/**
 * @file
 * Sidebar image Paragraph tests.
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
 * Class SidebarImageParagraphCest
 *
 * @package Tests\Js_capable
 */
class SidebarimageTestCest
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
     * Test if I can add sidebarimage to Content Page.
     *
     * @param JSCapableTester $I
     * @throws Exception
     * @before login
     */
    public function addSidebarimageToContentPage(JSCapableTester $I)
    {
        $I->wantTo('Add SidebarImageParagraph to new content page.');
        $I->amOnPage('/node/add/content_page/');
        $I->fillTextField(FormField::title(), 'Noman sukums');
        $I->click(Locator::contains('strong', 'Page Sections'));
        $page_elements = ParagraphFormField::field_page_section();
        $I->seeVar($page_elements);
        $I->click('.dropbutton-toggle button');
        $page_elements = ParagraphFormField::field_page_section();
        $I->seeVar($page_elements);
        $I->addNewParagraph('d_p_side_image', $page_elements);
        $I->fillTextField(FormField::field_d_main_title($page_elements), 'title');
        $I->click(MTOFormField::field_d_media_background($page_elements)->__get('open-button'));
        $I->attachImage($I, 'test.jpeg');
        $I->fillWysiwygEditor(FormField::field_d_long_text($page_elements), 'Lorem ipsum');
        $I->fillLinkField(FormField::field_d_cta_link($page_elements), 'http://example.com', 'Example');
        $I->clickOn(FormField::submit());
        $I->waitPageLoad(30);
        $url = $I->grabFromCurrentUrl();
        Fixtures::add('text_url', $url);
    }

    /**
     * Test if I can see the added tekst
     *
     * @param JSCapableTester $I
     *
     */
    public function seeCreatedTextAsRandomUser(JSCapableTester $I)
    {
        $I->wantTo('see if the tekst is created');
        $I->amOnPage(Fixtures::get('text_url'));
        $I->see('title');
        $src_background = $I->grabAttributeFrom('.d-p-side-image__background .media img', 'src');
        $I->seeVar($src_background);
        $I->assertStringContainsString('test', $src_background);
        $I->see('Example');
        $I->click('Example');
        $I->see('Example Domain');
        $I->moveBack();
        $I->see('title');
    }

    /**
     * Removing added text and checking if it's deleted
     *
     * @param JSCapableTester $I
     * @before login
     */
    public function removeText(JSCapableTester $I)
    {
        $I->wantTo('clear up after the test');
        $I->amOnPage(Fixtures::get('text_url'));
        $node_nr = $I->grabNodeOfTypeAndTitleNoUser('content_page', 'Noman sukums');
        $str = "/node/{$node_nr}/delete";
        $I->seeVar($str);
        $I->click("//a[contains(@href,'$str')]");
        $I->click('#edit-submit');
        $I->seeElement("//*[contains(@class, 'alert-success')]");
    }
}
