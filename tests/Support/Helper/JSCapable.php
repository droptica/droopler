<?php

namespace Tests\Support\Helper;

use Tests\Support\JSCapableTester;

/**
 * here you can define custom actions
 * all public methods declared in helper class will be available in $I
 */
class JSCapable extends \Codeception\Module
{
    /**
     * Attach Image.
     *
     * @param JSCapableTester $I
     * @param string $image
     *   Image name.
     * @throws \Exception
     */
    public function attachImage(JSCapableTester $I, string $image)
    {
        $I->waitAjaxLoad(30);
        $I->attachFile('[id^=edit-upload-upload--]', $image);
        $I->waitAjaxLoad(30);
        $I->fillField('[id^=edit-media-0-fields-field-media-image-0-alt--]', 'image alt');
        $I->click('.ui-dialog-buttonset.form-actions button');
        $I->waitAjaxLoad(30);
        $I->click('.ui-dialog-buttonset button');
        $I->waitAjaxLoad(30);
    }

   /**
     * Grab node of type and title for user.
     *
     * @param $type
     * @param $title
     * @return null
     */
    public function grabNodeOfTypeAndTitleNoUser($type, $title)
    {
        $query = \Drupal::database()->select('node_field_data', 'n');
        $query->fields('n', array(
            'nid'
        ));
        $query->condition('n.type', $type, '=');
        $query->condition('n.title', $title, '=');
        $query->orderBy('n.nid', 'desc');
        $query->range(null, 1);
        $result = $query->execute()->fetchCol();
        return (count($result) > 0 ? $result[0] : null);
    }
}
