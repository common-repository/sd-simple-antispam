<?php
/*                                                                                                                                                                                                                                                             
Plugin Name: SD Simple Antispam
Plugin URI: http://it.sverigedemokraterna.se
Description: Prevents comment spam.
Version: 1.1
Author: Sverigedemokraterna IT
Author URI: http://it.sverigedemokraterna.se
Author Email: it@sverigedemokraterna.se
License: GPLv3
*/

if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); }

/**
	SD Simple Antispam
	
	@brief		Prevents comment spam.
	@author		Edward Plainview	edward.plainview@sverigedemokraterna.se
**/
require_once( 'SD_Simple_Antispam_Base.php' );
class SD_Simple_Antispam
	extends SD_Simple_Antispam_Base
{
	protected $site_options = array(
		'empty_url' => false,
		'extra_fields' => false,
	);
	public function __construct()
	{
		parent::__construct( __FILE__ );

		add_action( 'admin_menu',									array( $this, 'admin_menu') );
		
		add_action( 'comment_form',								array( &$this, 'comment_form' ) );
		add_filter( 'pre_comment_approved', 					array( &$this, 'pre_comment_approved' ), 10, 2 );
		add_filter( 'threewp_activity_monitor_list_activities', array( &$this, 'list_activities'), 10, 2 );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Admin
	// --------------------------------------------------------------------------------------------
	
	public function admin_menu( $menus )
	{
		$this->load_language();

		$display = false;

		if ( $this->is_network )
			$display = is_super_admin();
		else
			$display = $this->role_at_least( 'administrator' );

		if ( $display )
			add_options_page(
				$this->_('SD Simple Antispam'),
				$this->_('SD Simple Antispam'),
				'manage_options',
				'sd_simple_antispam',
				array( &$this, 'admin' ),
				null
			);
	}

	public function admin()
	{
		$tab_data = array(
			'tabs'		=>	array(),
			'functions' =>	array(),
		);
				
		$tab_data['default'] = 'settings';

		$tab_data['tabs']['settings'] = $this->_( 'Settings' );
		$tab_data['functions']['settings'] = 'admin_settings';

		$tab_data['tabs']['uninstall'] = $this->_( 'Uninstall' );
		$tab_data['functions']['uninstall'] = 'admin_uninstall';

		$this->tabs($tab_data);
	}
	
	/**
		@brief	Settings.
	**/
	public function admin_settings()
	{
		if ( isset( $_POST['update'] ) )
		{
			foreach( array( 'empty_url', 'extra_fields' ) as $key )
				$this->update_site_option( $key, isset( $_POST[ $key ] ) );
			$this->message( $this->_( "The settings have been saved! If you use a caching plugin, clear the cache now." ) );
		}
		$form = $this->form();
		$inputs = array(
			'empty_url' => array(
				'name' => 'empty_url',
				'type' => 'checkbox',
				'label' => $this->_( 'No author website' ),
				'description' => sprintf(
					$this->_( 'Check that the user left the website address empty. Use CSS in your theme to hide the paragraph class %s.' ),
					'<em>comment-form-url</em>'
				),
				'checked' => $this->get_site_option( 'empty_url' ),
			),
			'extra_fields' => array(
				'name' => 'extra_fields',
				'type' => 'checkbox',
				'label' => $this->_( 'Extra fields' ),
				'description' => $this->_( 'Some invisible fields are placed in the comment box that are suppose to be left empty. Some spambots will automatically fill in these fields with text.' ),
				'checked' => $this->get_site_option( 'extra_fields' ),
			),
			'update' => array(
				'name' => 'update',
				'type' => 'submit',
				'value' => $this->_( 'Update settings' ),
				'css_class' => 'button-primary',
			),
		);
		$returnValue = '
			' . $form->start() . '
			<p>
				' . $this->_( 'The following techniques are available to help block spam.' ) . '
			</p>
			
			<h3>' . $this->_( 'Extra fields' ) . ' (' . $this->_( 'recommended' ) . ')</h3>
			
			<p>
				' . $this->_( 'Extra, invisible fields are inserted into the comment form. Bots will either omit these fields or fill them in, which will result in rejection of the comment.' ) . '
			</p>

			<p>
				' . $form->make_input( $inputs[ 'extra_fields' ] ) . '
				' . $form->make_label( $inputs[ 'extra_fields' ] ) . '
			</p>

			<h3>' . $this->_( 'No author website' ) . '</h3>
			
			<p>
				' . $this->_( "The author website must be left empty for the comment to pass. If you use this setting, don't forget to hide the author url field in your theme's CSS file." ) . '
			</p>

			<p>
				' . $form->make_input( $inputs[ 'empty_url' ] ) . '
				' . $form->make_label( $inputs[ 'empty_url' ] ) . '
			</p>

			<h3>' . $this->_( 'Logging' ) . '</h3>
			
			<p>
				' . sprintf(
						$this->_( 'This plugin uses %sThreeWP Activity Monitor%s to log activity.' ),
						'<a href="http://wordpress.org/extend/plugins/threewp-activity-monitor/">',
						'</a>'
					) . '
			</p>

			<p>
				' . $form->make_input( $inputs['update'] ) . '
			</p>
			' . $form->stop() . '
		';
		echo $returnValue;
	}
	
	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Actions
	// --------------------------------------------------------------------------------------------
	
	public function comment_form()
	{
		// Users may post anytime!
		global $current_user;
		wp_get_current_user();
		if ( $current_user->ID > 0 )
		    return;
		
		if ( $this->get_site_option( 'extra_fields' ) )
		{
			$form = $this->form();
			
			// Fields are named email- and comment- to try to fool the spambots.
			$inputs = array(
				'email' => array(
					'type' => 'text',
					'name' => 'email-' . substr( md5( rand(0, PHP_INT_MAX) ), 0, 4 ),
					'label' => $this->_( 'Do not write anything here' ),
					'size' => 50,
					'validation' => array( 'empty' => true ),
				),
				'comment' => array(
					'type' => 'text',
					'name' => 'comment-' . substr( md5( rand(0, PHP_INT_MAX) ), 0, 4 ),
					'label' => $this->_( 'Do not write anything here either' ),
					'size' => 50,
					'validation' => array( 'empty' => true ),
				),
			);
			
			echo '
				<div style="display: none;">
					' . $form->make_label( $inputs['email'] ) . '
					' . $form->make_input( $inputs['email'] ) . '
					' . $form->make_label( $inputs['comment'] ) . '
					' . $form->make_input( $inputs['comment'] ) . '
				</div>
			';
		}
	}
	
	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Filters
	// --------------------------------------------------------------------------------------------
	
	public function list_activities( $activities )
	{
		$this->load_language();
		
		// First, fill in our own activities.
		$activities['sd_simple_antispam_spam'] = array(
			'name' => $this->_('Spam found'),
			'description' => $this->_('Shows a summary of the comment marked as spam.'),
			'plugin' => 'SD Simple Antispam',
		);
		
		return $activities;
	}
	
	public function pre_comment_approved( $approved, $commentdata )
	{
		// If someone else has already marked it as spam, then why do anything?
		if ( $approved === 'spam' )
			return $approved;
		
		// Users may post anytime!
		global $current_user;
		wp_get_current_user();
		if ( $current_user->ID > 0 )
		    return $approved;
		
		// Pingbacks from ourselves don't have POSTs.
		if ( count( $_POST ) < 1 )
			return $approved;
		
		$this->load_language();

		$activity_monitor_action = array(
			'activity_id' => 'sd_simple_antispam_spam',
			'activity_strings' => array(
				'' => $this->_( "Stopped spam on %blog_name_with_panel_link%" )
			),
		);

		$spam = false;

		// Extra fields are extra, CSS invisible fields that are supposed to be left empty.
		if ( $this->get_site_option( 'extra_fields' ) )
		{
			$found = false;
			// Find the extra fields
			foreach( array( 'email', 'comment' ) as $key )
			{
				foreach( $_POST as $post_key => $post_value )
				{
					// XXXX-1234
					if ( strlen($post_key) != strlen($key) + 5 )
						continue;
					// Post key must start with the key
					if ( substr( $post_key, 0, strlen($key)+1 ) != $key . '-' )
						continue;

					// This is email-xxxx or comment-xxxx
					$found = true;
					// And it must be empty
					if ( $post_value !== '' )
					{
						$spam = true;
						$activity_monitor_action[ 'activity_strings' ][ ' ' ]  = $this->_( 'The extra fields were not empty.' ); 
					}
				}
			}
			if ( !$found )
			{
				$spam = true;
				$activity_monitor_action[ 'activity_strings' ][ ' ' ]  = $this->_( 'The extra fields did not exist.' ); 
			} 
		}

		// Empty URL
		if ( $this->get_site_option( 'empty_url' ) )
		{
			if ( $commentdata['comment_author_url'] != '' )
			{
				$spam = true;
				$activity_monitor_action[ 'activity_strings' ][ '   ' ]  = $this->_( 'Author URL was not left empty.' );
			} 
		}

		if ( $spam )
		{
			$spam_author = htmlspecialchars( $commentdata['comment_author'] );
			$spam_author_url = htmlspecialchars( $commentdata['comment_author_url'] );
			$spam_text = stripslashes( $commentdata['comment_content'] );
			$spam_text = htmlspecialchars( $spam_text );
			if ( strlen( $spam_text ) > 128 )
				$spam_text = substr( $spam_text, 0, 128 ) . '...';
			
			$activity_monitor_action['activity_strings'][ $this->_( 'Author' ) ] = $spam_author;
			$activity_monitor_action['activity_strings'][ $this->_( 'Author URL' ) ] = $spam_author_url;
			$activity_monitor_action['activity_strings'][ $this->_( 'Spam' ) ] = $spam_text;

			do_action('threewp_activity_monitor_new_activity', $activity_monitor_action ); 
		}

		return $spam ? 'spam' : $approved;
	} 

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Misc
	// --------------------------------------------------------------------------------------------
	
}
$SD_Simple_Antispam = new SD_Simple_Antispam();
