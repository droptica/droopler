<?php

/**
 * @file
 * Group Text Blocks Paragraph tests.
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
 * Class GroupTextBlocksCest
 *
 * @package Tests\Js_capable
 */
class GroupTextBlocksCest
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
     * Test if I can add Group of text blocks to Content Page.
     *
     * @param JSCapableTester $I
     * @throws Exception
     * @before login
     */
    public function addGroupTextBlocksToContentPage(JSCapableTester $I)
    {
        $I->wantTo('Add Group of text blocks to new content page.');
        $I->amOnPage('/node/add/content_page/');
        $I->seeVar(MTOFormField::title());
        $I->fillTextField(FormField::title(), 'Mans nosukums');
        $I->click(Locator::contains('strong', 'Page Sections'));
        $page_elements = ParagraphFormField::field_page_section();
        $I->seeVar($page_elements);
        $I->click('.dropbutton-toggle button');
        $I->addNewParagraph('d_p_group_of_text_blocks', $page_elements);
        $I->fillTextField(FormField::field_d_main_title($page_elements), 'title');
        $I->click(MTOFormField::field_d_media_icon($page_elements)->__get('open-button'));
        $I->attachImage($I, 'mask.png');
        $I->fillCk5WysiwygEditor(FormField::field_d_long_text($page_elements), 'LoremLorem');
        $I->click('//ul[@class="horizontal-tabs-list"]//strong[contains(text(), "Call to action")]');
        $I->fillLinkField(FormField::field_d_cta_link($page_elements), 'http://example.com', 'Example');
        $I->click(Locator::contains('strong', 'Items'));
        $page_item = ParagraphFormField::field_d_p_tb_block_reference($page_elements);
        $I->seeVar($page_item);
        $I->click('.dropbutton-toggle button');
        $I->addParagraph('d_p_single_text_block', $page_item);
        $I->fillTextField(FormField::field_d_main_title($page_item), 'title');
        $I->click(MTOFormField::field_d_media_background($page_item)->__get('open-button'));
        $I->attachImage($I, 'test.jpeg');
        $I->click(MTOFormField::field_d_media_icon($page_item)->__get('open-button'));
        $I->attachImage($I, 'mask.png');
        $I->fillCk5WysiwygEditor(FormField::field_d_long_text($page_item), 'LoremLorem');
        $I->fillLinkField(FormField::field_d_cta_link($page_item), 'http://example.com', 'Example');
        $I->clickOn(FormField::submit());
        $I->waitPageLoad(30);
        $url = $I->grabFromCurrentUrl();
        Fixtures::add('group_text_blocks_url', $url);
    }

    /**
     * Test if I can see the added Group of text blocks
     *
     * @param JSCapableTester $I
     *
     */
    public function seeCreatedGroupTextBlocksAsRandomUser(JSCapableTester $I)
    {
        $I->wantTo('see if the group text blocks is created');
        $I->amOnPage(Fixtures::get('group_text_blocks_url'));
        $I->see('title');
        $src_background = $I->grabAttributeFrom('.d-p-single-text-block__background img', 'src');
        $I->seeVar($src_background);
        $I->assertStringContainsString('test', $src_background);
        $src_icon = $I->grabAttributeFrom('.d-p-single-text-block__content .media-icon img', 'src');
        $I->seeVar($src_icon);
        $I->assertStringContainsString('mask', $src_icon);
        $I->see('Example');
        $I->click('Example');
        $I->see('Example Domain');
        $I->moveBack();
        $I->see('title');
    }

    /**
     * Removing added Group of text blocks and checking if it's deleted
     *
     * @param JSCapableTester $I
     * @before login
     */
    public function removeGroupTextBlocks(JSCapableTester $I)
    {
        $I->wantTo('clear up after the test');
        $I->amOnPage(Fixtures::get('group_text_blocks_url'));
        $node_nr = $I->grabNodeOfTypeAndTitleNoUser('content_page', 'Mans nosukums');
        $str = "/node/{$node_nr}/delete";
        $I->seeVar($str);
        $I->click("//a[contains(@href,'$str')]");
        $I->click('#edit-submit');
        $I->seeElement("//*[contains(@class, 'alert-success')]");
    }
}
