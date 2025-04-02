<?php

/**
 * @file
 * Subscribe For File Paragraph tests.
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
 * Class SubscribeForFileParagraphCest
 *
 * @package Tests\Js_capable
 */
class SubscribeForFileParagraphCest
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
     * Test if I can add subscribe for file paragraph to Content Page.
     *
     * @param JSCapableTester $I
     * @throws Exception
     * @before login
     */
    public function addSubscribeForFileParagraphToContentPage(JSCapableTester $I)
    {
        $I->wantTo('Add subscribe for file paragraph to a new content page.');
        $I->amOnPage('/node/add/content_page/');
        $I->seeVar(MTOFormField::title());
        $I->fillTextField(FormField::title(), 'Mans nosukums');
        $I->click(Locator::contains('strong', 'Page Sections'));
        $page_elements = ParagraphFormField::field_page_section();
        $I->seeVar($page_elements);
        $I->click('.dropbutton-toggle button');
        $I->addNewParagraph('d_p_subscribe_file', $page_elements);
        $I->fillTextField(FormField::field_d_main_title($page_elements), 'title');
        $I->click(MTOFormField::field_d_media_background($page_elements)->__get('open-button'));
        $I->attachImage($I, 'test.jpeg');
        $I->click(MTOFormField::field_d_media_icon($page_elements)->__get('open-button'));
        $I->attachImage($I, 'mask.png');
        try {
            $I->fillTextField(FormField::field_d_long_text($page_elements), 'Lorem');
        } catch (Exception $e) {
            $I->fillCk5WysiwygEditor(FormField::field_d_long_text($page_elements), 'Lorem');
        }
        try {
            $I->fillTextField(FormField::field_d_p_sf_additional_info($page_elements), 'Additional info');
        } catch (Exception $e) {
            $I->fillCk5WysiwygEditor(FormField::field_d_p_sf_additional_info($page_elements), 'Additional info');
        }
        $I->click(Locator::contains('strong', 'Form & Messages'));
        $I->wait(5);
        $I->attachFile(
            '[id^=edit-field-page-section-0-subform-field-file-download-0-upload--]',
            'sample.pdf'
        );
        $I->fillTextField(
            FormField::field_d_p_sf_consent($page_elements),
            'Sample consent checkbox with <a href="#">link</a>.'
        );
        $I->fillTextField(FormField::field_d_p_sf_download_button($page_elements), 'Get download link!');
        $I->fillCk5WysiwygEditor(
            FormField::field_d_p_sf_download_page($page_elements),
            'Text on the download page like: Lorem ipsum dolor sit amet,'
            . 'consectetur adipiscing elit. [download-button]'
        );
        $I->fillCk5WysiwygEditor(
            FormField::field_d_p_sf_mail_body($page_elements),
            'Mail body like: Lorem ipsum dolor sit amet. [download-button]'
        );
        $I->clickOn(FormField::submit());
        $I->waitPageLoad(30);
        $url = $I->grabFromCurrentUrl();
        Fixtures::add('subscribe_url', $url);
    }

    /**
     * Test if I can see the added subscribe for file paragraph
     *
     * @param JSCapableTester $I
     *
     */
    public function seeCreatedSubscribeForFileAsRandomUser(JSCapableTester $I)
    {
        $I->wantTo('see if the subscribe for file paragraph is created');
        $I->amOnPage(Fixtures::get('subscribe_url'));
        $I->see('title');
        $I->see('Lorem');
        $src_icon = $I->grabAttributeFrom(
            '.d-p-subscribe-file__content-column .media-icon img',
            'src'
        );
        $I->seeVar($src_icon);
        $I->assertStringContainsString('mask', $src_icon);
        $src_background = $I->grabAttributeFrom('.d-p-subscribe-file__background img', 'src');
        $I->seeVar($src_background);
        $I->assertStringContainsString('test', $src_background);
        // Clear old emails from MailHog
        $I->deleteAllEmails();
        $I->see('Additional info');
        $I->fillField("//input[@id='edit-name']", "Name");
        $I->fillField("//input[@id='edit-mail']", "test@example.com");
        $I->checkOption('.d-p-subscribe-file__content-column #edit-consent-0');
        $I->click('.d-p-subscribe-file__content-column #edit-submit');
        $I->see('We send download link, check Your e-mail.');
        // Check if the email is sent
        $I->fetchEmails();
        $I->haveUnreadEmails();
        $I->openNextUnreadEmail();
        $I->dontHaveUnreadEmails();
        $I->seeInOpenedEmailRecipients('test@example.com');
        $I->seeInOpenedEmailBody('Get download link');
    }

    /**
     * Removing added subscribe for file and checking if it's deleted
     *
     * @param JSCapableTester $I
     * @before login
     */
    public function removeSubscribeForFile(JSCapableTester $I)
    {
        $I->wantTo('clear up after the test');
        $I->amOnPage(Fixtures::get('subscribe_url'));
        $node_nr = $I->grabNodeOfTypeAndTitleNoUser('content_page', 'Mans nosukums');
        $str = "/node/{$node_nr}/delete";
        $I->seeVar($str);
        $I->click("//a[contains(@href,'$str')]");
        $I->click('#edit-submit');
        $I->seeElement("//*[contains(@class, 'alert-success')]");
    }
}
