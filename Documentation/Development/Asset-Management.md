## Asset Management

The issue tracker uses [NPM](https://www.npmjs.com/) and [Laravel Mix](https://github.com/JeffreyWay/laravel-mix) for managing and compiling its frontend assets.
 
### Default Environment

The compiled production assets are checked into the repo and used by default.  If making changes to any assets, you will need to set up the development environment.

### Development Environment

#### Setup

Note: Commands here may need to be prefixed with `sudo` depending on your local configuration

To set up the development environment, you will need to have [Node.js](https://nodejs.org/en/) and NPM installed.  With those installed, run the `npm install` command to install all dependencies.

#### Compiling Assets

If making changes to the assets (updating dependencies or editing the tracker's assets), you will need to recompile the production assets.

While working locally, you can run the `npm run watch` command which will watch for changes and recompile as needed.  Note that this will create unminified assets to make debugging issues easier.

When ready to commit your changes, you must run the `npm run prod` command which will compile and minify the assets.
