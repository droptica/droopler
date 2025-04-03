<?php

/**
 * @file
 * Block Paragraph tests.
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
 * Class BlockParagraphCest
 *
 * @package Tests\Js_capable
 */
class BlockParagraphCest
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
     * Test if I can add block paragraphs to Content Page.
     *
     * @param JSCapableTester $I
     * @throws Exception
     * @before login
     */
    public function addBlocksToContentPage(JSCapableTester $I)
    {
        $I->wantTo('Add block paragraphs to a new content page.');
        $I->amOnPage('/node/add/content_page/');
        $I->seeVar(MTOFormField::title());
        $I->fillTextField(FormField::title(), 'Mans nosukums');
        $I->click(Locator::contains('strong', 'Page Sections'));
        $page_elements = ParagraphFormField::field_page_section();
        $I->seeVar($page_elements);
        $I->click('.dropbutton-toggle button');
        $I->addNewParagraph('d_p_block', $page_elements);
        $I->wait(5);
        $I->selectOption(FormField::field_block($page_elements)->__get('plugin-id'), 'Content block');
        $I->wait(5);
        $I->fillField(
            ['name' => 'field_page_section[0][subform][field_block][0][settings][label]'],
            'Content block test'
        );
        $I->click(Locator::lastElement('.dropbutton-toggle button'));
        $I->click('#gin-sticky-edit-submit');
        $I->waitPageLoad(30);
        $url = $I->grabFromCurrentUrl();
        Fixtures::add('block_url', $url);
    }

    /**
     * Test if I can see the added block paragraphs
     *
     * @param JSCapableTester $I
     *
     */
    public function seeCreatedBlocksAsRandomUser(JSCapableTester $I)
    {
        $I->wantTo('see if the block paragraphs are created');
        $I->amOnPage(Fixtures::get('block_url'));
        $I->seeElement('//div[@class="d-p-block__content container"]
        /div[@class="block"]/h2[@class="heading" and contains(text(), "Social Media Block test")]');
        $I->seeElement('//div[@class="d-p-block__content container"]
        /div[@class="search-page-link-block"]/a[@class="search-page-link" and contains(text(), "Search")]');
    }

    /**
     * Removing added blocks and checking if they are deleted
     *
     * @param JSCapableTester $I
     * @before login
     */
    public function removeBlocks(JSCapableTester $I)
    {
        $I->wantTo('clear up after the test');
        $I->amOnPage(Fixtures::get('block_url'));
        $node_nr = $I->grabNodeOfTypeAndTitleNoUser('content_page', 'Mans nosukums');
        $str = "/node/{$node_nr}/delete";
        $I->seeVar($str);
        $I->click("//a[contains(@href,'$str')]");
        $I->click('#edit-submit');
        $I->seeElement("//*[contains(@class, 'alert-success')]");
    }
}
