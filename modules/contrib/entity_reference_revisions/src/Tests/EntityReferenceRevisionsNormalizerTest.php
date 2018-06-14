<?php

namespace Drupal\entity_reference_revisions\Tests;

use Drupal\field_ui\Tests\FieldUiTestTrait;
use Drupal\node\Entity\Node;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the entity_reference_revisions configuration.
 *
 * @group entity_reference_revisions
 */
class EntityReferenceRevisionsNormalizerTest extends WebTestBase {

  use FieldUiTestTrait;
  use EntityReferenceRevisionsCoreVersionUiTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array(
    'node',
    'field',
    'entity_reference_revisions',
    'field_ui',
    'block',
    'hal',
    'serialization',
    'rest',
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Create paragraphs and article content types.
    $this->drupalCreateContentType(array('type' => 'entity_revisions', 'name' => 'Entity revisions'));
    $this->drupalCreateContentType(array('type' => 'article', 'name' => 'Article'));
    // Place the breadcrumb, tested in fieldUIAddNewField().
    $this->drupalPlaceBlock('system_breadcrumb_block');
  }

  /**
   * Tests the entity reference revisions configuration.
   */
  public function testEntityReferenceRevisions() {
    $admin_user = $this->drupalCreateUser(array(
      'administer site configuration',
      'administer nodes',
      'create article content',
      'administer content types',
      'administer node fields',
      'administer node display',
      'administer node form display',
      'edit any article content',
    ));
    $this->drupalLogin($admin_user);
    // Create entity reference revisions field.
    static::fieldUIAddNewField('admin/structure/types/manage/entity_revisions', 'entity_reference_revisions', 'Entity reference revisions', 'entity_reference_revisions', array('settings[target_type]' => 'node', 'cardinality' => '-1'), array('settings[handler_settings][target_bundles][article]' => TRUE));
    $this->assertText('Saved Entity reference revisions configuration.');

    // Create an article.
    $title = $this->randomMachineName();
    $edit = array(
      'title[0][value]' => $title,
      'body[0][value]' => 'Revision 1',
    );
    $this->drupalPostNodeForm('node/add/article', $edit, t('Save and publish'));
    $this->assertText($title);
    $this->assertText('Revision 1');
    $node = $this->drupalGetNodeByTitle($title);

    // Create entity revisions content that includes the above article.
    $err_title = 'Entity reference revision content';
    $edit = array(
      'title[0][value]' => $err_title,
      'field_entity_reference_revisions[0][target_id]' => $node->label() . ' (' . $node->id() . ')',
    );
    $this->drupalPostNodeForm('node/add/entity_revisions', $edit, t('Save and publish'));
    $this->assertText('Entity revisions Entity reference revision content has been created.');
    $err_node = $this->drupalGetNodeByTitle($err_title);

    $this->assertText($err_title);
    $this->assertText($title);
    $this->assertText('Revision 1');

    // Create 2nd revision of the article.
    $edit = array(
      'body[0][value]' => 'Revision 2',
      'revision' => TRUE,
    );
    $this->drupalPostNodeForm('node/' . $node->id() . '/edit', $edit, t('Save and keep published'));
    $serializer = $this->container->get('serializer');
    $normalized = $serializer->normalize($err_node, 'hal_json');
    $request = \Drupal::request();
    $link_domain = $request->getSchemeAndHttpHost() . $request->getBasePath();
    $this->assertEqual($err_node->field_entity_reference_revisions->target_revision_id, $normalized['_embedded'][$link_domain . '/rest/relation/node/entity_revisions/field_entity_reference_revisions'][0]['target_revision_id']);
    $new_err_node = $serializer->denormalize($normalized, Node::class, 'hal_json');
    $this->assertEqual($err_node->field_entity_reference_revisions->target_revision_id, $new_err_node->field_entity_reference_revisions->target_revision_id);
  }

}
