<?php

namespace Tests\Support\Helper;

use Faker\Factory;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\user\Entity\User;

class TestUsers extends \Codeception\Module
{
        /**
         * A list of user ids created during test suite.
         *
         * @var array
         */
        protected $usersToDelete;

        /**
         * HOOK: Run before each suite to create test users.
         */
    public function _beforeSuite($settings = array())
    {
        $users = [
        [
            'name' => "user_admin",
            'role' => ['administrator', 'authenticated'],
        ],
        [
            'name' => "user.authenticated",
            'role' => ['authenticated'],
        ]
        ];
        foreach ($users as $key => $value) {
            $faker = Factory::create();
            /** @var \Drupal\user\Entity\User $user */
            try {
                $user = \Drupal::entityTypeManager()->getStorage('user')->create([
                    'name' => $value['name'],
                    'mail' => $faker->email,
                    'roles' => $value['role'],
                    'pass' => 'password',
                    'status' => 1,
                ]);

                $user->save();
                $this->usersToDelete[] = $user->id();
            } catch (\Exception $e) {
            }
        }
    }

    /**
     * HOOK: Run after each suite to delete unnecessary files.
     */
    public function _afterSuite()
    {
//        unlink('admin.txt');
        $delete = getenv('DELETE_TEST_USERS');
        if (isset($this->usersToDelete) && $delete == 1) {
            $users = User::loadMultiple($this->usersToDelete);
            /** @var \Drupal\user\Entity\User $user */
            foreach ($users as $user) {
                $this->deleteUsersContent($user->id());
                try {
                    $user->delete();
                } catch (\Exception $e) {
                    continue;
                }
            }
        }
    }

    /**
     * Delete user created entities.
     *
     * @param string|int $uid
     *   User id.
     */
    private function deleteUsersContent($uid)
    {
        $errors = [];
        $cleanup_entities = $this->_getConfig('cleanup_entities');
        if (is_array($cleanup_entities)) {
            foreach ($cleanup_entities as $cleanup_entity) {
                if (!is_string($cleanup_entity)) {
                    continue;
                }
                try {
                    /** @var EntityStorageInterface $storage */
                    $storage = \Drupal::entityTypeManager()->getStorage($cleanup_entity);
                } catch (\Exception $e) {
                    $errors[] = 'Could not load storage ' . $cleanup_entity;
                    continue;
                }
                try {
                    $bundles = \Drupal::service('entity_type.bundle.info')->getBundleInfo($cleanup_entity);
                    foreach ($bundles as $bundle => $bundle_data) {
                        $all_bundle_fields = \Drupal::service('entity_field.manager')->
                            getFieldDefinitions($cleanup_entity, $bundle);
                        if (isset($all_bundle_fields['uid'])) {
                            $entities = $storage->loadByProperties(['uid' => $uid]);
                        }
                    }
                } catch (\Exception $e) {
                    $errors[] = 'Could not load entities of type ' . $cleanup_entity . ' by uid ' . $uid;
                    continue;
                }
                try {
                    foreach ($entities as $entity) {
                        $entity->delete();
                    }
                } catch (\Exception $e) {
                    $errors[] = $e->getMessage();
                    continue;
                }
            }
        }
        if ($errors) {
            $this->fail(implode(PHP_EOL, $errors));
        }
    }
}
