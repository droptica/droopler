# Droopler Tests

## Structure
```yml
actor: Tester
namespace: Tests
support_namespace: Support
paths:
  tests: tests
  output: tests/_output
  data: tests/Support/Data
  support: tests/Support
  envs: tests/_envs
actor_suffix: Tester
```

## Configuration

Go to the `tests/Acceptance.suite.yml` file and make sure that the `url` and `root` parameters are correct

```json
modules:
    enabled:
        - PhpBrowser:
            url: http://web/
        - DrupalBootstrap:
            root: '/var/www/html/web'
```

## Running tests

To execute all or selected tests:

```bash
ddev tests
ddev tests Acceptance
ddev tests Js_capable
```

You can select the test to run by:

```bash
ddev tests Acceptance ResponseCodeTestCest
ddev tests Js_capable BannerParagraphCest
```
