<?php

/*
Plugin Name: IMG Custom Post Types
Plugin URI: http://imgiseverything.co.uk/wordpress-plugins/img-custom-post-types/
Description: Functionality to allow easy adding of custom post types via the functions.php file
Author: Phil Thompson
Version: 0.96
Author URI: http://imgiseverything.co.uk/



USAGE

In your functions.php file


if (class_exists('IMGCustomPostTypes') === true ) { // check for plugin activation
	
	// 1 Initialise a new custom post type
	$cpt_example = new IMGCustomPostTypes();
	
	// 2 Add your custom fields
	$cpt_example->customFields = array('example_foo', 'example_bar');
	// 3 Set your naming conventions
	$cpt_example->namingConventions = array(
		'name' 				=> 'example',
		'singular' 			=> 'Example',
		'plural'			=> 'Examples',
		'slug'				=> 'example',
		'tag_name'			=> 'example_tags',
		'tag_singular'		=> 'Example tag',
		'tag_plural'		=> 'Example tags'
	);
	// 4 What WordPress functionality do you want to support?
	$cpt_example->supports = array('title', 'editor', 'excerpt', 'thumbnail', 'page-attributes');
		
}



*/



if ( version_compare(PHP_VERSION, '5.2', '<') ) {
	if ( is_admin() && (!defined('DOING_AJAX') || !DOING_AJAX) ) {
		require_once ABSPATH . '/wp-admin/includes/plugin.php';
		deactivate_plugins( __FILE__ );
	    wp_die( 'IMG Custom Post Types requires PHP 5.2 or higher, as does WordPress 3.2 and higher. The plugin has now disabled itself' );
	} else {
		return;
	}
}



class IMGCustomPostTypes{


	/**
	 *	@var string
	 *	The name of the WordPress NONCE name - used to ensure the form is
	 *	posted by this site and not a spam/malicious attack
	 */
	protected $_nonce = 'imgcustomposttypes_noncename';


	/**
	 *	@var	array
	 *	Custom meta fields for the example post type
	 *	Notes: 
	 *	1 	to get a boolean field call an array key 'fieldname_boolean'
	 *	2	to use a foreign key on another custom post type use 'fieldname_fk' where 'fieldname'
	 *		is the name of the custom post type name.
	 *	3 	to have a textarea displayed instead of an input then call an array key 'fieldname_textarea'
	 */
	public $customFields = array();
	
	
	/**
	 *	@var	array
	 *	The naming conventions of the custom post type.
	 *	Removing the tag_ key means there won't be a custom taxonomy
	 */
	public $namingConventions = array(
		'name' 				=> 'example',
		'singular' 			=> 'Example',
		'plural'			=> 'Examples',
		'slug'				=> 'example'/*,
		'tag_name'			=> 'example_tags',
		'tags_singular'		=> 'Example tag',
		'tags_plural'		=> 'Example tags'*/
	);
	

	/**
	 *	@var	array
	 *	All the WordPress elements to support in custom post type
	 */
	public $supports = array('title', 'editor', 'thumbnail'/*, 'page-attributes', 'comments'*/);

	/**
	 *	Constructor
	 *	@return	void
	 */
	public function __construct(){
		
		
		if(function_exists('add_action')){
		
			// Register new custom post types in the system
			add_action('init', array($this, 'customRegister'));
		
			// Make sure custom field meta boxes show on add/edit post screen
			add_action('admin_init', array($this, 'adminConstruct'));

			// Make sure custom posts new meta field are saved when the post is
			add_action('save_post', array($this, 'saveCustom'));
			

		}
		
		


	}

	
	
	
	/**
	 *	adminConstruct
	 *	Hook into WordPress's functions and run when user is in the wp-admin area
	 *	@return	void
	 */
	public function adminConstruct(){
		
		// Let user add details
		if(!empty($this->customFields)){
			add_meta_box('custom_meta', $this->namingConventions['singular'] . ' details', array($this, 'customMeta'), $this->namingConventions['name'], 'normal', 'high');
		}
		
		wp_enqueue_script('datepicker', plugins_url('/js/datepicker.js', __FILE__), array('jquery'));
		wp_enqueue_script('datejs', plugins_url('/js/date.js', __FILE__), array('jquery', 'datepicker'));
		wp_enqueue_script('img-custom-post-types-js', plugins_url('/js/img-custom-post-types.js', __FILE__), array('jquery', 'datepicker', 'datejs'));
		wp_enqueue_style('img-custom-post-types-css', plugins_url('/css/img-custom-post-types.css', __FILE__));
		
		// Show custom meta fields in posts view
		if(function_exists('add_action')){

			// Let users see the extra fields in Posts view
			add_action('manage_' . $this->namingConventions['name'] . '_posts_custom_column',  array($this, 'customColumns'), 10, 2);
			
		}
		
		
		if(function_exists('add_filter')){
		
			add_filter('manage_edit-' . $this->namingConventions['name'] . '_columns', array($this, 'editColumns'));	
			
			add_filter('manage_edit-' . $this->namingConventions['name'] . '_sortable_columns', array($this, 'registerSortableColumns'));
				
			add_filter('request', array($this, 'orderColumns'));
			
			
			// Remove WordPress SEO's awful extra columns
			add_filter('manage_edit-' . $this->namingConventions['name'] . '_columns', array($this, 'removeWPSEOColumns'));
			
		}
		
		
	}

	/**
	 *	createLabel
	 *	When auto generating form fields this makes a nice user readable form label
	 *	@param	string e.g. HNF_field_name
	 *	@return	string	e.g. Field Name
	 */
	public function createLabel($field){
		$field = str_replace(array('_', 'boolean', 'fk', 'textarea'), array(' ', '', '', ''), $field);
		$field = ucwords($field);
		return $field;
	}
	
	
	/**
	 *	customRegister
	 *	Create a new custom_post_type called 'examples'
	 *	@return	void
	 */ 
	public function customRegister() {
	
		$has_tag = (!empty($this->namingConventions['tag_name'])) ? true : false;
	
		if($has_tag === true){
		
		
			$taxonomy = $this->namingConventions['tag_name'];
		
			// Create a new taxonomy
			register_taxonomy(
				$taxonomy,
				$this->namingConventions['name'],
				array(
					'label' 		=> $this->namingConventions['tag_plural'],
					'sort' 			=> true,
					'args' 			=> array('orderby' => 'term_order'),
					'hierarchical'	=> true,
					'rewrite'		=> array(
						'slug' 			=> str_replace(' ', '-', $this->namingConventions['tag_name']),
						'with_front' 	=> FALSE
					)
				)
			);
		}
	
	 	// Naming conventions for the custom post type
		$labels = array(
			'name' 					=> _x($this->namingConventions['plural'], 'post type general name'),
			'singular_name' 		=> _x($this->namingConventions['singular'], 'post type singular name'),
			'add_new' 				=> _x('Add New', strtolower($this->namingConventions['singular'])),
			'add_new_item' 			=> __('Add New ' . $this->namingConventions['singular']),
			'edit_item' 			=> __('Edit ' . $this->namingConventions['singular']),
			'new_item' 				=> __('New ' . $this->namingConventions['singular']),
			'view_item' 			=> __('View ' . $this->namingConventions['singular']),
			'search_items' 			=> __('Search ' . $this->namingConventions['plural']),
			'not_found' 			=> __('Nothing found'),
			'not_found_in_trash'	=> __('Nothing found in Trash'),
			'parent_item_colon' 	=> ''
		);
	 
		$args = array(
			'labels' 			=> $labels,
			'public' 			=> true,
			'publicly_queryable'=> true,
			'show_ui' 			=> true,
			'query_var' 		=> true,
			'rewrite' 			=> array(
				'slug'			=> $this->namingConventions['slug'],
				'with_front' 	=> FALSE
			),
			'capability_type' 	=> 'post',
			'hierarchical' 		=> false,
			'menu_position' 	=> null
		  ); 
		  
		  
		$args['supports'] = $this->supports;
		  
		if($has_tag === true){
		 	$args['taxonomies']	 = array($this->namingConventions['tag_name']);	
		}
	 
		register_post_type( $this->namingConventions['name'] , $args );
		
		if($has_tag === true){
			// Not sure if the following does anything.
			//register_taxonomy_for_object_type($this->namingConventions['tag_name'], $this->namingConventions['name']);
		}
		
	}
	
	
	
	/**
	 *	customMeta
	 *	Create custom_fields meta box for the examples
	 *	@return	void
	 */
	public function customMeta(){
	
		global $post;	
		
		
		$data = array();
		
		foreach($this->customFields as $field){
			$data[$field] = get_post_meta($post->ID, $field, true);
		}

		foreach($this->customFields as $field){
		
			if(strpos($field, '_boolean') !== false){
				
				// This is a Yes/No field
					
				$checked = ($data[$field] == 'yes') ? ' checked="checked"' : '';
			
				echo '<p>
				<input type="hidden" name="' . $field . '" value="no" />
				<input type="checkbox" value="yes" id="' . $field . '" name="' . $field . '"' . $checked . ' />
				<label for="' . $field . '">' . $this->createLabel($field) . '?</label>
				</p>';
				
			} else if(strpos($field, '_fk') !== false){
			
				// This is a `Foreign Key` field
			
				$args = array( 
					'numberposts' 	=> -1, 
					'order'			=> 'ASC',
					'orderby'       => 'title',
					'post_status' 	=> null, 
					'post_type' 	=> str_replace('_fk', '', $field)
				); 
				
				$custom_posts = get_posts($args);
			
				
				echo '<p>
				<label for="' . $field . '">' . $this->createLabel($field) . '</label><br />
				<select id="' . $field . '" name="' . $field . '">
					<option value="">Choose</option>';
				
					foreach($custom_posts as $custom_post){
						$selected = ($custom_post->ID == $data[$field]) ? ' selected="selected"' : ''; 
						echo  '<option value="' . $custom_post->ID . '"' . $selected . '>' . $custom_post->post_title . '</option>';
					}
					
				echo '</select>
				</p>';
				
			} else if(strpos($field, '_textarea') !== false){
			
				// This is a textbox field
				
				echo '<p>
				<label for="' . $field . '">' . $this->createLabel($field) . '</label><br />
				<textarea id="' . $field . '" name="' . $field . '" rows="5" cols="100">' . $data[$field] . '</textarea>
				</p>';
				
			} else if(strpos($field, '_date') !== false){
				
				
				// This is a date field - we'll use a javascript datepicker to make this easy for users
				echo '<p>
				<label for="' . $field . '">' . $this->createLabel($field) . '</label><br />
				<input type="date" value="' . $data[$field] . '" id="' . $field . '" name="' . $field . '" size="10" class="date" />
				</p>
				<p class="date-instruction">Please enter your date in the YYYY-MM-DD format e.g. ' . date('Y-m-d') .  '</p>';
				
				
			} else{
			
				// This is a normal field
				
				echo '<p>
				<label for="' . $field . '">' . $this->createLabel($field) . '</label><br />
				<input type="text" value="' . $data[$field] . '" id="' . $field . '" name="' . $field . '" size="50" />
				</p>';
				
			}

		}
		
	
	}
	
		
	/**
	 *	saveCustom
	 *	Save custom fields to the database when saving the post
	 *	@return	void
	 */
	public function saveCustom(){
	
		global $post;
	
		$post_id = $post->ID;
	
		// Prevent metadata or custom fields from disappearing…
		if ( 
			(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) 
			|| $post->post_type != $this->namingConventions['name']
		){
			return $post->ID;
		}
		
		// Don't update when (un)trashing because it wipes meta data
		if(
			!empty($_GET['action']) 
			&& ($_GET['action'] == 'trash' || $_GET['action'] == 'untrash')
		){
			return;
		}
		
		
		// Update custom fields with $_POST values
		foreach($this->customFields as $field){
			update_post_meta($post->ID, $field, $_POST[$field]);
			
			
			// Okay - we've got a date field so let's timestamp it and add it to the database
			// so we can order by it in get_posts() calls
			if(strpos($field, '_date') !== false){
				update_post_meta($post->ID, $field . '_strtotime', strtotime($_POST[$field]));
			}
			
			
		}
		
	}
	


	
	/**
	 *	editColumns
	 *	Data to show in the columns on WordPress' examples listings screen
	 *	@param	string
	 *	@return	array
	 */ 
	public function editColumns($columns){
		
		// Date columns displays after title but we want it at the end so unset it here and add it back in at the end
		unset($columns['date']);
		
		if(!empty($this->namingConventions['tag_name'])){
			$columns['custom_tags'] = $this->namingConventions['tag_plural'];
		}
		
		
		/*
		Put all $customFields into $columns list so they can be printed out on the listing page - 
		namespace them to avoid double printing of values if 2 custom post types have
		share $fields
		Don't show _textarea fields because they're too long and ruin the layout
		*/
		foreach($this->customFields as $field){
		
			if(strpos($field, '_textarea') === false){
				$columns[$this->namingConventions['singular'] . '_' . $field] = $this->createLabel($field);
			}
		}
		
		
		// Show thumbnail
		if(in_array('thumbnail', $this->supports)){
			$columns['thumbnail'] = 'Image';
		}
		
		$columns['date'] = 'Date';
		
	 
		return $columns;
	  
	}
	
	/**
	 *	customColumns
	 *	Data to show in the columns on WordPress' listing screen
	 *	@param	string
	 *	@return	void
	 */ 
	public function customColumns($column, $post_id){
		
		
		// remove namespacing
		$column = str_replace($this->namingConventions['singular'] . '_', '', $column);
		
		
		if(!empty($this->namingConventions['tag_name'])){
			$custom_tags = get_the_terms($post_id, $this->namingConventions['tag_name']);
		}
		
		
		switch ($column) {
		
			default:
				break;
		
			case 'custom_tags':
				// Print out the custom taxonomy/categories
				if(!empty($custom_tags)){
					foreach($custom_tags as $custom_tag){
						echo $custom_tag->name . ' ';
					}
				}
				break;
			
			case 'thumbnail':
				the_post_thumbnail(array(50,50), array('alt' => '', 'title' => ''));
				break;
		  
		}
		
		
		
		
		
		if(in_array($column, $this->customFields)){
		
			if(strpos($column, '_fk') !== false){
				// This is a `Foreign Key` so show it's title (not its ID) and link to its edit screen
				if($fk_id = get_post_meta($post_id, $column, true)){
					echo '<a href="/wp-admin/post.php?post=' . $fk_id . '&action=edit" title="Edit this">' . get_the_title($fk_id) . '</a>';
				}
			} else{
				echo get_post_meta($post_id, $column, true);
			}
			
		}


		
	}
	
	
	
	/**
	 *	registerSortableColumn
	 *	Register the column as sortable
	 *	@param	array
	 *	@return	void
	 */ 
	public function registerSortableColumns( $columns ) {
		
		foreach($this->customFields as $field){
			$columns[$this->namingConventions['singular'] . '_' . $field] = $field;
		}
	 
		return $columns;
	}

	
	
	/**
	 *	orderColumns
	 *	Order data table by a custom column
	 *	@return	array
	 */
	public function orderColumns( $vars ) {
		
		if ( isset( $vars['orderby'] ) && in_array($vars['orderby'], $this->customFields) ) {
			$vars = array_merge( $vars, array(
				'meta_key' => strtolower($vars['orderby'])
			) );
		}
	 
		return $vars;
	}
	
	
	
	// 
	/**
	 *	removeWPSEOColumns
	 *	Remove WordPress SEO's awful extra columns
	 *	@param	array
	 *	@return	array
	 */
	public function removeWPSEOColumns($columns){
	    unset($columns['wpseo-score']);
	    unset($columns['wpseo-title']);
	    unset($columns['wpseo-metadesc']);
	    unset($columns['wpseo-focuskw']);
	    return $columns;
	}
	
	
	
} // end class

