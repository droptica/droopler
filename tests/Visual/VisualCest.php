<?php

/**
 * @file
 * Visualception tests to compare dev pages version to version.
 */

namespace Tests\Visual;

use Codeception\Util\Shared\Asserts;
use Tests\Support\VisualTester;

/**
 * Class VisualCest
 *
 * @package visual
 */
class VisualCest
{
    use Asserts;

    /**
     * @var string list of pages
     */
    private $list_pages;

    /**
     * @var string my URL
     */
    private $my_url;

    /**
     * @var array no compare elements
     */
    private $no_compare;

    /**
     * @var array no compare elements on production site
     */
    private $no_compare_prod;

    /**
     * @var string production URL
     */
    private $prod_url;

    /**
     * VisualCest constructor.
     */
    public function __construct()
    {
        $this->my_url = getenv('DEV_URI');
        $this->list_pages = getenv('LIST_PAGES');
        $this->no_compare = getenv('DEV_NO_COMPARE');
    }

    /**
     * Provide pages list to compare with production.
     *
     * @return array
     */
    protected function pageProvider()
    {
        return [
            [
                'url' => "/documentation/banner-paragraph",
                'class' => '.wrapper-d_p_banner.full-example',
                'title' => "Banner full example"
            ],
            [
                'url' => "/documentation/banner-paragraph",
                'class' => '.wrapper-d_p_banner.basic-example',
                'title' => "Banner basic example"
            ],
            [
                'url' => "/documentation/banner-paragraph",
                'class' => '.wrapper-d_p_banner.half-transparent',
                'title' => "Banner half transparent"
            ],
            [
                'url' => "/documentation/carousel-paragraph",
                'class' => '.wrapper-d_p_carousel.full-example',
                'title' => "Carousel full example"
            ],
            [
                'url' => "/documentation/carousel-paragraph",
                'class' => '.wrapper-d_p_carousel.logos-example',
                'title' => "Carousel logos example"
            ],
            [
                'url' => "/documentation/carousel-paragraph",
                'class' => '.wrapper-d_p_carousel.opinions-example',
                'title' => "Carousel opinions example"
            ],
            [
                'url' => "/documentation/carousel-paragraph",
                'class' => '.wrapper-d_p_carousel.columns-example',
                'title' => "Carousel columns example"
            ],
            [
                'url' => "/documentation/carousel-paragraph",
                'class' => '.wrapper-d_p_carousel.full-width-example',
                'title' => "Carousel full width example"
            ],
            [
                'url' => "/documentation/sidebar-embed-paragraph",
                'class' => '.wrapper-d_p_side_embed.full-example',
                'title' => "Sidebar embed full example"
            ],
            [
                'url' => "/documentation/sidebar-embed-paragraph",
                'class' => '.wrapper-d_p_side_embed.full-width-example',
                'title' => "Sidebar embed full width example"
            ],
            [
                'url' => "/documentation/sidebar-embed-paragraph",
                'class' => '.wrapper-d_p_side_embed.youtube-example',
                'title' => "Sidebar embed youtube example"
            ],
            [
                'url' => "/documentation/form-paragraph",
                'class' => '.wrapper-d_p_form.full-example',
                'title' => "Form paragraph full example"
            ],
            [
                'url' => "/documentation/form-paragraph",
                'class' => '.wrapper-d_p_form.alternative-layout-example',
                'title' => "Form paragraph alternative layout example"
            ],
            [
                'url' => "/documentation/gallery-paragraph",
                'class' => '.wrapper-d_p_gallery.full-example',
                'title' => "Gallery full example"
            ],
            [
                'url' => "/documentation/gallery-paragraph",
                'class' => '.wrapper-d_p_gallery.basic-example',
                'title' => "Gallery basic example"
            ],
            [
                'url' => "/documentation/reference-content-paragraph",
                'class' => '.wrapper-d_p_reference_content.basic-example',
                'title' => "Reference content basic example"
            ],
            [
                'url' => "/documentation/sidebar-image-paragraph",
                'class' => '.wrapper-d_p_side_image.full-example',
                'title' => "Sidebar image full example"
            ],
            [
                'url' => "/documentation/sidebar-image-paragraph",
                'class' => '.wrapper-d_p_side_image.basic-example',
                'title' => "Sidebar image basic example"
            ],
            [
                'url' => "/documentation/sidebar-image-paragraph",
                'class' => '.wrapper-d_p_side_image.all-example',
                'title' => "Sidebar image all example"
            ],
            [
                'url' => "/documentation/sidebar-image-paragraph",
                'class' => '.wrapper-d_p_side_image.wide-example',
                'title' => "Sidebar image wide example"
            ],
            [
                'url' => "/documentation/side-by-side-paragraph",
                'class' => '.wrapper-d_p_side_by_side.full-example',
                'title' => "Side by side full example"
            ],
            [
                'url' => "/documentation/side-by-side-paragraph",
                'class' => '.wrapper-d_p_side_by_side.with-grid',
                'title' => "Side by side with grid"
            ],
            [
                'url' => "/documentation/subscribe-file-paragraph",
                'class' => '.wrapper-d_p_subscribe_file.full-example',
                'title' => "Subscribe file full example"
            ],
            [
                'url' => "/documentation/subscribe-file-paragraph",
                'class' => '.wrapper-d_p_subscribe_file.basic-example',
                'title' => "Subscribe file basic example"
            ],
            [
                'url' => "/documentation/text-paragraph",
                'class' => '.wrapper-d_p_text_paged.full-example',
                'title' => "Text full example"
            ],
            [
                'url' => "/documentation/text-paragraph",
                'class' => '.wrapper-d_p_text_paged.basic-example',
                'title' => "Text basic example"
            ],
            [
                'url' => "/documentation/text-paragraph",
                'class' => '.wrapper-d_p_text_paged.theme-invert-example',
                'title' => "Text theme invert example"
            ],
            [
                'url' => "/documentation/text-bg-paragraph",
                'class' => '.wrapper-d_p_text_with_bckg.full-example',
                'title' => "Text With Background full example"
            ],
            [
                'url' => "/documentation/text-bg-paragraph",
                'class' => '.wrapper-d_p_text_with_bckg.basic-example',
                'title' => "Text With Background basic example"
            ],
            [
                'url' => "/documentation/text-blocks-paragraph",
                'class' => '.wrapper-d_p_group_of_text_blocks.full-example',
                'title' => "Text Blocks full example"
            ],
            [
                'url' => "/documentation/text-blocks-paragraph",
                'class' => '.wrapper-d_p_group_of_text_blocks.basic-example',
                'title' => "Text Blocks basic example"
            ],
            [
                'url' => "/documentation/text-blocks-paragraph",
                'class' => '.wrapper-d_p_group_of_text_blocks.theme-invert-example',
                'title' => "Text Blocks theme invert example"
            ],
            [
                'url' => "/documentation/text-blocks-paragraph",
                'class' => '.wrapper-d_p_group_of_text_blocks.nodes-grid-example',
                'title' => "Text Blocks nodes grid example"
            ],
            [
                'url' => "/documentation/text-blocks-paragraph",
                'class' => '.wrapper-d_p_group_of_text_blocks.header-into-columns',
                'title' => "Text Blocks header into columns"
            ],
            [
                'url' => "/documentation/tiles-paragraphs",
                'class' => '.wrapper-d_p_tiles.full-example',
                'title' => "Titles full example"
            ],
            [
                'url' => "/documentation/tiles-paragraphs",
                'class' => '.wrapper-d_p_side_tiles.second-example',
                'title' => "Titles second example"
            ],
            [
                'url' => "/documentation/tiles-paragraphs",
                'class' => '.wrapper-d_p_side_tiles.third-example',
                'title' => "Titles third example"
            ],
            [
                'url' => "/documentation/tiles-paragraphs",
                'class' => '.wrapper-d_p_tiles.paragraph-top-dark.paragraph-bottom-dark',
                'title' => "Titles dark example"
            ],
            [
                'url' => "/documentation/tiles-paragraphs",
                'class' => '.wrapper-d_p_side_tiles.invert-example',
                'title' => "Titles invert example"
            ]
        ];
    }

    /**
     * Compares the screenshots Production Desktop.
     * @dataProvider pageProvider
     * @env desktop
     * @param VisualTester $I
     * @param $page - page from provider
     */
    public function screenshotProductionDesktop(VisualTester $I, \Codeception\Example $page)
    {
        $I->wantTo('Compare screenshots on ' . $page['title']);
        $I->amOnPage($page['url']);
        $I->waitPageLoad(30);
        if (strpos($page['class'], '.wrapper-d_p_side_embed') !== false) {
            $I->wait(10);
        }
        if (strpos($page['class'], '.wrapper-d_p_carousel') !== false) {
            $I->executeJS("jQuery('.slick-slider').slick('pause');");
            $I->executeJS("jQuery('.slick-slider').slick('slickGoTo', 0);");
            $I->wait(2);
        }
        if ($this->no_compare == "") {
            $I->dontSeeVisualChanges($page['title'], $page['class']);
        } elseif (strpos($this->no_compare, ",") !== false) {
            $I->dontSeeVisualChanges($page['title'], $page['class'], explode(",", $this->no_compare));
        } else {
            $I->dontSeeVisualChanges($page['title'], $page['class'], $this->no_compare);
        }
    }

    /**
     * Compares the screenshots Production Tablet.
     * @dataProvider pageProvider
     * @env tablet
     * @param VisualTester $I
     * @param $page - page from provider
     */
    public function screenshotProductionTablet(VisualTester $I, \Codeception\Example $page)
    {
        $I->wantTo('Compare screenshots on tablet ' . $page['title']);
        $I->amOnPage($page['url']);
        $I->waitPageLoad(30);
        if (strpos($page['class'], '.wrapper-d_p_side_embed') !== false) {
            $I->wait(10);
        }
        if (strpos($page['class'], '.wrapper-d_p_carousel') !== false) {
            $I->executeJS("jQuery('.slick-slider').slick('pause');");
            $I->executeJS("jQuery('.slick-slider').slick('slickGoTo', 0);");
            $I->wait(2);
        }
        if ($this->no_compare == "") {
            $I->dontSeeVisualChanges($page['title'] . " tablet", $page['class']);
        } elseif (strpos($this->no_compare, ",") !== false) {
            $I->dontSeeVisualChanges($page['title'] . " tablet", $page['class'], explode(",", $this->no_compare));
        } else {
            $I->dontSeeVisualChanges($page['title'] . " tablet", $page['class'], $this->no_compare);
        }
    }

    /**
     * Compares the screenshots Production mobile.
     * @dataProvider pageProvider
     * @env mobile
     * @param VisualTester $I
     * @param $page - page from provider
     */
    public function screenshotProductionMobile(VisualTester $I, \Codeception\Example $page)
    {
        $I->wantTo('Compare screenshots on mobile ' . $page['title']);
        $I->amOnPage($page['url']);
        $I->waitPageLoad(30);
        if (strpos($page['class'], '.wrapper-d_p_side_embed') !== false) {
            $I->wait(10);
        }
        if (strpos($page['class'], '.wrapper-d_p_carousel') !== false) {
            $I->executeJS("jQuery('.slick-slider').slick('pause');");
            $I->executeJS("jQuery('.slick-slider').slick('slickGoTo', 0);");
            $I->wait(2);
        }
        if ($this->no_compare == "") {
            $I->dontSeeVisualChanges($page['title'] . " mobile", $page['class']);
        } elseif (strpos($this->no_compare, ",") !== false) {
            $I->dontSeeVisualChanges($page['title'] . " mobile", $page['class'], explode(",", $this->no_compare));
        } else {
            $I->dontSeeVisualChanges($page['title'] . " mobile", $page['class'], $this->no_compare);
        }
    }
}
