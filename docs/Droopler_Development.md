# Droopler Development Guide

## Versions and Repositories

### Version 5.x (Current)
The latest version of Droopler is 5.x, which represents significant improvements and changes from previous versions.

#### Main Repository
- **Official Repository**: [drupal.org/project/droopler](https://www.drupal.org/project/droopler)
- **Default Branch**: `5.x`
- **Latest Releases**: Available at [git.drupalcode.org/project/droopler/-/tags](https://git.drupalcode.org/project/droopler/-/tags)

### Version 3.x (Legacy)
For projects still using Droopler 3.x, the code is maintained in GitHub repositories:

#### Legacy Repositories
- **Main Repository**: [github.com/droptica/droopler/tree/3.x](https://github.com/droptica/droopler/tree/3.x)
- **Project Template**: [github.com/droptica/droopler_project/tree/3.x](https://github.com/droptica/droopler_project/tree/3.x)

> **Note**: For new projects, it is recommended to use Version 5.x as it contains the latest features and improvements.

## How to Release a New Version of Droopler 5.x

When preparing a new release of Droopler 5.x, follow these steps:



1. **Test the Release**
   - Perform manual testing of key features (you can use testing_reinstall.sh)
   - (not ready yet) Run tests with playwright (you can use tests/run-playwright-tests.sh)

2. **Update Version Numbers**
   - Update the version number in `composer.json`:
     ```json
     {
       "name": "droptica/droopler",
       "version": "5.0.x-dev" // Change from "5.0.x-dev" to specific version like "5.0.10"
     }
     ```
   - Update the version in `web/profiles/droopler/droopler.info.yml`:
     ```yaml
     name: Droopler 5.0
     type: profile
     core_version_requirement: ^10.3 || ^11
     description: 'Droopler 5.0 profile.'
     version: '5.0.x-dev' // Update to new version number like "5.0.10"
     ```

4. **Create Release**
   - Tag the release on drupal.org with the new version number
   - Ensure the tag follows semantic versioning (e.g., `5.0.10`)
   - Create a new release on drupal.org/project/droople -> Edit -> Releases -> Add new release

5. **Post-Release**
   - Update development branch (`5.x`) version numbers back to development versions:
     - `5.0.x-dev` in `composer.json`
     - `5.0.x-dev` in `droopler.info.yml`

> **Note**: Always follow semantic versioning principles when choosing new version numbers.
