<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;

/**
 * @file
 * CSS test cest.
 */

/**
 * Class CSSTestCest
 */
class CSSTestCest
{
    /**
     * CSS themes test.
     *
     * @param AcceptanceTester $I
     */
    public function cssThemes(AcceptanceTester $I)
    {
        $I->wantTo('See css themes files');
        $I->amOnPage('/');
        $I->seeResponseCodeIs(200);
    }

    /**
     * CSS subtheme test.
     *
     * @param AcceptanceTester $I
     */
    public function cssSubtheme(AcceptanceTester $I)
    {
        $I->wantTo('See css subtheme files');
        $I->amOnPage('/');
        $I->seeResponseCodeIs(200);
    }
}
