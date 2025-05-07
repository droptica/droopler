<?php

declare(strict_types=1);

namespace Tests\Support\Helper;

use Codeception\Module;

/**
 * Here you can define custom actions
 * all public methods declared in helper class will be available in $I.
 */
class DrushHelper extends Module
{
  /**
   * Run Drush command.
   */
    public function runDrush($command)
    {
        $fullCommand = "drush $command";
        $output = shell_exec($fullCommand);
        $this->debug("Run drush command: $fullCommand");
        $this->debug("Output: " . ($output ?? 'No output'));
        return $output !== null ? trim($output) : '';
    }

  /**
   * Change user password.
   */
    public function changeUserPassword($username, $newPassword)
    {
        $command = "user:password $username \"$newPassword\" --no-interaction";
        $output = $this->runDrush($command);
        $this->debug("Changed password for user: $username");
        return $output;
    }

    /**
     * Clear cache.
     */
    public function clearCache()
    {
        $command = "cr";
        $output = $this->runDrush($command);
        $this->debug("Cleared cache");
        return $output;
    }

    /**
     * Agregatinon off
     */
    public function agregatinonOff() 
    {
        $this->runDrush('cset -y system.performance css.preprocess 0');
        $this->runDrush('cset -y system.performance js.preprocess 0');
    }

    /**
     * Get published nodes of a given type.
     *
     * @param string $type
     *   The node type machine name.
     * @return array
     *   Array of ['url' => nid, 'langcode' => langcode].
     */
    public function getPublishedNodes($type = 'content_page')
    {
        $command = "sql:query \"SELECT nid, langcode FROM node_field_data WHERE type = '$type' AND status = 1\"";
        $output = $this->runDrush($command);
        $lines = explode("\n", trim($output));
        $result = [];
        foreach ($lines as $line) {
            if (empty($line)) continue;
            $parts = preg_split('/\s+/', trim($line));
            if (count($parts) >= 2) {
                $result[] = ['url' => $parts[0], 'langcode' => $parts[1]];
            }
        }
        return $result;
    }

    // Codeception action to grab published nodes from tests
    public function grabPublishedNodes($type = 'content_page')
    {
        return $this->getPublishedNodes($type);
    }

    /**
     * Fetch published nodes for Acceptance tests (mimics original logic).
     *
     * @param string $type
     * @return array
     */
    public function getPublishedNodesForAcceptance($type = 'content_page')
    {
        $command = "sql:query \"SELECT nid, langcode FROM node_field_data WHERE type = '$type' AND status = 1\"";
        $output = $this->runDrush($command);
        $lines = explode("\n", trim($output));
        $vars = [];
        $nodesID = 1;
        foreach ($lines as $line) {
            if (empty($line)) continue;
            $parts = preg_split('/\s+/', trim($line));
            if (count($parts) >= 2 && is_numeric($parts[0])) {
                $vars[$nodesID]['url'] = $parts[0];
                $vars[$nodesID]['langcode'] = $parts[1];
                $nodesID++;
            }
        }
        return $vars;
    }
}