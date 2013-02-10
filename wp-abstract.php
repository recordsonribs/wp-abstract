<?php
/*
Plugin Name: WordPress Abstract
Plugin URI: http:/recordsonribs.com/ribcage
Description: A set of useful OO abstractions for common WordPress tasks.
Version: 0.2
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

if (!class_exists('WPAbstractPostType')) {

	class WPAbstractPostType
	{
		public $name = '';
		public $single = '';
		public $plural = '';

		// Overwrite the defaults for the custom post type.
		private $overwrite = array();

		// Set to the parent custom post type - for ease of testing custom post types
		public $parent = false;

		function __construct ($name, $single = false, $plural = false, $overwrite = false, $parent = false)
		{
			// Fake named parameters - PHP Y U NO RUBY?
			if (is_array($name)) extract($name, EXTR_IF_EXISTS);

			if (substr($name, -1) == 's') {
				$this->name = rtrim($name, 's');
			} else {
				$this->name = $name;
			}

			if ($single) {
				$this->single = ucfirst(strtolower($single));
			} else {
				$this->single = ucfirst(strtolower($this->name));
			}

			if ($plural) {
				$this->plural = $plural;
			} else {
				$this->plural = $this->single . 's';
			}

			if ($overwrite) {
				$this->overwrite = $overwrite;
			}

			// Make sure that we over-write the 'hiearchical' variable for custom post types with parents.
			if ($parent) {
				$overwrite['hierarchical'] = true;
			}

			add_action('init', array($this, 'init'));

			// Overwrite the 'Enter title here' for the post type
			if ($this->overwrite['title_prompt']) {
				add_filter('enter_title_here', array($this, 'enter_title_here'));
			}

			// Overwrite the little instruction underneith the Featured Image metabox
			if ($this->overwrite['featured_image_instruction']) {
				add_filter('admin_post_thumbnail_html', array($this, 'admin_post_thumbnail_html'));
			}

			// Overwrite metabox title text!
			if ($this->overwrite['meta_box_titles']) {
				add_action('add_meta_boxes', array($this, 'add_meta_boxes'), 10, 2);
			}
		}

		function init ()
		{
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

			$args = array_merge($args, $this->overwrite);

			register_post_type ($this->name, $args);

			add_filter('post_updated_messages', array($this, 'post_updated_messages'));
		}

		function post_updated_messages($messages)
		{
			global $post;

			$this->messages = array(
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

			$messages[$this->name] = $this->messages;

			return $messages;
		}

		function metaboxes ()
		{
			return;
		}

		function create_labels ()
		{
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

		function enter_title_here ($content)
		{
			global $post;

			if ($post->post_type != $this->name) {
				return $content;
			}

			return $this->overwrite['enter_title_here'];
		}

		function admin_post_thumbnail_html ($content)
		{
			global $post;

			if ($post->post_type != $this->name) {
				return $content;
			}

			return $content .= '<p>' . $this->overwrite['featured_image_instruction'] . '</p>';
		}

		function add_meta_boxes ($post_type, $post)
		{
			global $wp_meta_boxes;

			// Lets smoosh through these and make changes as we see fit!
			foreach (array('side', 'normal') as $column) {
				foreach (array('core', 'low') as $placing) {
					foreach ($wp_meta_boxes[$this->name][$column][$placing] as $meta_box_name => $meta_box) {
						foreach ($this->overwrite['meta_box_titles'] as $overwrite => $with) {
							if ($meta_box['title'] == $overwrite) {
								$wp_meta_boxes[$this->name][$column][$placing][$meta_box_name]['title'] = $with;
							}
						}
					}
				}
			}
		}

	}

}

/**
 * WordPress Flash
 *
 * An object oriented wrapping of the WordPress notice functions to show admin messages.
 *
 * Inspired by Laravel.
 *
 * @package WordPress Flash
 * @author Alex Andrews
 * @version 1.0
 **/

if (! class_exists('WPAbstractFlash')) {
	/**
	 * A nice little system of displaying error and status messages in WordPress.
	 *
	 * @package WordPress Flash
	 * @author Alex Andrews
	 * @version 1.0
	 * @todo Show certain messages to certain users, or certain levels of ability.
	 **/
	class WPAbstractFlash
	{
		public $messages = array();
		public $sticky_messages = null;

		public $user_id = null;
		public $user_suppress = null;

		/**
		 * Constructor.
		 *
		 * @param string $message Message to put into the queue as first.
		 * @param string $type Type of this first message.
		 * @package Wordpress Flash
		 * @author Alex Andrews
		 * @version 1.0
		 * @since 1.0
		 **/
		function __construct ($message = false, $type = 'notice')
		{
			if ($message) {
				$this->flash($message, $type);
			}

			$this->sticky_messages = get_transient('wordpress_flash_sticky_messages');

			if (! $this->sticky_messages) {
				$this->sticky_messages = array();
			}

			$this->user_id = get_current_user_id();

			$this->user_suppress = get_user_meta($this->user_id, 'wordpress_flash_suppressed_messages', true);

			if (! $this->user_suppress) {
				$this->user_suppress = array('sticky_messages' => array(), 'messages' => array());
				$this->save_suppressed_messages();
			}

			add_action('admin_notices', array(&$this, 'render_messages'));
			add_action('admin_init', array(&$this, 'admin_init'));
		}

		/**
		 * Filter on admin_init action that allows messages to be suppressed.
		 *
		 * @author Alex Andrews
		 * @version 1.0
		 * @since 1.0
		 **/
		function admin_init ()
		{
			if (isset($_GET['wpf_suppress_sticky'])) {
				$this->suppress_sticky_message($_GET['wpf_suppress_sticky']);
			}
		}

		/**
		 * Adds a message to the queue.
		 *
		 * This message will only be displayed when this code is run and if it again reaches the same error.
		 *
		 * @param string $message Message to show.
		 * @param string $type Type of message.
		 * @package Wordpress Flash
		 * @author Alex Andrews
		 * @version 1.0
		 * @since 1.0
		 **/
		function flash ($message, $type = 'notice')
		{
			$add = array('message' => $message, 'type' => $type);

			array_push($this->messages, $add);

			return $add;
		}

		/**
		 * Adds a message of the type 'notice' to the queue.
		 *
		 * This type of message are surrounded by a yellow box.
		 *
		 * This message will only be displayed when this code is run and if it again reaches the same error.
		 *
		 * @param string $message Message to put into the queue as first.
		 * @package Wordpress Flash
		 * @author Alex Andrews
		 * @version 1.0
		 * @since 1.0
		 **/
		function notice ($message)
		{
			$this->flash($message, 'notice');
		}

		/**
		 * Adds a message of the type 'error' to the queue.
		 *
		 * This type of message are surrounded by a large red box.
		 *
		 * This message will only be displayed when this code is run and if it again reaches the same error.
		 *
		 * @param string $message Message to put into the queue as first.
		 * @package Wordpress Flash
		 * @author Alex Andrews
		 * @version 1.0
		 * @since 1.0
		 **/
		function error ($message)
		{
			$this->flash($message, 'error');
		}

		/**
		 * Adds a sticky message to the sticky message queue.
		 *
		 * These message persist. They continue to appear on WordPress backend until they are turned off even if the code has not reached
		 * the same point.
		 *
		 * @param string $message Message to put into the queue as first.
		 * @param string $type Type of of message, default notice.
		 * @package Wordpress Flash
		 * @author Alex Andrews
		 * @version 1.0
		 * @since 1.0
		 **/
		function sticky ($message, $type = 'notice')
		{
			$add = array('message' => $message, 'type' => $type);

			// Check to see if we already have this sticky
			// If we do, then don't add it again.
			if (count($this->sticky_messages) != 0) {
				foreach ($this->sticky_messages as $message) {
				if ($message['message'] == $add['message']) {
					return $add;
				}
			}
			}

			array_push($this->sticky_messages, $add);
			$this->save_sticky_messages();

			return $add;
		}

		/**
		 * Saves the sticky messages to a transient.
		 *
		 * @package WordPress Flash
		 * @author Alex Andrews
		 * @version 1.0
		 * @since 1.0
		 **/
		function save_sticky_messages ()
		{
			// Save for a year.
			set_transient('wordpress_flash_sticky_messages', $this->sticky_messages, 31536000);
		}

		/**
		 * Clear all stick messages and empty the sticky message cache.
		 *
		 * @return void
		 * @package WordPress Flash
		 * @author Alex Andrews
		 * @version 1.0
		 * @since 1.0
		 **/
		function clear_sticky_messages ()
		{
			$this->sticky_messages = array();
			delete_transient('wordpress_flash_sticky_messages');
		}

		/**
		 * Clear a particular sticky message.
		 *
		 * @param string $message The message to remove from the queue.
		 * @return bool $found True if we found and removed the stick message from the queue.
		 * @package WordPress Flash
		 * @author Alex Andrews
		 * @version 1.0
		 * @since 1.0
		 **/
		 function clear_sticky_message ($message)
		 {
			 $found = false;

			 foreach ($this->sticky_messages as $pos => $value) {
				 if ($value['message'] == $message) {
					 unset ($this->sticky_messages[$pos]);
					 $found = true;
					 break;
				 }
			 }

			 if ($found) {
				 $this->save_sticky_messages();
			 }

			 return $found;
		 }

		/**
		  * Convenience wrapper function for clearing a sticky error.
		  *
		  * @param string $message Message to remove from queue.
		  * @return bool $found Return what clear_sticky_message returns.
		  * @package WordPress Flash
		  * @author Alex Andrews
		  * @version 1.0
		  * @since 1.0
		  **/
		 function clear_sticky_error ($message)
		 {
			 return $this->clear_sticky_message($message);
		 }


		/**
		  * Convenience wrapper function for clearing a sticky notice.
		  *
		  * @param string $message Message to remove from queue.
		  * @return bool $found Return what clear_sticky_message returns.
		  * @package WordPress Flash
		  * @author Alex Andrews
		  * @version 1.0
		  * @since 1.0
		  **/
		 function clear_sticky_notice ($message)
		 {
			 return $this->clear_sticky_message($message);
		 }

		/**
		 * Adds a sticky message of the type 'error' to the queue.
		 *
		 * This type of message are surrounded by a yellow box.
		 *
		 * This message will only be displayed persistantly.
		 *
		 * @param string $message Message to put into the queue as first.
		 * @package Wordpress Flash
		 * @author Alex Andrews
		 * @version 1.0
		 * @since 1.0
		 **/
		function sticky_error ($message)
		{
			$this->sticky($message, 'error');
		}

		/**
		 * Adds a sticky message of the type 'notice' to the queue.
		 *
		 * This type of message are surrounded by a yellow box.
		 *
		 * This message will only be displayed persistantly.
		 *
		 * @param string $message Message to put into the queue as first.
		 * @package Wordpress Flash
		 * @author Alex Andrews
		 * @version 1.0
		 * @since 1.0
		 **/
		function sticky_notice ($message)
		{
			$this->sticky($message, 'notice');
		}

		/**
		 * Action to render the messages we have queued up, both as sticky and runtime messages.
		 *
		 * @package WordPress Flash
		 * @author Alex Andrews
		 * @version 1.0
		 * @since 1.0
		 **/
		function render_messages ()
		{
			if ((count($this->sticky_messages) == 0) && (count($this->messages) == 0)) {
				return;
			}

			foreach ($this->sticky_messages as $pos => $message) {
				if (! $this->user_suppress['sticky_messages'][$pos]) {
					$this->render_message($message['message'], $message['type'], $pos, true);
				}
			}

			foreach ($this->messages as $pos => $message) {
				if (! $this->user_suppress['messages'][$pos]) {
					$this->render_message($message['message'], $message['type'], $pos);
				}
			}
		}

		/**
		 * Suppress a sticky message.
		 *
		 * @param int $id ID (in reality location in array) of message to suppress.
		 * @return bool $found True if we have found and suppressed a message.
		 * @version 1.0
		 * @since 1.0
		 **/
		function suppress_sticky_message ($id)
		{
			$this->user_suppress['sticky_messages'][$id] = array('message' => $this->sticky_messages[$id]['message'], 'type' => $this->sticky_messages[$id]['type']);
			$this->save_suppressed_messages();
			$this->notice('Gone forever!');
		}

		/**
		 * Reset all suppressed messages.
		 *
		 * Then you will start seeing all those lovely messages again.
		 *
		 * @author Alex Andews
		 * @version 1.0
		 * @since 1.0
		 **/
		function clear_suppressed_messages ()
		{
			$this->user_suppress = array('sticky_messages' => array(), 'messages' => array());
			$this->save_suppressed_messages();
		}

		/**
		 * Save messages we are suppressing to the user's metadata.
		 *
		 * @author Alex Andrews
		 * @version 1.0
		 * @since 1.0
		 **/
		function save_suppressed_messages ()
		{
			update_user_meta($this->user_id, 'wordpress_flash_suppressed_messages', $this->user_suppress);
		}

		/**
		 * Render a single message in the admin section.
		 *
		 * @param string $message The message to show.
		 * @param bool $error Message is error, not simply a notice.
		 * @param int $id The location of the error in the errors array.
		 * @author Alex Andrews
		 * @version 1.0
		 * @since 1.0
		 **/
		function render_message ($message, $type = 'notice', $id, $sticky = false)
		{
			  if ($type == 'error') {
				echo '<div id="message" class="error">';
			  } else {
				echo '<div id="message" class="updated fade">';
			  }

			  echo "<p>$message</p>";

			  if ($sticky) {
				  echo "<p><a href='?wpf_suppress_sticky=$id'>Don't show me this message again.</a></p>";
			  }

			  echo '</div>';
		}
	}
}
