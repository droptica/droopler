# Installation

droopler_theme theme uses [Webpack](https://webpack.js.org) to compile and
bundle SASS and JS.

#### Step 1
Make sure you have Node and npm installed.
You can read a guide on how to install node here:
https://docs.npmjs.com/getting-started/installing-node

If you prefer to use [Yarn](https://yarnpkg.com) instead of npm, install Yarn by
following the guide [here](https://yarnpkg.com/docs/install).

#### Step 2
Go to the root of droopler_theme theme and run the following commands:
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
