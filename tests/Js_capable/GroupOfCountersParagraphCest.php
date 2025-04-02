<?php

/**
 * @file
 * Group of Counters Paragraph tests.
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
 * Class Group of Counters ParagraphCest
 *
 * @package Tests\Js_capable
 */
class GroupOfCountersParagraphCest
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
     * Test if I can add group of counters paragraph to Content Page.
     *
     * @param JSCapableTester $I
     * @throws Exception
     * @before login
     */
    public function addGroupOfCountersParagraphToContentPage(JSCapableTester $I)
    {
        $I->wantTo('Add counters paragraph to new content page.');
        $I->amOnPage('/node/add/content_page/');
        $I->seeVar(MTOFormField::title());
        $I->fillTextField(FormField::title(), 'Mans nosukums');
        $I->click(Locator::contains('strong', 'Page Sections'));
        $page_elements = ParagraphFormField::field_page_section();
        $I->seeVar($page_elements);
        $I->click('.dropbutton-toggle button');
        $I->addNewParagraph('d_p_group_of_counters', $page_elements);
        $I->fillTextField(FormField::field_d_main_title($page_elements), 'Tytul');
        $I->click(MTOFormField::field_d_media_background($page_elements)->__get('open-button'));
        $I->attachImage($I, 'test.png');
        $I->click(MTOFormField::field_d_media_icon($page_elements)->__get('open-button'));
        $I->attachImage($I, 'mask.png');
        $I->fillCk5WysiwygEditor(FormField::field_d_long_text($page_elements), 'Lorem ipsum');
        $I->fillLinkField(FormField::field_d_cta_link($page_elements), 'http://example.com', 'Example');
        $I->click(Locator::contains('strong', 'Items'));
        $page_item = ParagraphFormField::field_d_counter_reference($page_elements);
        $I->seeVar($page_item);
        $I->fillTextField(FormField::field_d_number($page_item), '70');
        $I->fillTextField(FormField::field_d_main_title($page_item), 'Clients this year');
        $I->click(MTOFormField::field_d_media_icon($page_item)->__get('open-button'));
        $I->attachImage($I, 'mask.png');
        $page_item = ParagraphFormField::field_d_counter_reference($page_elements)->next();
        $I->seeVar($page_item);
        $I->addParagraph('d_p_single_counter', $page_item);
        $I->fillTextField(FormField::field_d_number($page_item), '23');
        $I->fillTextField(FormField::field_d_main_title($page_item), 'New job opportunities');
        $I->click(MTOFormField::field_d_media_icon($page_item)->__get('open-button'));
        $I->attachImage($I, 'test.png');
        $I->clickOn(FormField::submit());
        $I->waitPageLoad(30);
        $url = $I->grabFromCurrentUrl();
        Fixtures::add('group_counters_url', $url);
    }

    /**
     * Test if I can see the added group of counters paragraph
     *
     * @param JSCapableTester $I
     *
     */
    public function seeCreatedCountersAsRandomUser(JSCapableTester $I)
    {
        $I->wantTo('see if the group of counters paragraph is created');
        $I->amOnPage(Fixtures::get('group_counters_url'));
        $I->see('Tytul');
        $src_icon = $I->grabAttributeFrom('.d-p-group-of-counters__header-column .media-icon img', 'src');
        $I->seeVar($src_icon);
        $I->assertStringContainsString('mask', $src_icon);
        $src_background = $I->grabAttributeFrom('.d-p-group-of-counters__background img', 'src');
        $I->seeVar($src_background);
        $I->assertStringContainsString('test', $src_background);
        $I->wait(15);
        $I->see('70');
        $I->see('Clients this year');
        $src_icon = $I->grabAttributeFrom('.d-p-counter__content .media-icon img', 'src');
        $I->seeVar($src_icon);
        $I->assertStringContainsString('mask', $src_icon);
        $I->see('23');
        $I->see('New job opportunities');
        $src_icon = $I->grabAttributeFrom(Locator::elementAt('.d-p-counter__content .media-icon img', 2), 'src');
        $I->seeVar($src_icon);
        $I->assertStringContainsString('test', $src_icon);
        $I->see('Example');
        $I->click('Example');
        $I->see('Example Domain');
        $I->moveBack();
        $I->see('Tytul');
    }

    /**
     * Removing added group of counters paragraph and checking if it's deleted
     *
     * @param JSCapableTester $I
     * @before login
     */
    public function removeGroupOfCountersParagraph(JSCapableTester $I)
    {
        $I->wantTo('clear up after the test');
        $I->amOnPage(Fixtures::get('group_counters_url'));
        $node_nr = $I->grabNodeOfTypeAndTitleNoUser('content_page', 'Mans nosukums');
        $str = "/node/{$node_nr}/delete";
        $I->seeVar($str);
        $I->click("//a[contains(@href,'$str')]");
        $I->click('#edit-submit');
        $I->seeElement("//*[contains(@class, 'alert-success')]");
    }
}
