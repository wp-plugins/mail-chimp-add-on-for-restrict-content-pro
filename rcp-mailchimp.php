<?php
/*
Plugin Name: Restrict Content Pro - MailChimp
Plugin URL: http://pippinsplugins.com/restrict-content-pro-mailchimp/
Description: Include a MailChimp signup option with your Restrict Content Pro registration form
Version: 1.2
Author: Pippin Williamson
Author URI: http://pippinsplugins.com
Contributors: Pippin Williamson
Text Domain: restrict-content-pro-mailchimp
*/

function rcp_mailchimp_settings_menu() {
	// add settings page

	add_submenu_page( 'rcp-members', __( 'Restrict Content Pro MailChimp Settings', 'restrict-content-pro-mailchimp' ), __( 'MailChimp', 'restrict-content-pro-mailchimp' ), 'manage_options', 'rcp-mailchimp', 'rcp_mailchimp_settings_page' );
}
add_action( 'admin_menu', 'rcp_mailchimp_settings_menu', 100 );

// register the plugin settings
function rcp_mailchimp_register_settings() {

	// create whitelist of options
	register_setting( 'rcp_mailchimp_settings_group', 'rcp_mailchimp_settings' );
}
//call register settings function
add_action( 'admin_init', 'rcp_mailchimp_register_settings', 100 );

function rcp_mailchimp_settings_page() {
	
	$rcp_mc_options = get_option( 'rcp_mailchimp_settings' );
	$saved_list     = isset( $rcp_mc_options['mailchimp_list'] ) ? $rcp_mc_options['mailchimp_list'] : false;
		
	?>
	<div class="wrap">
		<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
		<?php
		if ( ! isset( $_REQUEST['updated'] ) )
			$_REQUEST['updated'] = false;
		?>
		<?php if ( false !== $_REQUEST['updated'] ) : ?>
		<div class="updated fade"><p><strong><?php _e( 'Options saved', 'restrict-content-pro-mailchimp' ); ?></strong></p></div>
		<?php endif; ?>
		<form method="post" action="options.php" class="rcp_options_form">

			<?php settings_fields( 'rcp_mailchimp_settings_group' ); ?>
			<?php $lists = rcp_get_mailchimp_lists(); ?>
				
			<table class="form-table">

				<tr>
					<th>
						<label for="rcp_mailchimp_settings[mailchimp_api]"><?php _e( 'MailChimp API Key', 'restrict-content-pro-mailchimp' ); ?></label>
					</th>
					<td>
						<input class="regular-text" type="text" id="rcp_mailchimp_settings[mailchimp_api]" name="rcp_mailchimp_settings[mailchimp_api]" value="<?php if ( isset( $rcp_mc_options['mailchimp_api'] ) ) { echo $rcp_mc_options['mailchimp_api']; } ?>"/>
						<div class="description"><?php _e( 'Enter your MailChimp API key to enable a newsletter signup option with the registration form.', 'restrict-content-pro-mailchimp' ); ?></div>
					</td>
				</tr>
				<tr>
					<th>
						<label for="rcp_mailchimp_settings[mailchimp_list]"><?php _e( 'Newsletter List', 'restrict-content-pro-mailchimp' ); ?></label>
					</th>
					<td>
						<select id="rcp_mailchimp_settings[mailchimp_list]" name="rcp_mailchimp_settings[mailchimp_list]">
							<?php
								if ( $lists ) :
									foreach ( $lists as $list ) :
										echo '<option value="' . esc_attr( $list['id'] ) . '"' . selected( $saved_list, $list['id'], false ) . '>' . esc_html( $list['name'] ) . '</option>';
									endforeach;
								else :
							?>
							<option value="no list"><?php _e( 'no lists', 'restrict-content-pro-mailchimp' ); ?></option>
						<?php endif; ?>
						</select>
						<div class="description"><?php _e( 'Choose the list to subscribe users to', 'restrict-content-pro-mailchimp' ); ?></div>
					</td>
				</tr>
				<tr>
					<th>
						<label for="rcp_mailchimp_settings[signup_label]"><?php _e( 'Form Label', 'restrict-content-pro-mailchimp' ); ?></label>
					</th>
					<td>
						<input class="regular-text" type="text" id="rcp_mailchimp_settings[signup_label]" name="rcp_mailchimp_settings[signup_label]" value="<?php if ( isset( $rcp_mc_options['signup_label'] ) ) { echo $rcp_mc_options['signup_label']; } ?>"/>
						<div class="description"><?php _e( 'Enter the label to be shown on the "Signup for Newsletter" checkbox', 'restrict-content-pro-mailchimp' ); ?></div>
					</td>
				</tr>
			</table>
			<!-- save the options -->
			<p class="submit">
				<input type="submit" class="button-primary" value="<?php _e( 'Save Options', 'restrict-content-pro-mailchimp' ); ?>" />
			</p>
			
		</form>
	</div><!--end .wrap-->
	<?php
}

function rcp_mailchimp_admin_styles() {
	wp_enqueue_style( 'rcp-admin', RCP_PLUGIN_DIR . 'includes/css/admin-styles.css' );
}
if ( isset( $_GET['page'] ) && ( $_GET['page'] == 'rcp-mailchimp' ) ) {
	add_action('admin_enqueue_scripts', 'rcp_mailchimp_admin_styles');
}

// get an array of all MailChimp subscription lists
function rcp_get_mailchimp_lists() {
	
	$rcp_mc_options = get_option( 'rcp_mailchimp_settings' );
	
	if ( ! empty( $rcp_mc_options['mailchimp_api'] ) ) {
		
		$api_key = trim( $rcp_mc_options['mailchimp_api'] );

		$lists = array();
		if ( ! class_exists( 'MCAPI' ) )
			require_once( 'mailchimp/MCAPI.class.php' );
		$api = new MCAPI( $api_key );
		$list_data = $api->lists();
		if ( $list_data ) {
			foreach ( $list_data['data'] as $key => $list ) {
				$lists[ $key ]['id']   = $list['id'];
				$lists[ $key ]['name'] = $list['name'];
			}
		}
		return $lists;
	}
	return false;
}

// adds an email to the MailChimp subscription list
function rcp_subscribe_email( $email = '' ) {

	$rcp_mc_options = get_option( 'rcp_mailchimp_settings' );
	
	if ( ! empty( $rcp_mc_options['mailchimp_api'] ) ) {

		$api_key = trim( $rcp_mc_options['mailchimp_api'] );

		if ( ! class_exists( 'MCAPI' ) )
			require_once( 'mailchimp/MCAPI.class.php' );
		$api = new MCAPI( $api_key );
		
		$merge_vars = array(
			'FNAME' => isset( $_POST['rcp_user_first'] ) ? sanitize_text_field( $_POST['rcp_user_first'] ) : '',
			'LNAME' => isset( $_POST['rcp_user_last'] )  ? sanitize_text_field( $_POST['rcp_user_last'] )  : ''
		);

		if ( $api->listSubscribe( $rcp_mc_options['mailchimp_list'], $email, $merge_vars ) === true ) {
			return true;
		}
	}

	return false;
}

// displays the mailchimp checkbox
function rcp_mailchimp_fields() {
	$rcp_mc_options = get_option('rcp_mailchimp_settings');
	ob_start(); 
		if ( ! empty( $rcp_mc_options['mailchimp_api'] ) ) { ?>
		<p>
			<input name="rcp_mailchimp_signup" id="rcp_mailchimp_signup" type="checkbox" checked="checked"/>
			<label for="rcp_mailchimp_signup"><?php echo isset( $rcp_mc_options['signup_label'] ) ? $rcp_mc_options['signup_label'] : __( 'Signup for our newsletter', 'restrict-content-pro-mailchimp' ); ?></label>
		</p>
		<?php
	}
	echo ob_get_clean();
}
add_action( 'rcp_before_registration_submit_field', 'rcp_mailchimp_fields', 100 );

// checks whether a user should be signed up for he MailChimp list
function rcp_check_for_email_signup( $posted, $user_id ) {
	if ( isset( $posted['rcp_mailchimp_signup'] ) ) {
		if ( is_user_logged_in() ) {
			$user_data 	= get_userdata( $user_id );
			$email 		= $user_data->user_email;
		} else {
			$email = $posted['rcp_user_email'];
		}
		rcp_subscribe_email( $email );
		update_user_meta( $user_id, 'rcp_subscribed_to_mailchimp', 'yes' );
	}
}
add_action( 'rcp_form_processing', 'rcp_check_for_email_signup', 10, 2 );

function rcp_add_mc_signup_notice($user_id) {
	$signed_up = get_user_meta( $user_id, 'rcp_subscribed_to_mailchimp', true );
	
	if( $signed_up )
		$signed_up = __('yes', 'rcp' );
	else
		$signed_up = __('no', 'rcp' );
	
	echo '<tr><td>MailChimp: ' . $signed_up . '</tr></td>';
}
add_action('rcp_view_member_after', 'rcp_add_mc_signup_notice');