<?php

$I = new CITester($scenario);
$I->wantTo('Verify that the site is accessible');
$I->amOnPage('/');
$I->see('Droopler', 'title');
