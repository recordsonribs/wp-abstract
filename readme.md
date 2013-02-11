# WordPress Abstract

[![Build Status](https://api.travis-ci.org/recordsonribs/wp-abstract.png)](http://travis-ci.org/recordsonribs/wp-abstract)

Creating custom post types and using other advanced features of WordPress can sometimes be a bit annoying, resulting in very repetitive code. 

WordPress Abstract, from [Records On Ribs](http://recordsonribs.com) hopes to make it a little easier from the perspective of the coder. We are using it to re-write our [Ribcage](http://github.com/recordsonrib/ribcage) plugin faster.

The hope of WordPress Abstract is that it provides a simple way in which to do things in WordPress that may be a little obscure, so plugins and themes aren't rammed with boilerplate code and you can get down to making functionality.

## Adding WordPress Abstract To Your Code

Either install WordPress Abstract as a WordPress plugin in the standard way and begin using as below, or simply include this one file in your code for your theme or plugin using `require_once`. Done!

## Custom Post Types

### Creating a Custom Post Type

Simply do the following:
    
    $records = new WP_Abstract_Post_Type('records');

And that is it! A custom post type called records will be created with quite a few bells and whistles.

Okay, so you want the custom post type to be called internally 'something' and in the interface 'thing'?

    $something = new WP_Abstract_Post_Type('something', 'thing');

Done! And say the plural of 'thing' is 'stuffs'?

    $something = new WP_Abstract_Post_Type('something', 'thing', 'stuffs');

And we are away!

### Using Named Arguments

Sadly for whatever reason the PHP developers don't like named arguments. But lets fake it anyway by running an array into WP_Abstract_Post_Type.

    $records = new WP_Abstract_Post_Type(array('name' => 'records'));

And we are away! The rest of the examples assume this syntax.

### Using WordPress Normal Custom Post Type Settings

Say you want to create records, but want to make `has_archive` when the custom post type is setup, which defaults to true, instead be false? We can do this by using the ovewrite parameter.

	$params = array('name' => 'records');
	$params['overwrite'] = array('has_archive' => false);

	$records = new WP_Abstract_Post_Type($params);

And we have no archive!

### Overwriting Titles Of Default Metaboxes

When you create a custom post type you are left with a load of WordPress boilerplate text inherited from posts and pages hanging about. But we don't 'Featured Image', we want something like 'Record Sleeve'! No problem at all, you don't even need to know the name of the metabox internally, just the text it normally displays, in this case, 'Featured Image'.

Then just issue the following, passing our changes into an array in overwrite with the name 'meta_box_titles'.

    $meta_box_titles = array('Featured Image' => 'Record Sleeve');
    $overwrite = array('meta_box_titles' => $meta_box_titles);

    $records = new WP_Abstract_Post_Type(array('name' => 'records', 'overwrite' => $overwrite);

Done! The when you run `the_post_thumbnail();` on the front end then it'll show what the user submitted in the 'Record Sleeve' metabox.

### Overwriting Everything In Default Metaboxes

Right now, the only thing you can over-write is the instructions for the Featured Image.

	$params['name'] = 'records';
	$params['overwrite'] = array('featured_image_instruction' => 'The cover image of the record.');

	$records = new WP_Abstract_Post_Type($params);

Soon we shall use the translation matrix to overwrite anything you see on screen!

### Overwriting The Title Prompt

We've all seen the boring 'Enter title here' in WordPress thousands of times. Lets change it for our record post type.

	$records = new WP_Abstract_Post_Type(array('name' => 'records', 'overwrite' => array('enter_post_here' => 'The title of the record'));

And we are away!

## Flash Messages

If you have an error in your submission or something you want to show a quick flash message to let the user know in the admin interface. Or perhaps you want a permanent message, for example, telling the admin to update a setting.

WordPress Abstract can help out here as well, and you get a deal for free - for example, the ability of the user to surpress an error message.

It uses good old transients to save the queue of messages (though this may change in future) and remembers if messages are hidden on a per user basis. Because of the queuing, you can add as many as you like at the same time as well.

### Setting Up

Simple create an instance of WordPress Abstract Flash - done!

	$flash = new WP_Abstract_Flash();

We'll probably convert this to a singleton soon, but for the moment, lets use this. It need to be a global variable obviously.

### Create A Notice

A notice is something that can be used from, say, when a user saves a custom post type.

    $flash->notice('Something happened - I am telling you about.');

### Create A Error Message

A error message in red can be displayed using the following.

	$flash->error('Something went badly wrong.');

### Create A Sticky Message

Sticking messages come in two flavours, the same as normal errors, notice and error.

    $flash->sticky_notice('Telling you to do something');
    $flash->sticky_error('Something is badly wrong.');

You can clear the sticky notices in code.

    $flash->clear_sticky_error('Text of the error to clear.');
    $flash->clear_sticky_notice('Text of the notice to clear.');
    $flash->clear_sticky_messages(); // Clear all sticky messages.

## Soon

A quick list of other things this will implement soon enough.

* Customised columns for a custom post type, the easy way.
* A better way of doing the things attached to `init` to allow on the fly changes.
* Less nested arrays for parameters, use them if you like, or don't!
* Taxonomies with just as much customisation.
* Change the default text of any default metabox, easily.
* Hierachical custom post types, handled.
* Integration with [Meta-Box](https://github.com/rilwis/meta-box) - this sort of works if you create your custom post type by extending the class and overload the `metaboxes` function to add your functions.
* CLI to create scaffold WordPress code, probably using [wp-cli](https://github.com/wp-cli/wp-cli).

