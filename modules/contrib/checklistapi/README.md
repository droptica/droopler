# Checklist API

## Contents of This File

- [Introduction](#introduction)
- [Installation](#installation)
- [Implementation](#implementation)
- [Drush](#drush)


## Introduction

Current Maintainer: [TravisCarden](https://www.drupal.org/u/traviscarden)

Checklist API provides a simple interface for modules to create fillable,
persistent checklists that track progress with completion times and users. See
checklistapi_example.module for an example implementation.


## Installation

Checklist API is installed in the usual way. See [Installing contributed
modules](https://www.drupal.org/documentation/install/modules-themes/modules-8).


## Implementation

Checklists are declared as multidimensional arrays using
`hook_checklistapi_checklist_info()`. They can be altered using
`hook_checklistapi_checklist_info_alter()`. Checklist API handles creation of
menu items and permissions. Progress details are saved in one config file per
checklist.

See checklistapi.api.php for more details.


## Drush

Checklist API has Drush integration. To see the list of available commands, run
`drush --filter=checklistapi`. For more about Drush, including installation
instructions, see the [Drush docs](http://drush.org/).
