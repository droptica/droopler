<?php

namespace Tests\Support\Step;

use Tests\Support\AcceptanceTester;
use Tests\Support\Drupal\Pages\AccessDenied;
use Tests\Support\Drupal\Pages\NodePage;
use Tests\Support\Drupal\Pages\Page;
use Codeception\Module\DrupalTestUser;

trait NodeCommonSteps
{
    use UserCommonSteps;

  /**
   * @param $username
   * @param $content_type
   * @param $fields_config
   * @param bool $grab_nid
   * @return int|null
   */
    public function createNewNodeAsUser($username, $content_type, $fields_config)
    {
      /** @var AcceptanceTester $I */
        $I = $this;

      // The same value in different variable to see functions from trait UserCommonSteps.
      /** @var UserCommonSteps $U */
        $U = $this;

      // login as user
      /** @var DrupalTestUser $user */
        $user = $I->getTestUserByName($username);
        $U->login($user->name, $user->pass);

      // create node
        $I->createNode($I, $content_type, $fields_config, null, false);
        $nid = $this->grabNodeNid();
        $I->appendNodeToStorage($nid);
        $I->seeVar($nid);

      // logout
        $U->logout();

        return $nid;
    }

  /**
   * @return null
   */
    public function grabNodeNid()
    {
      /** @var AcceptanceTester $I */
        $I = $this;

      // Grab the node id from the Edit tab once the node has been saved.
        $edit_url = $I->grabAttributeFrom('ul.tabs--primary > li:nth-child(2) > a', 'href');
        $matches = array();

        if (preg_match('~/node/(\d+)/edit~', $edit_url, $matches)) {
            return $matches[1];
        }

        return null;
    }

  /**
   * @param $username
   * @param $nid
   * @return mixed
   */
    public function deleteNodeAsUser($username, $nid)
    {
      /** @var AcceptanceTester $I */
        $I = $this;

      // The same value in different variable to see functions from trait UserCommonSteps.
      /** @var UserCommonSteps $U */
        $U = $this;

      // login as user
      /** @var DrupalTestUser $user */
        $user = $I->getTestUserByName($username);
        $U->login($user->name, $user->pass);

        $I->deleteNodeFromStorage($nid);

      // delete node
        $I->amOnPage(NodePage::route($nid, 'edit'));

        $I->click('#edit-delete');
        $I->see('Are you sure you want to delete');

      // logout
        $U->logout();

        return $nid;
    }

  /**
   * Node loads.
   *
   * @param $nid
   * @return mixed
   */
    public function seeNodePage($nid)
    {
      /** @var AcceptanceTester $I */
        $I = $this;
        $node = node_load($nid);
        $I->amOnPage(NodePage::route($nid));
        $I->see($node->title, Page::$pageTitle);
        $I->seeElement(NodePage::$footerRegion);
        $I->dontSee('Page not found', Page::$pageTitle);
        $I->dontSee('Access denied', Page::$pageTitle);
      //TODO: add better check
    }

  /**
   * Node access denied.
   *
   * @param $nid
   * @return mixed
   */
    public function cantSeeNodePage($nid)
    {
        if ($nid) {
          /** @var AcceptanceTester $I */
            $I = $this;
            $I->amOnPage(NodePage::route($nid));
            $I->see(AccessDenied::$accessDeniedMessage, Page::$pageTitle);
        }
    }

  /**
   * Grab published node of type.
   *
   * @param $type
   * @return null
   */
    public function grabNodeOfType($type)
    {
        $query = db_select('node', 'n');
        $query->fields('n', array('nid'));
        $query->condition('n.type', $type, '=');
        $query->condition('n.status', 1, '=');
        $query->orderBy('n.nid', 'asc');
        $query->range(null, 1);
        $result = $query->execute()->fetchCol();
        return (count($result) > 0 ? $result[0] : null);
    }

  /**
   * Count published nodes of type.
   *
   * @param $type
   */
    public function countNodes($type)
    {
        $query = db_select('node', 'n');
        $query->fields('n', array('nid'));
        $query->condition('n.type', $type, '=');
        $query->condition('n.status', 1, '=');
        return $query->execute()->rowCount();
    }
}
