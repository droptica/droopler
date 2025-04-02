<?php

namespace Tests\Support\Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class DrupalHelper extends \Codeception\Module
{
    /**
     * @param $var
     * this will only run if you run codeception with -d
     * Otherwise this is silent
     */
    public function seeVar($var)
    {
        $this->debug($var);
    }
}
