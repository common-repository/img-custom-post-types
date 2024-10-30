=== IMG Custom Post Types ===
Contributors: imgiseverything
Author URL: imgiseverything.co.uk/wordpress-plugins/img-custom-post-types/
Tags: custom, posts
Requires at least: 3.3
Tested up to: 3.4.1
Stable tag: trunk


== Description ==


Custom Post Types, are by far and away one of the best additions to WordPress. However, they can be a bit cumbersome to set-up.

With the IMG Custom Post Types plug-in creating basic custom post types is now easy.



== Installation ==

1. Upload the `img-custom-post-types` folder to the `/wp-content/plugins/` directory
1. Activate the IMG Custom Post Types plugin through the 'Plugins' menu in WordPress
1. Add new custom post types by editing your functions.php file using these examples


- Usage: -



Simple example:
https://gist.github.com/1939891

Example with custom meta fields:
https://gist.github.com/1939907

Complex example:
https://gist.github.com/1939868

Example with custom meta fields and data types:
https://gist.github.com/1939932

Retrieving custom post type and Ordering by a date based custom field
https://gist.github.com/2157663


== Screenshots ==

1. Add/Edit screen of your custom post type
2. View all listings for your custom post type

== Changelog ==

= 0.96 =
* Take advantage of new type="date" input attribute/value on date fields. Removal of custom date picker where HTML5 is supported

= 0.951 =
* Bug fixed where NULL/unset foreign keys field shows the post title in table listings view

= 0.95 =
* If thumbnails are supported then they are shown in the listings screen of the custom post type
* Instead of just one to one relationships between two custom post types you can now assign one to many relationships by naming a custom field say `example_taxonomy`


= 0.941 =
* Date picker CSS and JavaScript improved (a tiny tiny bit)

= 0.94 =
* Bug fixed where custom taxonomies were added to posts as well as the new custom post type

= 0.93 =
* Ability to use a datepicker to date fields in custom fields
* Date fields get timestamped too, so order by date in the get_posts() function with the meta_compare value should work
* Bug fixed where custom meta fields were not showing in View Posts listings view
* Longer copy custom fields (indicated by an _textarea naming convention) not show in View Posts listings view

= 0.92 =
* Read me file improved. 
* Screenshots added.

= 0.91 =
* Tiny bug fixed with the naming of custom fields
