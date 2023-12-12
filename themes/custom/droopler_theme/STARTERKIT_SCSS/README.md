# SCSS Starterkit for Droopler
This is a starter child theme of `droopler_theme`, you may use it as a derivative with your own overrides.

Assume your new theme will be called `foobar`.

1. Copy and rename `STARTERKIT_CSS` to your custom theme location, like `web/themes/custom/foobar`.
2. Change all `STARTERKIT` references to `foobar` (including content and file names).
3. Change `STARTERKIT.rename.yml` to `foobar.info.yml`.

And that's it! Your theme is visible on `Appearance` admin page.

# Working with SCSS

Droopler SCSS Subtheme uses [Webpack](https://webpack.js.org) to compile and
bundle SASS and JS.

#### Step 1
Make sure you have Node and npm installed.
You can read a guide on how to install node here:
https://docs.npmjs.com/getting-started/installing-node

If you prefer to use [Yarn](https://yarnpkg.com) instead of npm, install Yarn by
following the guide [here](https://yarnpkg.com/docs/install).

#### Step 2
Go to the root of the subtheme and run the following commands:
`nvm use`

This command triggers the node version switch specified in the `.nvmrc` file
Read more about [NVM](https://github.com/nvm-sh/nvm)

then:
`npm install` or `yarn install`

#### Step 3
Update `proxy` in **config/proxy.js**.

#### Step 4
Run the following command to compile Sass and watch for changes: `npm run watch`
or `yarn watch`.

There are also other commands for theme developers, here's the full reference:

- **npm run watch** - watches for changes in SCSS and JS and processes them on the fly
- **npm run dev** - cleans derivative files and compiles all SCSS/JS in the subtheme for DEV environment
- **npm run production** - cleans derivative files and compiles all SCSS/JS in the subtheme for PROD environment
- **npm run stylint** - run stylint
- **npm run stylint-fix** - run stylint and fix errors automatically

# Documentation
- [How to maintain the components directory](src/components/README.md)
