# Thimble

Tumblr Theme development tool. Protects your fingers from tedious copy-pasting.

It is no longer actively maintained.

## What is this?

Developing a Theme for Tumblr can be tedious: occupied by a large amount of copying and pasting a Tumblr template into their customize tool. **Thimble** gives you, the Tumblr Theme developer, a canvas to test your theme before moving it into Tumblr. Think of it as a place to work out your theme's rough draft. And you can work offline.

Follow along at: http://www.tumblr.com/docs/en/custom_themes

### Install It

Put it in a PHP server. Any flavor of *AMP will do. I don't recommend putting it on a public-facing server. Point your browser to `index.php`.

I don't know if it will work with a version of PHP less than 5.2.11.

## How does it work?

Thimble does a bad impression of Tumblr's templating system. If you could imagine a terrible impression of Jerry Lewis, and Jerry Lewis was Tumblr's templating system, that's what Thimble is. It's not foolproof, and it can be unpredictable, but it should give you a good approximation of what your theme will look like. It tries its best to conform to [Tumblr's theme docs](http://www.tumblr.com/docs/en/custom_themes). 

Its only goal is to give you a reasonable idea of what your theme will look like.

Put your theme in the theme directory. You'll be able to select it from the application.

It reads data out of a [YAML](http://yaml.org/) file. If you'd like to render your own custom data, take a look at `data/demo.yml` for reference. Create a new YAML document in the data directory, and in `theme.php`, set the variable `$DATA` to your document's filename.

### Caveats

+ Thimble provides the basic index page, similar to the one found in the Tumblr customize tool. Other page types (permalinks, search pages) are currently not supported, but might be in the future.
+ <strike>Thimble does not yet support Answer posts.</strike> Answer posts have arrived in Version 0.3! Thanks to [drtangible](http://github.com/drtangible) for a great contribution!
+ Thimble does not yet support listing Liked posts. Planned for a future release.
+ Thimble does not yet support Group blog stuff. Future release.
+ <strike>Thimble doesn't allow you to manipulate meta options in the UI.</strike> You can manipulate Apperance Options (the stuff in Meta tags) as of Version 0.3.
+ Thimble does not support Photosets.
+ It could be missing other stuff... I don't know.

## Copyright

Thimble is Copyright (c) 2010 Mark Wunsch and is licensed under the [GPL License](http://www.gnu.org/licenses/gpl.html). 

Tumblr is Copyright (c) Tumblr, Inc. Thimble is NOT affiliated with Tumblr, Inc.

[Redux](http://www.tumblr.com/theme/433) theme by Jacob Bijani.
