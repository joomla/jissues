## Asset Management

The issue tracker uses [Bower](http://bower.io) and [Grunt](http://gruntjs.com) for managing and compiling its media assets.
 
### Default Environment

The compiled production assets are checked into the repo and used by default.  If making changes to any assets (anything related to Bower or the tracker's JavaScript API), you will need to set up the development environment.

### Development Environment

#### Setup

Note: Commands here may need to be prefixed with `sudo` depending on your local configuration

To set up the development environment, you will need to have [npm](https://www.npmjs.com) and Bower installed.  With those installed, run the following commands to set up all dependencies:
  
```sh
npm install
bower install
```

This will install the necessary dependencies to your local system.

To set up your development environment to use the uncompiled assets, you will need to run this command:

```sh
grunt bower
```

This command copies all required Bower dependencies from the `bower_components` directory to the `www/media/*/vendor` directories (both of these paths are gitignored).  You can now set the `debug.template` configuration key to `1` to enable the rendering engine's debug mode which uses the uncompiled assets.

#### Compiling Assets

If making changes to the assets (updating Bower dependencies or editing the tracker's JavaScript API), you will need to recompile the production assets.  You can do that with this command:

```sh
grunt
```

This triggers the default `grunt` command which does the following operations; the command to trigger only these steps is also noted:

- Copy Bower assets to `www/media/*/vendor` (`grunt:bower`)
- Create combined (non-compressed) assets at `www/media/*/vendor.*` (`grunt:bower_concat`)
- Minify `www/media/js/vendor.js` (`grunt:uglify:bower`)
- Minify `www/media/js/jtracker.js` (`grunt:uglify:core`)
- Fix certain paths in `www/media/css/vendor.css` (`grunt:replace`)
- Minify `www/media/css/vendor.css` (`grunt:cssmin`)
- Copy the Octicon fonts to `www/media/fonts` (`grunt:copy:octicons`)
- Copy the Blueimp images to `www/media/img` (`grunt:copy:upload`)
- Copy the jQuery localization plugin's translations to `www/media/js/validation` (`grunt:copy:validation`)
