=========Krokedil dev info=========

=========Requirements=========
You need to have NodeJS and Yarn installed on your system.

1.NodeJS
2.Yarn

==========Translation and assets==========

The plugin we will use minified version by default.
If WordPress's constant SCRIPT_DEBUG exists, the plugin will use the non minified version of assets.

// TODO: add info about how to generate a .mo file.

JS Command
    yarn jsmin

CSS Command
    yarn cssmin

POT Command
    yarn makepot

Build (.min.js, .min.css, .pot )
    yarn build

==========Webpack==========

Development mode
     yarn webpack:dev

===========Linter============

JS
  yarn lintjs


// TODO add phpcs command ( composer )