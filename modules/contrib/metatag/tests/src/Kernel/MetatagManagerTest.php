<?php

namespace Drupal\Tests\metatag\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Test the Metatag Manager class.
 *
 * @group metatag
 */
class MetatagManagerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'metatag',
    'metatag_open_graph',
  ];

  /**
   * Test the order of the meta tags as they are output.
   */
  public function testMetatagOrder() {
    /** @var \Drupal\metatag\MetatagManager $metatag_manager */
    $metatag_manager = \Drupal::service('metatag.manager');

    $tags = $metatag_manager->generateElements([
      'og_image_width' => 100,
      'og_image_height' => 100,
      'og_image_url' => 'http://www.example.com/example/foo.png',
    ]);

    $expected = [
      '#attached' => [
        'html_head' => [
          [
            [
              '#tag' => 'meta',
              '#attributes' => [
                'property' => 'og:image:url',
                'content' => 'http://www.example.com/example/foo.png',
              ],
            ],
            'og_image_url_0',
          ],
          [
            [
              '#tag' => 'meta',
              '#attributes' => [
                'property' => 'og:image:width',
                'content' => 100,
              ],
            ],
            'og_image_width',
          ],
          [
            [
              '#tag' => 'meta',
              '#attributes' => [
                'property' => 'og:image:height',
                'content' => 100,
              ],
            ],
            'og_image_height',
          ],
        ],
      ],
    ];
    $this->assertEquals($expected, $tags);
  }

  /**
   * Tests metatags with multiple values return multiple metatags.
   */
  public function testMetatagMultiple() {
    /** @var \Drupal\metatag\MetatagManager $metatag_manager */
    $metatag_manager = \Drupal::service('metatag.manager');

    $tags = $metatag_manager->generateElements([
      'og_image_width' => 100,
      'og_image_height' => 100,
      'og_image_url' => 'http://www.example.com/example/foo.png, http://www.example.com/example/foo2.png',
    ]);

    $expected = [
      '#attached' => [
        'html_head' => [
          [
            [
              '#tag' => 'meta',
              '#attributes' => [
                'property' => 'og:image:url',
                'content' => 'http://www.example.com/example/foo.png',
              ],
            ],
            'og_image_url_0',
          ],
          [
            [
              '#tag' => 'meta',
              '#attributes' => [
                'property' => 'og:image:url',
                'content' => 'http://www.example.com/example/foo2.png',
              ],
            ],
            'og_image_url_1',
          ],
          [
            [
              '#tag' => 'meta',
              '#attributes' => [
                'property' => 'og:image:width',
                'content' => 100,
              ],
            ],
            'og_image_width',
          ],
          [
            [
              '#tag' => 'meta',
              '#attributes' => [
                'property' => 'og:image:height',
                'content' => 100,
              ],
            ],
            'og_image_height',
          ],
        ],
      ],
    ];
    $this->assertEquals($expected, $tags);
  }

}
