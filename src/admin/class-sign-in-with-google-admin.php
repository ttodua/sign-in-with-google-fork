<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @since      1.0.0
 *
 * @package    Sign_In_With_Google
 * @subpackage Sign_In_With_Google/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Sign_In_With_Google
 * @subpackage Sign_In_With_Google/admin
 * @author     Tanner Record <tanner.record@gmail.com>
 */
class Sign_In_With_Google_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * The user's information.
	 *
	 * @since 1.2.0
	 * @access private
	 * @var string $user The user data.
	 */
	private $user;

	/**
	 * Holds the state to send with Google redirect. It will be
	 * json and url encoded before the redirect.
	 *
	 * @since 1.2.1
	 * @access private
	 * @var array $state
	 */
	private $state;

	/**
	 * GoogleAuth class
	 *
	 * @since 1.5.2
	 * @access private
	 * @var object
	 */
	private $google_auth;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string $plugin_name   The name of this plugin.
	 * @param      string $version       The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		$this->google_auth = new SIWG_GoogleAuth( get_option( 'siwg_google_client_id' ) );

	}

	/**
	 * Load assets for admin.
	 *
	 * @since 1.3.1
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/sign-in-with-google-admin.css', array(), $this->version, 'all' );
	}

	/**
	 * Add the plugin settings link found on the plugin page.
	 *
	 * @since    1.0.0
	 * @param array $links The links to add to the plugin page.
	 */
	public function add_action_links( $links ) {

		$mylinks = array(
			'<a href="' . admin_url( 'options-general.php?page=siwg_settings' ) . '">' . esc_html__( 'Settings', 'sign-in-with-google' ) . '</a>',
		);

		return array_merge( $links, $mylinks );

	}

	/**
	 * Add "Connect With Google" button to user profile settings.
	 *
	 * @since 1.3.1
	 */
	public function add_connect_button_to_profile() {

		$url            = site_url( '?google_redirect' );
		$linked_account = get_user_meta( get_current_user_id(), 'siwg_google_account', true );
		?>
		<h2><?php esc_html_e( 'Sign In With Google', 'sign-in-with-google' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Connect', 'sign-in-with-google' ); ?></th>
				<td>
				<?php if ( $linked_account ) : ?>
					<?php echo $linked_account; ?>
					<?php if ( current_user_can( 'manage_options' ) || get_option( 'siwg_show_unlink_in_profile' ) ) { ?>
						<form method="post">
							<input type="submit" role="button" value="<?php esc_html_e( 'Unlink Account', 'sign-in-with-google' ); ?>">
							<?php wp_nonce_field( 'siwg_unlink_account', '_siwg_account_nonce' ); ?>
						</form>
					<?php } ?>
				<?php else : ?>
					<a id="ConnectWithGoogleButton" href="<?php echo esc_attr( $url ); ?>"><?php esc_html_e( 'Connect to Google', 'sign-in-with-google' ); ?></a>
					<span class="description"><?php esc_html_e( 'Connect your user profile so you can sign in with Google', 'sign-in-with-google' ); ?></span>
				<?php endif; ?>
				</td>
			</tr>
		</table>
		<?php
	}

	public $google_logo_svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/><path d="M1 1h22v22H1z" fill="none"/></svg>';

	/**
	 * Initialize the settings menu.
	 *
	 * @since 1.0.0
	 */
	public function settings_menu_init() {
		$prefix_logo = '<span style="width:12px; height:12px; display: inline-block;">' . $this->google_logo_svg . '</span>';
		add_options_page(
			__( 'Sign in with Google', 'sign-in-with-google' ), // The text to be displayed for this actual menu item.
			__( $prefix_logo . ' Sign in with Google', 'sign-in-with-google' ), // The title to be displayed on this menu's corresponding page.
			'manage_options',                                   // Which capability can see this menu.
			'siwg_settings',                                    // The unique ID - that is, the slug - for this menu item.
			array( $this, 'settings_page_render' )              // The name of the function to call when rendering this menu's page.
		);

	}

	/**
	 * Register the admin settings section.
	 *
	 * @since    1.0.0
	 */
	public function settings_api_init() {

		add_settings_section(
			'siwg_section',
			'',
			array( $this, 'siwg_section' ),
			'siwg_settings'
		);

		add_settings_field(
			'siwg_google_client_id',
			__( 'Client ID', 'sign-in-with-google' ),
			array( $this, 'siwg_google_client_id' ),
			'siwg_settings',
			'siwg_section'
		);

		add_settings_field(
			'siwg_google_client_secret',
			__( 'Client Secret', 'sign-in-with-google' ),
			array( $this, 'siwg_google_client_secret' ),
			'siwg_settings',
			'siwg_section'
		);

		add_settings_field(
			'siwg_google_user_default_role',
			__( 'Default New User Role', 'sign-in-with-google' ),
			array( $this, 'siwg_google_user_default_role' ),
			'siwg_settings',
			'siwg_section'
		);

		add_settings_field(
			'siwg_use_google_profile_picture',
			__( 'Use google profile images for user', 'sign-in-with-google' ),
			array( $this, 'siwg_use_google_profile_picture' ),
			'siwg_settings',
			'siwg_section'
		);

		add_settings_field(
			'siwg_google_domain_restriction',
			__( 'Restrict To Domain', 'sign-in-with-google' ),
			array( $this, 'siwg_google_domain_restriction' ),
			'siwg_settings',
			'siwg_section'
		);

		add_settings_field( 			
			'sign_allow_forced_domains_on_disabled_registrations',
			__( 'Allow domain user registrations', 'sign-in-with-google' ),
			array( $this, 'sign_allow_forced_domains_on_disabled_registrations' ),
			'siwg_settings',
			'siwg_section'
		);

		add_settings_field( 			
			'siwg_allow_registration_even_if_disabled',
			__( 'Allow registrations even if globally disabled', 'sign-in-with-google' ),
			array( $this, 'siwg_allow_registration_even_if_disabled' ),
			'siwg_settings',
			'siwg_section'
		);

		add_settings_field(
			'siwg_save_google_userinfo',
			__( 'Save user info received from Google', 'sign-in-with-google' ),
			array( $this, 'siwg_save_google_userinfo' ),
			'siwg_settings',
			'siwg_section'
		);

		add_settings_field(
			'siwg_google_email_sanitization',
			__( 'Sanitize email addresses', 'sign-in-with-google' ),
			array( $this, 'siwg_google_email_sanitization' ),
			'siwg_settings',
			'siwg_section'
		);

		add_settings_field(
			'siwg_show_on_login',
			__( 'Show Google Signup Button on Login Form', 'sign-in-with-google' ),
			array( $this, 'siwg_show_on_login' ),
			'siwg_settings',
			'siwg_section'
		);
	
		add_settings_field(
			'siwg_disable_login_page',
			__( 'Disable WP login page and reditect users to Goolge Sign In', 'sign-in-with-google' ),
			array( $this, 'siwg_disable_login_page' ),
			'siwg_settings',
			'siwg_section'
		);

		add_settings_field(
			'siwg_allow_mail_change',
			__( 'Allow regular user to change own email', 'sign-in-with-google' ),
			array( $this, 'siwg_allow_mail_change' ),
			'siwg_settings',
			'siwg_section'
		);

		add_settings_field(
			'siwg_google_custom_redir_url',
			__( 'Custom redirect-back url for Google (you can use relative path or even full domain links, like <code>https://example.com/whatever</code>)', 'sign-in-with-google' ),
			array( $this, 'siwg_google_custom_redir_url' ),
			'siwg_settings',
			'siwg_section'
		);

		add_settings_field(
			'siwg_expose_class_instance',
			__( 'Expose plugin instance', 'sign-in-with-google' ),
			array( $this, 'siwg_expose_class_instance' ),
			'siwg_settings',
			'siwg_section'
		);

		register_setting( 'siwg_settings', 'siwg_google_client_id', array( $this, 'input_validation' ) );
		register_setting( 'siwg_settings', 'siwg_google_client_secret', array( $this, 'input_validation' ) );
		register_setting( 'siwg_settings', 'siwg_google_user_default_role' );
		register_setting( 'siwg_settings', 'siwg_use_google_profile_picture' );
		register_setting( 'siwg_settings', 'siwg_google_domain_restriction', array( $this, 'domain_input_validation' ) );
		register_setting( 'siwg_settings', 'siwg_google_email_sanitization' );
		register_setting( 'siwg_settings', 'siwg_save_google_userinfo' );
		register_setting( 'siwg_settings', 'sign_allow_forced_domains_on_disabled_registrations' );
		register_setting( 'siwg_settings', 'siwg_allow_registration_even_if_disabled' );
		register_setting( 'siwg_settings', 'siwg_show_unlink_in_profile' );
		register_setting( 'siwg_settings', 'siwg_show_on_login' );
		register_setting( 'siwg_settings', 'siwg_allow_mail_change' );
		register_setting( 'siwg_settings', 'siwg_disable_login_page' );
		register_setting( 'siwg_settings', 'siwg_google_custom_redir_url', array( $this, 'custom_login_input_validation' ) );
		register_setting( 'siwg_settings', 'siwg_expose_class_instance' );
	}

	/**
	 * Settings section callback function.
	 *
	 * This function is needed to add a new section.
	 *
	 * @since    1.0.0
	 */
	public function siwg_section() {
		echo sprintf(
			'<p>%s <a href="%s" rel="noopener" target="_blank">%s</a></p>',
			__( 'Please paste in the necessary credentials so that we can authenticate your users.', 'sign-in-with-google' ),
			'https://wordpress.org/plugins/sign-in-with-google/#where%20can%20i%20get%20a%20client%20id%20and%20client%20secret%3F',
			__( 'Learn More', 'sign-in-with-google' )
		);
	}

	/**
	 * Callback function for Google Client ID
	 *
	 * @since    1.0.0
	 */
	public function siwg_google_client_id() {
		echo '<input name="siwg_google_client_id" id="siwg_google_client_id" type="text" size="50" value="' . get_option( 'siwg_google_client_id' ) . '"/>';
	}

	/**
	 * Callback function for Google Client Secret
	 *
	 * @since    1.0.0
	 */
	public function siwg_google_client_secret() {
		echo '<input name="siwg_google_client_secret" id="siwg_google_client_secret" type="text" size="50" value="' . get_option( 'siwg_google_client_secret' ) . '"/>';
	}

	/**
	 * Callback function for Custom redir url
	 *
	 * @since    1.0.0
	 */
	public function siwg_google_custom_redir_url() {
		echo '<input name="siwg_google_custom_redir_url" id="siwg_google_custom_redir_url" type="text" size="50" value="' . get_option( 'siwg_google_custom_redir_url', '?google_response' ) . '" placeholder="?google_response" />';
	}

	/**
	 * Callback function for Google User Default Role
	 *
	 * @since    1.0.0
	 */
	public function siwg_google_user_default_role() {
		?>
		<select name="siwg_google_user_default_role" id="siwg_google_user_default_role">
			<?php
			$siwg_roles = get_editable_roles();
			foreach ( $siwg_roles as $key => $value ) :
				$siwg_selected = '';
				if ( get_option( 'siwg_google_user_default_role', 'subscriber' ) === $key ) {
					$siwg_selected = 'selected';
				}
				?>

				<option value="<?php echo $key; ?>" <?php echo $siwg_selected; ?>><?php echo $value['name']; ?></option>

			<?php endforeach; ?>

		</select>
		<?php
	}

	/**
	 * Callback function for Google Domain Restriction
	 *
	 * @since    1.0.0
	 */
	public function siwg_google_domain_restriction() {
		// Get the TLD and domain.
		$siwg_urlparts    = parse_url( site_url() );
		$siwg_domain      = $siwg_urlparts['host'];
		$siwg_domainparts = explode( '.', $siwg_domain );
		// fix for localhost
		$siwg_domain = count( $siwg_domainparts ) === 1 ? $siwg_domainparts[0] : $siwg_domainparts[ count( $siwg_domainparts ) - 2 ] . '.' . $siwg_domainparts[ count( $siwg_domainparts ) - 1 ];

		?>
		<input name="siwg_google_domain_restriction" id="siwg_google_domain_restriction" type="text" size="50" value="<?php echo get_option( 'siwg_google_domain_restriction' ); ?>" placeholder="<?php echo $siwg_domain; ?>">
		<p class="description"><?php _e( 'Enter the domain you would like to restrict new users to or leave blank to allow anyone with a google account. (Separate multiple domains with commas)', 'sign-in-with-google' ); ?></p>
		<p class="description">
			<?php
			printf(
				// translators: An example of the required email domain users must have when logging in.
				esc_html__( 'Entering %1$s will only allow Google users with an @%2$s email address to sign up.', 'sign-in-with-google' ),
				$siwg_domain,
				$siwg_domain
			);
			?>
		</p>
		<?php
	}

	/**
	 * Callback function for Email Sanitize
	 *
	 * @since    1.0.0
	 */
	public function siwg_google_email_sanitization() {

		echo sprintf(
			'<input type="checkbox" name="%1$s" id="%1$s" value="1" %2$s /><p class="description">%3$s</p>',
			'siwg_google_email_sanitization',
			checked( get_option( 'siwg_google_email_sanitization', true ), true, false ),
			__( 'If enabled, user emails will be sanitized during registration to the base unique account (like <code>james.figard+123@gmail.com</code> to <code>jamesfigard@gmail.com</code> so you can avoid unlimited duplicate/spam registration from gmail aliases).', 'sign-in-with-google' ),
		);
	}

	/**
	 * Callback function for Allow users with the approved domain register accounts
	 *
	 * @since    [NEXT]
	 */
	public function sign_allow_forced_domains_on_disabled_registrations() {

		echo sprintf(
			'<input type="checkbox" name="%1$s" id="%1$s" value="1" %2$s /><p class="description">%3$s</p>',
			'sign_allow_forced_domains_on_disabled_registrations',
			checked( get_option( 'sign_allow_forced_domains_on_disabled_registrations' ), true, false ),
			__( 'If enabled, users with domains in the "Restrict to Domain" field will be allowed to register new user accounts even when new user registrations are disabled.', 'sign-in-with-google' ),
		);
	}

	/**
	 * Callback function for Allow users with the approved domain register accounts
	 *
	 * @since    [NEXT]
	 */
	public function siwg_allow_registration_even_if_disabled() {

		echo sprintf(
			'<input type="checkbox" name="%1$s" id="%1$s" value="1" %2$s /><p class="description">%3$s</p>',
			'siwg_allow_registration_even_if_disabled',
			checked( get_option( 'siwg_allow_registration_even_if_disabled' ), true, false ),
			__( 'If registrations are disabled in site settings &gt; general, people will be still regsitered if they successfully login with Google Sign In', 'sign-in-with-google' ),
		);
	}

	/**
	 * Callback function for Allow users with the approved domain register accounts
	 *
	 * @since    [NEXT]
	 */
	public function siwg_expose_class_instance() {

		echo sprintf(
			'<input type="checkbox" name="%1$s" id="%1$s" value="1" %2$s /><p class="description">%3$s</p>',
			'siwg_expose_class_instance',
			checked( get_option( 'siwg_expose_class_instance', true ), true, false ),
			__( 'If you want instantiated class of SignInWithGoogle to be available for other plugins under $GLOBALS[\'SIGN_IN_WITH_GOOGLE_INSTANCE_PUBLIC\'] and $GLOBALS[\'SIGN_IN_WITH_GOOGLE_INSTANCE_ADMIN\']', 'sign-in-with-google' ),
		);
	}

	/**

	 * Callback function for Show Unlink Button in user's profile page
	 *
	 * @since    [NEXT]
	 */
	public function siwg_show_unlink_in_profile() {

		echo sprintf(
			'<input type="checkbox" name="%1$s" id="%1$s" value="1" %2$s /><p class="description">%3$s</p>',
			'siwg_show_unlink_in_profile',
			checked( get_option( 'siwg_show_unlink_in_profile' ), true, false ),
			__( 'Allow users to unlink their account from google (when you do not want users could control themselves, you should uncheck this option).', 'sign-in-with-google' ),
		);
	}

	/*
	 * Callback function for Save user infos received from google
	 *
	 * @since    [NEXT]
	 */
	public function siwg_save_google_userinfo() {

		echo sprintf(
			'<input type="checkbox" name="%1$s" id="%1$s" value="1" %2$s /><p class="description">%3$s</p>',
			'siwg_save_google_userinfo',
			checked( get_option( 'siwg_save_google_userinfo' ), true, false ),
			__( 'If enabled, user info  (full name, language, id, profile-picture and other info, received from google after successful authorization), will be saved in user-metadatas.', 'sign-in-with-google' ),
		);
	}


	/**
	 * Callback function for Show Google Signup Button on Login Form
	 *
	 * @since    1.0.0
	 */
	public function siwg_show_on_login() {

		echo '<input type="checkbox" name="siwg_show_on_login" id="siwg_show_on_login" value="1" ' . checked( get_option( 'siwg_show_on_login' ), true, false ) . ' />';

	}

	/**

	 * Callback function for Allow user to change own email
	 *
	 * @since    1.0.0
	 */
	public function siwg_allow_mail_change() {

		echo '<input type="checkbox" name="siwg_allow_mail_change" id="siwg_allow_mail_change" value="1" ' . checked( get_option( 'siwg_allow_mail_change' ), true, false ) . ' />';

	}

	/**
	 * Callback function for Show Google Signup Button on Login Form
	 *
	 * @since    1.0.0
	 */
	public function siwg_disable_login_page() {
		echo '<input type="checkbox" name="siwg_disable_login_page" id="siwg_disable_login_page" value="1" ' . checked( get_option( 'siwg_disable_login_page' ), true, false ) . ' />';
	}

	/**
	 * Callback function for validating the form inputs.
	 *
	 * @since    1.0.0
	 * @param string $input The input supplied by the field.
	 */
	public function input_validation( $input ) {

		// Strip all HTML and PHP tags and properly handle quoted strings.
		$sanitized_input = strip_tags( stripslashes( $input ) );

		return $sanitized_input;
	}

	/**
	 * Callback function for validating the form inputs.
	 *
	 * @since    1.0.0
	 * @param string $input The input supplied by the field.
	 */
	public function domain_input_validation( $input ) {

		// Strip all HTML and PHP tags and properly handle quoted strings.
		$sanitized_input = strip_tags( stripslashes( $input ) );

		if ( '' !== $sanitized_input && ! Sign_In_With_Google_Utility::verify_domain_list( $sanitized_input ) ) {

			add_settings_error(
				'siwg_settings',
				esc_attr( 'domain-error' ),
				__( 'Please make sure you have a proper comma separated list of domains.', 'sign-in-with-google' ),
				'error'
			);
		}

		return $sanitized_input;
	}

	/**
	 * Callback function for Use Google Profile Image
	 *
	 * @since    1.0.0
	 */
	public function siwg_use_google_profile_picture() {

		echo '<input type="checkbox" name="siwg_use_google_profile_picture" id="siwg_use_google_profile_picture" value="1" ' . checked( get_option( 'siwg_use_google_profile_picture', true ), true, false ) . ' />';

	}

	/**
	 * Callback function for validating custom login param input.
	 *
	 * @since    1.0.0
	 * @param string $input The input supplied by the field.
	 */
	public function custom_login_input_validation( $input ) {
		// Strip all HTML and PHP tags and properly handle quoted strings.
		$sanitized_input = strip_tags( stripslashes( $input ) );

		return $sanitized_input;
	}

	/**
	 * Render the settings page.
	 *
	 * @since    1.0.0
	 */
	public function settings_page_render() {

		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// show error/update messages.
		settings_errors( 'siwg_messages' );
		?>
		<div class="wrap">
			<h2><?php esc_html_e( 'Sign In With Google Settings', 'sign-in-with-google' ); ?></h2>
			<form method="post" action="options.php">
				<?php settings_fields( 'siwg_settings' ); ?>
				<?php do_settings_sections( 'siwg_settings' ); ?>
				<p class="submit">
					<input name="submit" type="submit" id="submit" class="button-primary" value="<?php esc_html_e( 'Save Changes', 'sign-in-with-google' ); ?>" />
				</p>
			</form>
			<div class="metabox-holder">
				<div class="postbox">
					<h3><span><?php esc_html_e( 'Export Settings', 'sign-in-with-google' ); ?></span></h3>
					<div class="inside">
						<p><?php esc_html_e( 'Export the plugin settings for this site as a .json file.', 'sign-in-with-google' ); ?></p>
						<form method="post">
							<p><input type="hidden" name="siwg_action" value="export_settings" /></p>
							<p>
								<?php wp_nonce_field( 'siwg_export_nonce', 'siwg_export_nonce' ); ?>
								<?php submit_button( esc_html__( 'Export', 'sign-in-with-google' ), 'secondary', 'submit', false ); ?>
							</p>
						</form>
					</div><!-- .inside -->
				</div><!-- .postbox -->

				<div class="postbox">
					<h3><span><?php esc_html_e( 'Import Settings', 'sign-in-with-google' ); ?></span></h3>
					<div class="inside">
						<p><?php esc_html_e( 'Import the plugin settings from a .json file. This file can be obtained by exporting the settings on another site using the form above.', 'sign-in-with-google' ); ?></p>
						<form method="post" enctype="multipart/form-data">
							<p>
								<input type="file" name="import_file"/>
							</p>
							<p>
								<input type="hidden" name="siwg_action" value="import_settings" />
								<?php wp_nonce_field( 'siwg_import_nonce', 'siwg_import_nonce' ); ?>
								<?php submit_button( esc_html__( 'Import', 'sign-in-with-google' ), 'secondary', 'submit', false ); ?>
							</p>
						</form>
					</div><!-- .inside -->
				</div><!-- .postbox -->
			</div><!-- .metabox-holder -->
		</div>

		<?php

	}

	/**
	 * Redirect the user to get authenticated by Google.
	 *
	 * @since    1.0.0
	 */
	public function google_auth_redirect() {

		// Gather necessary elements for 'state' parameter.
		$redirect_to = isset( $_GET['redirect_to'] ) ? $_GET['redirect_to'] : '';

		$this->state = array(
			'redirect_to' => $redirect_to,
		);

		$url = $this->google_auth->get_google_auth_url( $this->state );
		wp_redirect( $url );
		exit;
	}

	/**
	 * Uses the code response from Google to authenticate the user.
	 *
	 * @since 1.0.0
	 */
	public function authenticate_user($code = null, $state = null, $redirect_after_login = true) {

		$params = [];
		$params['code'] = $code ?: $_GET['code'];
		$params['state'] = $state ?: $_GET['state'];
		$params['redirect_after_login'] = true;

		$access_token = $this->set_access_token( $params['code'] );
		$this->user = $this->get_user_by_token( $access_token );

		if (!$this->user) {
			// Something went wrong, redirect to the login page.
			return ['error'=>'Could not validate user'];
		}

		// If the user is logged in, just connect the authenticated Google account.
		if ( is_user_logged_in() ) {

			// link the account.
			$this->connect_account( $this->user->email );

		} else {
				
			// Check if a user is linked to this Google account.
			$linked_user = get_users(
				array(
					'meta_key'   => 'siwg_google_account',
					'meta_value' => $this->user->email,
				)
			);

			// If user is linked to Google account, sign them in. Otherwise, check the domain
			// and create the user if necessary.
			$validUser = null;
			if ( ! empty( $linked_user ) ) {

				$validUser = $linked_user[0]; 
			} else {

				$result_user = $this->find_by_email_or_create( $this->user );

				if (is_int($result_user)) {
					$validUser = get_user_by( 'id', $result_user );
					// link the account.
					$this->connect_account( $this->user->email, $validUser );
				}
			}

			if ( $validUser ) {
				
				wp_set_current_user( $validUser->ID, $validUser->user_login );
				wp_set_auth_cookie( $validUser->ID );
				do_action( 'wp_login', $validUser->user_login, $validUser ); // phpcs:ignore

				if ( (bool) get_option ('siwg_save_google_userinfo') ) {
					update_user_meta ( $validUser->ID, 'siwg_google_userinfo', apply_filters( 'siwg_saved_google_userinfo', $this->user ) );
				}
			}
		}

		// Decode passed back state.
		$state     = json_decode( base64_decode( $params['state']) );

		$redirect = !empty($state->my_redirect_uri ) ? $state->my_redirect_uri  : admin_url('profile.php?siwg_redirected'); // Send users to the dashboard by default.

		if ($redirect_after_login && !empty($redirect)) {
			wp_redirect( apply_filters( 'siwg_google_redirect_after_login_url', $redirect ) ); //phpcs:ignore
			exit;
		}
		return ['error'=>null, 'redirecting_to'=>$redirect];
	}

	/**
	 * Displays a message to the user if domain restriction is in use and their domain does not match.
	 *
	 * @since    1.0.0
	 * @param string $message The message to show the user on the login screen.
	 */
	public function domain_restriction_error( $message ) {
		// translators: The required domain.
		$message = '<div id="login_error"> ' . sprintf( __( 'You must have an email with a required domain (<strong>%s</strong>) to log in to this website using Google.', 'sign-in-with-google' ), get_option( 'siwg_google_domain_restriction' ) ) . '</div>';
		return $message;
	}

	/**
	 * Process a settings export that generates a .json file of the shop settings
	 */
	public function process_settings_export() {

		if ( empty( $_POST['siwg_action'] ) || 'export_settings' !== $_POST['siwg_action'] ) {
			return;
		}

		if ( ! wp_verify_nonce( $_POST['siwg_export_nonce'], 'siwg_export_nonce' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = array(
			'siwg_google_client_id'               => get_option( 'siwg_google_client_id' ),
			'siwg_google_client_secret'           => get_option( 'siwg_google_client_secret' ),
			'siwg_google_user_default_role'       => get_option( 'siwg_google_user_default_role' ),
			'siwg_use_google_profile_picture'     => get_option( 'siwg_use_google_profile_picture', true ),
			'siwg_google_domain_restriction'      => get_option( 'siwg_google_domain_restriction' ),
			'siwg_google_email_sanitization'      => get_option( 'siwg_google_email_sanitization' ),			
			'sign_allow_forced_domains_on_disabled_registrations' => get_option( 'sign_allow_forced_domains_on_disabled_registrations' ),
			'siwg_allow_registration_even_if_disabled' => get_option( 'siwg_allow_registration_even_if_disabled' ),
			'siwg_show_unlink_in_profile'         => get_option( 'siwg_show_unlink_in_profile' ),
			'siwg_show_on_login'                  => get_option( 'siwg_show_on_login' ),
			'siwg_allow_mail_change'              => get_option( 'siwg_allow_mail_change' ),
			'siwg_google_custom_redir_url'        => get_option( 'siwg_google_custom_redir_url', '?google_response' ),
			'siwg_expose_class_instance'          => get_option( 'siwg_expose_class_instance', true ),
		);

		ignore_user_abort( true );

		nocache_headers();

		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=siwg-settings-export-' . gmdate( 'm-d-Y' ) . '.json' );
		header( 'Expires: 0' );

		echo json_encode( $settings );
		exit;
	}

	/**
	 * Process a settings import from a json file
	 */
	public function process_settings_import() {

		if ( empty( $_POST['siwg_action'] ) || 'import_settings' !== $_POST['siwg_action'] ) {
			return;
		}

		if ( ! wp_verify_nonce( $_POST['siwg_import_nonce'], 'siwg_import_nonce' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$extension = end( explode( '.', $_FILES['import_file']['name'] ) );

		if ( 'json' !== $extension ) {
			wp_die( __( 'Please upload a valid .json file', 'sign-in-with-google' ) );
		}

		$import_file = $_FILES['import_file']['tmp_name'];

		if ( empty( $import_file ) ) {
			wp_die( __( 'Please upload a file to import', 'sign-in-with-google' ) );
		}

		// Retrieve the settings from the file and convert the json object to an array.
		$settings = (array) json_decode( file_get_contents( $import_file ) );

		foreach ( $settings as $key => $value ) {
			update_option( $key, $value );
		}

		wp_safe_redirect( admin_url( 'options-general.php?page=siwg_settings' ) );

		exit;
	}

	/**
	 * Sets the access_token using the response code.
	 *
	 * @since 1.0.0
	 * @param string $code The code provided by Google's redirect.
	 *
	 * @return mixed Access token on success or WP_Error.
	 */
	protected function set_access_token( $code = '' ) {

		if ( ! $code ) {
			throw new \Exception ( 'No authorization code provided.' );
		}

		// Sanitize auth code.
		$code = sanitize_text_field( $code );

		$custom_redir_url = get_option ( 'siwg_google_custom_redir_url', '?google_response');

		$final_redir_url = str_contains( $custom_redir_url, '://' ) ? $custom_redir_url : site_url( $custom_redir_url );

		$args = array(
			'body' => array(
				'code'          => $code,
				'client_id'     => get_option( 'siwg_google_client_id' ),
				'client_secret' => get_option( 'siwg_google_client_secret' ),
				'redirect_uri'  => $final_redir_url,
				'grant_type'    => 'authorization_code',
			),
		);

		$response = wp_remote_post( 'https://www.googleapis.com/oauth2/v4/token', $args );
		
		$body = json_decode( wp_remote_retrieve_body( $response ) );
		/*
		error:
		  {
		    public $error => "invalid_grant"
		  	public $error_description => "Bad Request"
		  }
		
		success:
		  {
		    public $access_token =>  "yaG453h..."
		    public $expires_in   =>  int(3599)
		    public $scope        => "openid https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/userinfo.profile"
		    public $token_type   => "Bearer"
		    public $id_token      => "eyAfd46iOiJSUzI..."
		  }
		*/

		if ( isset($body->access_token) && '' !== $body->access_token ) {
			return $body->access_token;
		}

		return false;
	}

	/**
	 * Add usermeta for current user and Google account email.
	 *
	 * @since 1.3.1
	 * @param string $email The users authenticated Google account email.
	 */
	protected function connect_account( $email = '', $wp_user = null ) {

		if ( ! $email ) {
			return false;
		}

		$current_user = $wp_user ?: wp_get_current_user();

		if ( ! ( $current_user instanceof WP_User ) ) {
			return false;
		}

		return add_user_meta( $current_user->ID, 'siwg_google_account', $email, true );
	}

	/**
	 * Remove usermeta for current user and Google account email.
	 *
	 * @since 1.3.1
	 */
	public function disconnect_account() {

		// if user not allowed to unlink, then return
		if ( ! current_user_can( 'manage_options' ) && ! get_option( 'siwg_show_unlink_in_profile' ) ) 
			return;

		if ( ! isset( $_POST['_siwg_account_nonce'] ) || ! wp_verify_nonce( $_POST['_siwg_account_nonce'], 'siwg_unlink_account' ) ) {
			wp_die( __( 'Unauthorized', 'sign-in-with-google' ) );
		}

		$current_user = wp_get_current_user();

		if ( ! ( $current_user instanceof WP_User ) ) {
			return false;
		}

		return delete_user_meta( $current_user->ID, 'siwg_google_account' );
	}

	/**
	 * Gets a user by email or creates a new user.
	 *
	 * @since 1.0.0
	 * @param object $user_data  The Google+ user data object.
	 */
	protected function find_by_email_or_create( $user_data ) {

		$user_email = $user_data->email;
		if (get_option( 'siwg_google_email_sanitization', true )) {
			$user_email = Sign_In_With_Google_Utility::sanitize_google_email( $user_email );
		}
		
		// The user doesn't have the correct domain, don't authenticate them.
		$force_domains = array_filter( explode( ', ', get_option( 'siwg_google_domain_restriction' ) ) );
		$user_domain = explode( '@', $user_email );

		$is_forbidden_domain = ! empty($force_domains) && ! in_array( $user_domain[1], $force_domains, true );
		$is_whitelisted_domain = ! empty($force_domains) && in_array( $user_domain[1], $force_domains, true );

		if ( $is_forbidden_domain ) {
			wp_redirect( apply_filters( 'siwg_forbidden_registration_redirect', wp_login_url() . '?google_login=incorrect_domain' ) );
			exit;
		}

		$user = get_user_by( 'email', $user_email );

		// Redirect the user if registrations are disabled and there is no domain user registration override.
		$forbid_registration = 
			! $user && 
			(
				! get_option( 'users_can_register' ) 
					&& 
				! get_option( 'siwg_allow_registration_even_if_disabled' ) 
					&&
				( ! get_option( 'sign_allow_forced_domains_on_disabled_registrations' ) || ! $is_whitelisted_domain )
			);

		if ( $forbid_registration ) {
			wp_redirect( apply_filters( 'siwg_forbidden_registration_redirect', site_url( 'wp-login.php?registration=disabled' ) ) );
			exit;
		}

		// hooked to disallow user login/registration (i.e. banned emails) from external codes
		if ( ! apply_filters( 'siwg_permit_authorization', true, $user_data, $user, $user_email ) ) {
			wp_redirect( apply_filters( 'siwg_forbidden_registration_redirect', site_url( 'wp-login.php?registration=disabled&userstatus=disallowed' ) ) );
			exit;
		}
	
		if ( false !== $user ) { 
			update_user_meta( $user->ID, 'first_name', $user_data->given_name );
			update_user_meta( $user->ID, 'last_name', $user_data->family_name );
			return $user; 
		}

		$user_pass    = wp_generate_password( 18 );
		$user_email   = $user_data->email;
		// set username as friendly as possible
		$user_email_data = explode( '@', $user_email );
		$user_login      = $user_email_data[0];
		while ( username_exists($user_login) ) {
			$user_login  = $user_login . rand(1,10);
		}

		$user = array(
			'user_pass'       => $user_pass,
			'user_login'      => $user_email, //$user_login
			'user_email'      => $user_email,
			'display_name'    => $user_data->given_name . ' ' . $user_data->family_name,
			'first_name'      => $user_data->given_name,
			'last_name'       => $user_data->family_name,
			'user_registered' => gmdate( 'Y-m-d H:i:s' ),
			'role'            => get_option( 'siwg_google_user_default_role', 'subscriber' ),
		);
		$user = apply_filters ('siwg_pre_insert_user', $user, $user_data);
		$new_user_id = wp_insert_user( $user );
		do_action ('siwg_after_new_user_insert', $new_user_id );

		if ( is_wp_error( $new_user_id ) ) {
			do_action ('siwg_new_user_creation_error', $new_user_id );
			wp_die( $new_user_id->get_error_message() . ' <a href="' . wp_login_url() . '">Return to Log In</a>' );
			return false;
		} else {
			$this->check_and_update_profile_pic ($new_user_id, $user_data);
			return $new_user_id;
		}

	}


	/**
	 * Check & update user's profile pic
	 *
	 * @since 1.2.0
	 *
	 * @param string $user_id The user's id
	 * @param object $user_data Obtained user-data from google
	 */
	protected function check_and_update_profile_pic( $user_id, $user_data ) {
		// add profile image from google, that can be used as alternative to gravatar
		if ( property_exists( $user_data, 'picture' ) ) {
			update_user_meta( $user_id, 'siwg_profile_image', $user_data->picture);
		}
	}

	function slug_get_avatar( $avatar, $id_or_email, $size, $default, $alt ) {

		if (!get_option('siwg_use_google_profile_picture', true)) {
			return $avatar;
		}

		//If is email, try and find user ID
		if( ! is_numeric( $id_or_email ) && is_email( $id_or_email ) ){
			$user  =  get_user_by( 'email', $id_or_email );
			if( $user ){
				$id_or_email = $user->ID;
			}
		}
	
		//if not user ID, return
		if( ! is_numeric( $id_or_email ) ){
			return $avatar;
		}
	
		//Find URL of saved avatar in user metaget_user_meta( $id, 'siwg_profile_image')
		$saved = get_user_meta( $id_or_email, 'siwg_profile_image', true );

		//check if it is a URL
		if( filter_var( $saved, FILTER_VALIDATE_URL ) ) {
			//return saved image
			return sprintf( '<img src="%s" />', esc_url( $saved ));
		}
	
		//return normal
		return $avatar;
	}
	

	/**
	 * Get the user's info.
	 *
	 * @since 1.2.0
	 *
	 * @param string $token The user's token for authentication.
	 */
	protected function get_user_by_token( $token ) {

		if ( ! $token ) {
			return;
		}

		$args = array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $token,
			),
		);

		$result = wp_remote_request( 'https://www.googleapis.com/userinfo/v2/me', $args );

		return json_decode( wp_remote_retrieve_body( $result ) );
		//
		// 	{
		// 		public $id             =>  "12345..."
		// 		public $email          => "example@gmail.com"
		// 		public $verified_email => bool(true)
		// 		public $name           => string(8) "Firstname Lastname"
		// 		public $given_name     => string(2) "Firstname"
		// 		public $family_name    => string(5) "Lastname"
		// 		public $picture        => string(98) "https://lh3.googleusercontent.com/a/xyzxyzxyzxyz=s96-c"
		// 		public $locale         => string(2) "en"
		// 	}
		//
	}

	/**
	 * Disable Login page & redirect directly to google login
	 *
	 * @since 1.3.1
	 */ 
	public function check_login_redirection()
	{
		if ( boolval( get_option( 'siwg_disable_login_page' ) ) ) 
		{
			// Skip only logout action
			$action = trim( strtolower( $_REQUEST['action'] ) );
			if ( ! in_array( $action, ["logout", "registration"] ) ) {
				$this->google_auth_redirect();
			}
		}
	}


	
	/**
	 * Disable User email modifications
	 *    https://wordpress.stackexchange.com/a/363376/33667
	 * @since 1.3.1
	 */
	public function disallow_email_changes()
	{
		if ( ! current_user_can( 'manage_options' ) && ! get_option('siwg_allow_mail_change') ) 
		{
			add_action( 'personal_options_update',  
				function ($user_id) {
					if ( !current_user_can( 'manage_options' ) ) { 
						$user = get_user_by('id', $user_id ); 
						$_POST['email'] = $user->user_email; // reset back to original, so user can't modify
					}
				}, 
				5
			);

			add_action( 'show_user_profile',
				function ($user) {
					if ( !current_user_can( 'manage_options' ) ) { 
						?><script>document.getElementById("email").setAttribute("disabled", "disabled");</script> <?php 
					} 
				}
			); 
		}
	}

}
