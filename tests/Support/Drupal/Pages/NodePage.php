<?php

/**
 * @file
 * Routing and structural elements of node/* pages.
 */

namespace Tests\Support\Drupal\Pages;

/**
 * Class NodePage
 * @package Tests\Support\Drupal\Pages
 */
class NodePage extends Page
{
    /**
     * @var string
     *   URL/path to this page.
     */
    protected static $URL = '/node';

    /**
     * @param int $nid
     * @param null $type
     * @return string
     */
    public static function route($nid = 0, $type = null)
    {
        // Force conversion to string because Codeception uses the Maybe class
        // to represent proper parameters.
        $nid = (string) $nid;
        $pathSuffix = '';

        if (is_numeric($nid) && $nid > 0) {
            $pathSuffix = '/' . $nid;

            switch ($type) {
                case 'edit':
                    $pathSuffix .= '/edit';
                    break;

                case 'delete':
                    $pathSuffix .= '/delete';
                    break;
            }
        }

        return static::$URL . $pathSuffix;
    }
}
