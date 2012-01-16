<?php
/*
Plugin Name: WordPress Abstract
Plugin URI: http:/recordsonribs.com/ribcage
Description: A set of useful OO abstractions for common WordPress tasks.
Version: 0.1
Author: Alex Andrews
Author URI: http:/alexandrews.info
License: GPL2
*/
/*  
Copyright 2012  Alex Andrews  (alex@recordsonribs.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

$records = new wp_abstract_post_type('records');

class wp_abstract_post_type {
	static $namespace = '';

	public $name = '';
	public $single = '';
	public $plural = '';

	public $messages = array();

	function __construct ($name, $single = false, $plural = false, $complex = false) {
		if (substr($name, -1) == 's') {
			$this->name = rtrim($name, 's');
		}
		else {
			$this->name = $name;
		}
		
		if ($single){
			$this->single = ucfirst(strtolower($single));
		}
		else {
			$this->single = ucfirst(strtolower($this->name));
		}
		
		if (! $plural) {
			$this->plural = $this->single . 's';
		}
		
		add_action('init', array ($this, 'init'));
	}
	
	function init ($overwrite = array()) {
		$args = array(
			'labels' => $this->create_labels(),
			'description' => '',
			'public' => true,
			'publicly_queryable' => true,
			'show_ui' => true,
			'capability_type' => 'post',
			'has_archive' => true,
			'hierarchical' => false,
			'show_in_menu' => true,
			'menu_position' => 50,
			'supports' => array('title', 'editor', 'author', 'thumbnail', 'excerpt'),
			'register_meta_box_cb' => array($this, 'metaboxes'),
			'rewrite' => array(
				'slug' => strtolower($this->single),
				'with_front' => false,
				'feeds' => true,
				'pages' => true
			),
			'can_export' => true,
			'show_in_nav_menus' => true,
		);
		
		$args = array_merge($args, $overwrite);
		
		register_post_type ($this->name, $args);

		add_filter('post_updated_messages', array($this, 'post_updated_messages'));
	}
	
	function post_updated_messages($messages) {
		global $post;
		
		$this->messages[$this->name] = array(
			0 => '', // Unused in WordPress messages
			1 => sprintf("$this->single updated. <a href='%s'>View $this->single</a>", esc_url(get_permalink($post->ID))),
			2 => __("Custom field updated."),
			3 => __("Custom field updated."),
			4 => __("$this->single updated."),
			5 => isset($_GET['revision']) ? sprintf( "$this->single restored to revision from %s", wp_post_revision_title( (int) $_GET['revision']), false) : false,
			6 => sprintf( "$this->single published. <a href='%s'>View $this->single</a>", esc_url(get_permalink($post->ID) )),
			7 => __("$this->single saved."),
		    8 => sprintf( "$this->single submitted. <a target='_blank' href='%s'>Preview $this->single</a>", esc_url( add_query_arg( 'preview', 'true', get_permalink($post->ID)))),
			9 => sprintf('Page scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview page</a>', date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date)), esc_url( get_permalink($post->ID) )),
			10 => sprintf("$this->single draft updated. <a target=\"_blank\" href=\"%s\">Preview $this->single</a>", esc_url( add_query_arg( 'preview', 'true', get_permalink($post->ID)))),
		);

		return array_merge($messages, $this->messages);
	}
	
	function metaboxes () {
		return;
	}
	
	function create_labels () {
		return array(
			'name' => $this->plural,
			'singular_name' => $this->single,
			'add_new' => __("Add New $this->single"),
			'all_items' => "All $this->plural",
			'add_new_item' => "Add New $this->single",
			'edit_item' => "Edit $this->single",
			'new_item' => "New $this->single",
			'view_item' => "View $this->single",
			'search_items' => "View $this->plural",
			'not_found' => "No $this->plural found",
			'not_found_in_trash' => "No $this->plural found in trash",
			'menu_name' => $this->plural
		);
	}

	/**
	 * Flashes a new message
	 *
	 * Modern MVV frameworks have rather spoilt us with their flash menthods.
	 * We can just use these to display a message, rather than anything else.
	 * 
	 * The following is a shortcut method for doing this that is location aware.
	 *
	 * $this->flash('You didn't do this right);
	 *
	 * Should be all you need to raise when you want to show a message to the user
	 * after, say, a post update.
	 *
	 * @return void
	 * @author Alex Andrews
	 * @version 0.1
	 * @since 0.1
	 */
	function flash ($message, $number) {
		if (! $number) {
			$number = $this->message_code;
			$this->message_code++;
		}

		$this->messages[$number][$message] = $message;

		$this->current_message = $number;

		add_filter('redirect_post_location', array($this, 'redirect_post_location'));
		add_filter('post_updated_messages', array($this, 'flash_message'));
	}

	/**
	 * Filter on redirect_post_location
	 *
	 * @return void
	 * @author 
	 */
	function redirect_post_location ($location) {
		remove_filter('redirect_post_location', __FUNCTION__, $this->current_message);
		$location = add_query_arg('message', $this->current_message, $location);
		return $location;
	}

	/**
	 * Show the flash message you just created.
	 *
	 * @return void
	 * @author Alex Andrews
	 **/
	function flash_message ($messages) {
		array_merge($messages, $this->messages);
	}
}