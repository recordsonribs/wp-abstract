<?php
namespace RecordsOnRibs\WordPressAbstract;

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
class Flash {
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
	function __construct( $message = false, $type = 'notice' ) {
		if ( $message ) {
			$this->flash( $message, $type );
		}

		$this->sticky_messages = get_transient( 'wordpress_flash_sticky_messages' );

		if ( ! $this->sticky_messages ) {
			$this->sticky_messages = array();
		}

		$this->user_id = get_current_user_id();

		$this->user_suppress = get_user_meta( $this->user_id, 'wordpress_flash_suppressed_messages', true );

		if ( ! $this->user_suppress ) {
			$this->user_suppress = array( 'sticky_messages' => array(), 'messages' => array() );
			$this->save_suppressed_messages();
		}

		add_action( 'admin_notices', array( &$this, 'render_messages' ) );
		add_action( 'admin_init', array( &$this, 'admin_init' ) );
	}

	/**
	 * Filter on admin_init action that allows messages to be suppressed.
	 *
	 * @author Alex Andrews
	 * @version 1.0
	 * @since 1.0
	 **/
	function admin_init() {
		if ( isset( $_GET['wpf_suppress_sticky'] ) ) {
			$this->suppress_sticky_message( $_GET['wpf_suppress_sticky'] );
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
	function flash( $message, $type = 'notice' ) {
		$add = array( 'message' => $message, 'type' => $type );

		array_push( $this->messages, $add );

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
	function notice( $message ) {
		$this->flash( $message, 'notice' );
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
	function error( $message ) {
		$this->flash( $message, 'error' );
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
	function sticky( $message, $type = 'notice' ) {
		$add = array( 'message' => $message, 'type' => $type );

		// Check to see if we already have this sticky
		// If we do, then don't add it again.
		if ( count( $this->sticky_messages ) != 0 ) {
			foreach ( $this->sticky_messages as $message ) {
				if ( $message['message'] == $add['message'] )
					return $add;
			}
		}

		array_push( $this->sticky_messages, $add );
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
	function save_sticky_messages() {
		// Save for a year.
		set_transient( 'wordpress_flash_sticky_messages', $this->sticky_messages, 31536000 );
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
	function clear_sticky_messages() {
		$this->sticky_messages = array();
		delete_transient( 'wordpress_flash_sticky_messages' );
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
	function clear_sticky_message( $message ) {
		$found = false;

		foreach ( $this->sticky_messages as $pos => $value ) {
			if ( $value['message'] == $message ) {
				unset ($this->sticky_messages[$pos]);
				$found = true;
				break;
			}
		}

		if ( $found )
			$this->save_sticky_messages();

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
	function clear_sticky_error( $message ) {
		return $this->clear_sticky_message( $message );
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
	function clear_sticky_notice( $message ) {
		return $this->clear_sticky_message( $message );
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
	function sticky_error( $message ) {
		$this->sticky( $message, 'error' );
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
	function sticky_notice( $message ) {
		$this->sticky( $message, 'notice' );
	}

	/**
	 * Action to render the messages we have queued up, both as sticky and runtime messages.
	 *
	 * @package WordPress Flash
	 * @author Alex Andrews
	 * @version 1.0
	 * @since 1.0
	 **/
	function render_messages(){
		if ( (count( $this->sticky_messages ) == 0) && ( count( $this->messages ) == 0 ) ) {
			return;
		}

		foreach ( $this->sticky_messages as $pos => $message ) {
			if ( ! $this->user_suppress['sticky_messages'][$pos] ) {
				$this->render_message( $message['message'], $message['type'], $pos, true );
			}
		}

		foreach ( $this->messages as $pos => $message ) {
			if ( ! $this->user_suppress['messages'][$pos] ) {
				$this->render_message( $message['message'], $message['type'], $pos );
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
	function suppress_sticky_message( $id ) {
		$this->user_suppress['sticky_messages'][$id] = array( 'message' => $this->sticky_messages[$id]['message'], 'type' => $this->sticky_messages[$id]['type'] );
		$this->save_suppressed_messages();
		$this->notice( 'Gone forever!' );
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
	function clear_suppressed_messages() {
		$this->user_suppress = array( 'sticky_messages' => array(), 'messages' => array() );
		$this->save_suppressed_messages();
	}

	/**
	 * Save messages we are suppressing to the user's metadata.
	 *
	 * @author Alex Andrews
	 * @version 1.0
	 * @since 1.0
	 **/
	function save_suppressed_messages() {
		update_user_meta( $this->user_id, 'wordpress_flash_suppressed_messages', $this->user_suppress );
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
	function render_message( $message, $type = 'notice', $id, $sticky = false ) {
		if ( $type == 'error' ) {
			echo '<div id="message" class="error">';
		} else {
			echo '<div id="message" class="updated fade">';
		}

		echo '<p>' . esc_html( $message ) . '</p>';

		if ( $sticky ) {
			echo '<p><a href="' . esc_url( '?wpf_suppress_sticky=$id' ) . '">Don\'t show me this message again.</a></p>';
		}

		echo '</div>';
	}
}