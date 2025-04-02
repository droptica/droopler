<?php

/**
 * @file
 * Form Paragraph tests.
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
 * Class FormParagraphCest
 *
 * @package Tests\Js_capable
 */
class FormParagraphCest
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
     * Test if I can add form to Content Page.
     *
     * @param JSCapableTester $I
     * @throws Exception
     * @before login
     */
    public function addFormToContentPage(JSCapableTester $I)
    {
        $I->wantTo('Add form to a new content page.');
        $I->amOnPage('/node/add/content_page/');
        $I->seeVar(MTOFormField::title());
        $I->fillTextField(FormField::title(), 'Mans nosukums');
        $I->click(Locator::contains('strong', 'Page Sections'));
        $page_elements = ParagraphFormField::field_page_section();
        $I->seeVar($page_elements);
        $I->click('.dropbutton-toggle button');
        $I->addNewParagraph('d_p_form', $page_elements);
        $I->fillTextField(FormField::field_d_main_title($page_elements), 'Tytul');
        $I->click(MTOFormField::field_d_media_background($page_elements)->__get('open-button'));
        $I->attachImage($I, 'test.jpeg');
        $I->click(MTOFormField::field_d_media_icon($page_elements)->__get('open-button'));
        $I->attachImage($I, 'mask.png');
        $I->fillCk5WysiwygEditor(FormField::field_d_long_text($page_elements), 'Lorem');
        $I->clickOn(FormField::field_d_forms($page_elements));
        $I->wait(5);
        $I->selectOption(
            "//select[contains(@data-drupal-selector, 
            'edit-field-page-section-0-subform-field-d-forms')]",
            "feedback"
        );
        $I->clickOn(FormField::submit());
        $I->waitPageLoad(30);
        $url = $I->grabFromCurrentUrl();
        Fixtures::add('form_url', $url);
    }

    /**
     * Test if I can see the added form
     *
     * @param JSCapableTester $I
     *
     */
    public function seeCreatedFormAsRandomUser(JSCapableTester $I)
    {
        $I->wantTo('see if the form is created');
        $I->amOnPage(Fixtures::get('form_url'));
        $I->see('Tytul');
        $src_icon = $I->grabAttributeFrom('.d-p-form__content-column .media-icon img', 'src');
        $I->seeVar($src_icon);
        $I->assertStringContainsString('mask', $src_icon);
        $I->seeElement('#edit-name');
        $I->seeElement('#edit-mail');
        $I->seeElement('#edit-subject-0-value');
        $I->seeElement('#edit-message-0-value');
        $I->seeElement('#edit-submit');
    }

    /**
     * Removing added form and checking if it's deleted
     *
     * @param JSCapableTester $I
     * @before login
     */
    public function removeForm(JSCapableTester $I)
    {
        $I->wantTo('clear up after the test');
        $I->amOnPage(Fixtures::get('form_url'));
        $node_nr = $I->grabNodeOfTypeAndTitleNoUser('content_page', 'Mans nosukums');
        $str = "/node/{$node_nr}/delete";
        $I->seeVar($str);
        $I->click("//a[contains(@href,'$str')]");
        $I->click('#edit-submit');
        $I->seeElement("//*[contains(@class, 'alert-success')]");
    }
}
