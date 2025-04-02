<?php

namespace Tests\Js_capable;

use Codeception\Util\Drupal\FormField;
use Codeception\Util\Drupal\ParagraphFormField;
use Codeception\Util\Drupal\MTOFormField;
use Codeception\Util\Fixtures;
use Codeception\Util\Locator;
use Exception;
use Tests\Support\JSCapableTester;

/**
 * Class ReferenceContentCest
 *
 * @package Tests\Js_capable
 */
class ReferenceContentCest
{
    protected const TITLE_CONTENT_PAGE = 'Reference content test page';
    protected const TITLE_REFERENCE_CONTENT_PAGE = 'Page to be referenced';
    protected const TITLE_REFERENCE_CONTENT_COMPONENT = 'Reference content component';

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
     * Create content.
     *
     * @param JSCapableTester $I
     * @throws Exception
     * @before login
     */
    public function addTestContent(JSCapableTester $I)
    {
        $I->wantTo('Create content.');
        $I->amOnPage('/node/add/content_page/');
        $I->seeVar(MTOFormField::title());
        $I->fillTextField(FormField::title(), self::TITLE_CONTENT_PAGE);
        $I->clickOn(FormField::submit());
        $I->waitPageLoad(30);
        $url = $I->grabFromCurrentUrl();
        Fixtures::add('test_content_url', $url);
    }

  /**
   * Test if I can add reference content to Content Page.
   *
   * @param JSCapableTester $I
   * @throws Exception
   * @before login
   */
    public function addReferenceContentToContentPage(JSCapableTester $I)
    {
        $I->wantTo('Add reference content to new content page.');
        $I->amOnPage('/node/add/content_page/');
        $I->seeVar(MTOFormField::title());
        $I->fillTextField(FormField::title(), self::TITLE_REFERENCE_CONTENT_PAGE);
        $I->click(Locator::contains('strong', 'Page Sections'));
        $page_elements = ParagraphFormField::field_page_section();
        $I->seeVar($page_elements);
        $I->click('.dropbutton-toggle button');
        $I->addNewParagraph('d_p_reference_content', $page_elements);
        $I->fillTextField(FormField::field_d_main_title($page_elements), self::TITLE_REFERENCE_CONTENT_COMPONENT);
        $I->click(MTOFormField::field_d_media_icon($page_elements)->__get('open-button'));
        $I->attachImage($I, 'mask.png');
        $I->click(
            "//*[contains(@data-drupal-selector, 'edit-field-page-section-0')]
             //*[contains(@class, 'horizontal-tab-button-1')] //a[contains(@href, '#edit-group-items')]"
        );
        $page_item = ParagraphFormField::field_d_p_reference_content($page_elements);
        $I->seeVar($page_item);
        $selector = "//*[contains(@data-drupal-selector, " .
                    "'edit-field-page-section-0-subform-field-d-p-reference-content-0')]";
        $I->fillField($selector, self::TITLE_CONTENT_PAGE);
        $I->clickOn(FormField::submit());
        $I->waitPageLoad(30);
        $url = $I->grabFromCurrentUrl();
        Fixtures::add('reference_content_url', $url);
    }

  /**
   * Test if I can see the added reference content.
   *
   * @param JSCapableTester $I
   *
   */
    public function seeCreatedReferenceContentAsRandomUser(JSCapableTester $I)
    {
        $I->wantTo('see if the reference content is created');
        $I->amOnPage(Fixtures::get('reference_content_url'));
        $I->see(self::TITLE_REFERENCE_CONTENT_COMPONENT);
        $src_icon = $I->grabAttributeFrom('.d-p-reference-content__content .media-icon img', 'src');
        $I->seeVar($src_icon);
        $I->assertStringContainsString('mask', $src_icon);
        $I->see(self::TITLE_CONTENT_PAGE);
        $I->see('LEARN MORE');
        $I->click('Learn more');
        $I->moveBack();
    }

    /**
     * Removing added reference content and checking if it's deleted.
     *
     * @param JSCapableTester $I
     * @before login
     */
    public function removeReferenceContent(JSCapableTester $I)
    {
        $I->wantTo('clear up after the test');
        $I->amOnPage(Fixtures::get('reference_content_url'));
        $node_nr = $I->grabNodeOfTypeAndTitleNoUser('content_page', self::TITLE_REFERENCE_CONTENT_PAGE);
        $str = "/node/{$node_nr}/delete";
        $I->seeVar($str);
        $I->click("//a[contains(@href,'$str')]");
        $I->click('#edit-submit');
        $I->seeElement("//*[contains(@class, 'alert-success')]");

        $I->amOnPage(Fixtures::get('test_content_url'));
        $node_nr = $I->grabNodeOfTypeAndTitleNoUser('content_page', self::TITLE_CONTENT_PAGE);
        $str = "/node/{$node_nr}/delete";
        $I->seeVar($str);
        $I->click("//a[contains(@href,'$str')]");
        $I->click('#edit-submit');
        $I->seeElement("//*[contains(@class, 'alert-success')]");
    }
}
