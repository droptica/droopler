<?php

/**
 * @file
 * Sidebar Embed Paragraph tests.
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
 * Class SidebarEmbedParagraphCest
 *
 * @package Tests\Js_capable
 */
class SidebarEmbedParagraphCest
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
     * Test if I can add sidebar embed paragraph to Content Page.
     *
     * @param JSCapableTester $I
     * @throws Exception
     * @before login
     */
    public function addSidebarEmbedToContentPage(JSCapableTester $I)
    {
        $I->wantTo('Add sidebar embed  paragraph to a new content page.');
        $I->amOnPage('/node/add/content_page/');
        $I->seeVar(MTOFormField::title());
        $I->fillTextField(FormField::title(), 'Mans nosukums');
        $I->click(Locator::contains('strong', 'Page Sections'));
        $page_elements = ParagraphFormField::field_page_section();
        $I->seeVar($page_elements);
        $I->click('.dropbutton-toggle button');
        $I->addNewParagraph('d_p_side_embed', $page_elements);
        $I->fillTextField(FormField::field_d_main_title($page_elements), 'Tytuł');
        $I->click(MTOFormField::field_d_media_icon($page_elements)->__get('open-button'));
        $I->attachImage($I, 'mask.png');
        $I->fillCk5WysiwygEditor(FormField::field_d_long_text($page_elements), 'Lorem');
        $I->fillTextField(
            FormField::field_d_embed($page_elements),
            '<iframe allow="autoplay" width="560" height="315"
        src="https://www.youtube.com/embed/6yO16tVGC1w?showinfo=0&amp;rel=0&amp;modestbranding=1&amp;
        title=&amp;fs=0&amp;controls=0"
        frameborder="0" allowfullscreen=""></iframe>'
        );
        $I->fillLinkField(FormField::field_d_cta_link($page_elements), 'http://example.com', 'Example');
        $I->clickOn(FormField::submit());
        $I->waitPageLoad(30);
        $url = $I->grabFromCurrentUrl();
        Fixtures::add('sidebarembed_url', $url);
    }

    /**
     * Test if I can see the added sidebar embed paragraph
     *
     * @param JSCapableTester $I
     *
     */
    public function seeCreatedSidebarEmbedAsRandomUser(JSCapableTester $I)
    {
        $I->wantTo('see if the sidebar embed paragraph is created');
        $I->amOnPage(Fixtures::get('sidebarembed_url'));
        $I->see('Tytuł');
        $src_icon = $I->grabAttributeFrom('.d-p-side-embed__content-column .media-icon img', 'src');
        $I->seeVar($src_icon);
        $I->assertStringContainsString('mask', $src_icon);
        $src_video = $I->grabAttributeFrom('.d-p-side-embed__embed iframe', 'src');
        $I->seeVar($src_video);
        $I->assertStringContainsString('https://www.youtube.com/embed/6yO16tVGC1w', $src_video);
        $I->see('Example');
        $I->click('Example');
        $I->see('Example Domain');
    }

    /**
     * Removing added sidebar embed paragraph and checking if it's deleted
     *
     * @param JSCapableTester $I
     * @before login
     */
    public function removeSidebarEmbed(JSCapableTester $I)
    {
        $I->wantTo('clear up after the test');
        $I->amOnPage(Fixtures::get('sidebarembed_url'));
        $node_nr = $I->grabNodeOfTypeAndTitleNoUser('content_page', 'Mans nosukums');
        $str = "/node/{$node_nr}/delete";
        $I->seeVar($str);
        $I->click("//a[contains(@href,'$str')]");
        $I->click('#edit-submit');
        $I->seeElement("//*[contains(@class, 'alert-success')]");
    }
}
