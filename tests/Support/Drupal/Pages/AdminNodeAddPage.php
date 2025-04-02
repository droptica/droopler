<?php

/**
 * @file
 * Administration page for adding nodes.
 */

namespace Tests\Support\Drupal\Pages;

/**
 * Class AdminNodeAddPage
 * @package Tests\Support\Drupal\Pages
 */
class AdminNodeAddPage extends Page
{
    /**
     * @var string
     *   URL/path to this page.
     */
    protected static $URL = '/node/add';

    /**
     * @param string $type
     * @return string
     */
    public static function route($type = '')
    {
        if ($type == '') {
            // No type provided, so assume we are on the main "add content"
            // page.
            return static::$URL;
        } else {
            $type = str_replace('_', '-', $type);
            return static::$URL . '/' . $type;
        }
    }
}
