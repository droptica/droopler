<?php

/**
 * @file
 * CSS test cest.
 */

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;

/**
 * Class JSTestCest
 */
class JSTestCest
{
    /**
     * JS themes test.
     *
     * @param AcceptanceTester $I
     */
    public function jsThemes(AcceptanceTester $I)
    {
        $I->wantTo('See js themes files');
        $I->amOnPage('/');
        $I->seeResponseCodeIs(200);
        $I->amOnPage('/');
        $I->seeResponseCodeIs(200);
    }

    /**
     * JS subtheme test.
     *
     * @param AcceptanceTester $I
     */
    public function jsSubtheme(AcceptanceTester $I)
    {
        $I->wantTo('See js subtheme files');
        $I->amOnPage('/');
        $I->seeResponseCodeIs(200);
    }
}
