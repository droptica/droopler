# Droopler Development Guide

## Versions and Repositories

### Version 5.1.x (Current)
The latest version of Droopler is 5.1.x, which represents significant improvements and changes from previous versions.

#### Main Repository
- **Official Repository**: [drupal.org/project/droopler](https://www.drupal.org/project/droopler)
- **Default Branch**: `5.1.x`
- **Latest Releases**: Available at [git.drupalcode.org/project/droopler/-/tags](https://git.drupalcode.org/project/droopler/-/tags)

### Version 3.x (Legacy)
For projects still using Droopler 3.x, the code is maintained in GitHub repositories:

#### Legacy Repositories
- **Main Repository**: [github.com/droptica/droopler/tree/3.x](https://github.com/droptica/droopler/tree/3.x)
- **Project Template**: [github.com/droptica/droopler_project/tree/3.x](https://github.com/droptica/droopler_project/tree/3.x)

> **Note**: For new projects, it is recommended to use Version 5.x as it contains the latest features and improvements.

## How to Release a New Version of Droopler 5.1.x

When preparing a new release of Droopler 5.1.x, follow these steps:



1. **Test the Release**
   - Perform manual testing of key features (you can use testing_reinstall.sh)
   - (not ready yet) Run tests with playwright (you can use tests/run-playwright-tests.sh)

2. **Update Version Numbers**
   - Update the version number in `composer.json`:
     ```json
     {
       "name": "droptica/droopler",
       "version": "dev-5.1.x" // Change from "dev-5.1.x" to specific version like "5.1.1"
     }
     ```
   - Update the version in `web/profiles/droopler/droopler.info.yml`:
     ```yaml
     name: Droopler 5.1
     type: profile
     core_version_requirement: ^10.3 || ^11
     description: 'Droopler 5.1 profile.'
     version: 'dev-5.1.x' // Update to new version number like "5.1.1"
     ```

4. **Create Release**
   - Tag the release on drupal.org with the new version number
   - Ensure the tag follows semantic versioning (e.g., `5.1.1`)
   - Create a new release on drupal.org/project/droople -> Edit -> Releases -> Add new release

5. **Post-Release**
   - Update development branch (`5.1.x`) version numbers back to development versions:
     - `dev-5.1.x` in `composer.json`
     - `dev-5.1.x` in `droopler.info.yml`

> **Note**: Always follow semantic versioning principles when choosing new version numbers.
