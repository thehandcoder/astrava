<?php
    /**
     * 
     * Functionality related to the Admin pages
     * 
     */
	

function astrava_enqueue_admin( $hook ) {
    wp_enqueue_script( 'astrava_admin_scripts', ASTRAVA_PLUGIN_URL . 'assets/js/astrava-admin.js', array(), '1.0', true );
}

add_action( 'admin_enqueue_scripts', 'astrava_enqueue_admin' );

    /**
     * Add the Astrava tab to the settings menu
     */
	add_action('admin_menu', 'astrava_tab');
    /**
     * Create the astrava options
     */
    add_action('admin_init','astrava_admin_init');

    /**
     * Wrapper to add the options page to the Wordpress Admin
     * 
     * @return void
     */
	function astrava_tab() {
		add_options_page('Astrava','Astrava','manage_options','astrava_admin','display_astrava_admin_page');
	}
		
    /**
     * Ad the Meta box to the post page
     */
    add_action("add_meta_boxes", "add_custom_meta_box");

    /**
     * Wrapper to initialize all the astrava options
     * 
     * @return void
     */
	function astrava_admin_init() {

        register_setting('astrava_api_settings','astrava_api_settings');        
        add_settings_section('astrava_api_settings', 'Api Settings', 'display_strava_connect_instructions', 'astrava_api_settings');
        
        add_settings_field('astrava_strava_client_id', 'Client ID', 'display_admin_field', 'astrava_api_settings', 
                           'astrava_api_settings', array('astrava_strava_client_id'));
        add_settings_field('astrava_strava_client_secret', 'Client Secret', 'display_admin_field', 'astrava_api_settings', 
                           'astrava_api_settings', array('astrava_strava_client_secret'));
        add_settings_field('astrava_strava_oauth', 'Access Token', 'process_oath_token', 'astrava_api_settings', 
                           'astrava_api_settings', array('astrava_strava_oauth', 'disabled'));

        register_setting('astrava_gen_settings','astrava_gen_settings');		

		add_settings_section('astrava_gen_settings', 'General Settings', 'display_strava_settings', 'astrava_gen_settings');
		add_settings_field('astrava_auto_create_post', 'Autocreate Posts for new activity', 'display_astrava_auto_create', 
                           'astrava_gen_settings', 'astrava_gen_settings');
        add_settings_field('astrava_auto_create_post_cat', 'Default category for auto-created post', 
                           'display_astrava_auto_create_cat', 'astrava_gen_settings', 'astrava_gen_settings');
        add_settings_field('astrava_embed_type', 'Embed Type', 'display_astrava_embed_type', 
                           'astrava_gen_settings', 'astrava_gen_settings');

        add_settings_field('astrava_embed_units', 'Units of measurment', 'display_astrava_embed_units', 
                           'astrava_gen_settings', 'astrava_gen_settings');

        add_settings_field('astrava_embed_pace', 'Use pace for runs', 'display_astrava_embed_pace', 
                           'astrava_gen_settings', 'astrava_gen_settings');


        register_setting('astrava_templates','astrava_templates');
        add_settings_section('astrava_templates', 'Edit Templates', 'display_astrava_templates', 'astrava_templates');
        add_settings_field('default', 'Default Template', 'display_astrava_embed_template', 
                           'astrava_templates', 'astrava_templates');

	}

    /**
     * Wrapper for adding the metabox to the post
     * 
     * @return void
     */
    function add_custom_meta_box() {
        add_meta_box("astrava-admin-meta", "Recent Strava Activity", "display_astrava_admin_meta", 
                     "post", "side", "core", null);
    }

    /**
     * Process the request for a bearer token
     * @param  array $args An array of options as passed in from the add_settings_field.  It 
     *                     expects at least the initial array item to be the name of the option
     *                   
     * @return void
     */
    function process_oath_token($args) {
        // Look to see if we have a code
        if (isset($_GET['code'])) {
            $options      = wp_parse_args(get_option('astrava_api_settings'));
            $strava       = new StravaApi($options['astrava_strava_client_id'], $options['astrava_strava_client_secret']);
            $access_token = $strava->fetchOauthToken($_GET['code']);

            $options['astrava_strava_oauth'] = $access_token;
            update_option('astrava_api_settings', $options);
        }

        display_admin_field($args);

    }

    /**
     * Check to see if the strava API has been configured and display a warning if it hasn't
     * 
     * @return void
     */
    function display_strava_settings() {
        $options  = wp_parse_args( get_option( 'astrava_api_settings' )); 
        $template = new Template(ASTRAVA_PLUGIN_DIR . 'templates/');

        //Check to see if the options are avaialble
        if (empty($options['astrava_strava_client_id']) || 
            empty($options['astrava_strava_client_secret']) || 
            empty($options['astrava_strava_oauth'])){

            echo $template->render('admin/oauth-warning', array('optionsUrl' => home_url() . '/wp-admin/options-general.php?page=astrava_admin&tab=api'));
        }
    }


    /**
     * A Generic function to display an admin filed.  Assumes the $args array contains atleast the name of 
     * the field.  $args can contain a second boolean option to disable the field.
     * 
     * @param  array $args Array of args as passed in from add_settings_field
     * 
     * @return void
     */
    function display_admin_field($args) {
        $id       = $args[0];
        $options  = wp_parse_args( get_option( 'astrava_api_settings' ), array($id => ''));

        $template = new Template(ASTRAVA_PLUGIN_DIR . 'templates/');
        echo $template->render('admin/forms/input', array( 'id' => $id,
                                                            'value'    => $options[$id],
                                                            'disabled'   => ((isset($args[1])) ? ' disabled' : '' )));
    }

    /**
     * Generic function for displaying a select option in the admin
     * 
     * @param  string $settings The name of the options item
     * @param  string $field    The name of the option
     * @param  array  $values   The possible values of the optino in an associative array
     * 
     * @return void
     */
    function display_admin_select($settings, $field, $values) {
        $options  = wp_parse_args(get_option($settings), array($field => '')); 
        $template = new Template(ASTRAVA_PLUGIN_DIR . 'templates/');
        echo $template->render('admin/forms/select', array( 'settings' => $settings,
                                                            'field'    => $field,
                                                            'values'   => $values,
                                                            'current'  => $options[$field]));
    }

    /**
     * Wrapper for the astrava_embed_type option    
     * 
     * @return void
     */
    function display_astrava_embed_type() {
        display_admin_select('astrava_gen_settings', 'astrava_embed_type', array('iframe' => 'iframe', 
                                                                                 'template' => 'template'));
    }

    /**
     * Wrapper for the astrava_embed_units option    
     * 
     * @return void
     */
    function display_astrava_embed_units() {
        display_admin_select('astrava_gen_settings', 'astrava_embed_units', array('Miles/Feet' => 'i', 
                                                                                  'Meter/Kilometers' => 'm'));
    }

    /**
     * Wrapper for the astrava_embed_pace option    
     * 
     * @return void
     */
    function display_astrava_embed_pace() {
        display_admin_select('astrava_gen_settings', 'astrava_embed_pace', array('Yes' => 1, 
                                                                                 'No' => 0));
    }

    /**
     * Wrapper for the display_astrava_auto_create option    
     * 
     * @return void
     */
    function display_astrava_auto_create() {
        display_admin_select('astrava_gen_settings', 'display_astrava_auto_create', array('Yes' => 1, 
                                                                                          'No' => 0));
    }

    /**
     * Wrapper for the astrava_auto_create_post_cat option    
     * 
     * @return void
     */
    function display_astrava_auto_create_cat() {
        $options  = wp_parse_args(get_option('astrava_gen_settings'), array('astrava_auto_create_post_cat' => 0)); 
        wp_dropdown_categories(array('name'          => 'astrava_gen_settings[astrava_auto_create_post_cat]',
                                     'selected'      => $options['astrava_auto_create_post_cat'],
                                     'hide_empty'    => false,
                                     'hide_if_empty' => false));
    }

    /**
     * Create and displaye a template editor    
     * 
     * @return void
     */
    function display_astrava_embed_template() {
        $options  = wp_parse_args(get_option('astrava_templates'), array('default' => ''));
        $template = new Template(ASTRAVA_PLUGIN_DIR . 'templates/');
        wp_editor($options['default'], 'template_editor', 
                    array ('media_buttons' => false, 
                           'textarea_name' => 'astrava_templates[default]',
                           'textarea_rows' => 10)); 
        echo $template->render('admin/available-tags');
    }

    /**
     * Display the instructions for setting up the api access
     * 
     * @return void
     */
    function display_strava_connect_instructions() { 
        $id      = $args[0];
        $options = wp_parse_args( get_option( 'astrava_api_settings' )); 
        $data    = array('plugin_url' => ASTRAVA_PLUGIN_URL);

        if (!empty($options['astrava_strava_client_id']) && !empty($options['astrava_strava_client_secret'])){
            $authCallbackUrl = site_url() . '/wp-admin/options-general.php?page=astrava_admin&tab=api';
            $strava          = new StravaApi($options['astrava_strava_client_id'], $options['astrava_strava_client_secret']);
            $data['authUrl'] = $strava->getAuthorizeURL($authCallbackUrl);
        }

        $template = new Template(ASTRAVA_PLUGIN_DIR . 'templates/');
        echo $template->render('admin/oauth-instructions', $data);
    }

    /**
     * Display the base admin options page
     * 
     * @return void
     */
    function display_astrava_admin_page() {
        $ctab = (isset($_GET['tab']) ? $_GET['tab'] : 'gen');
        $template = new Template(ASTRAVA_PLUGIN_DIR . 'templates/');

        echo $template->render('admin/page-header', array('ctab' => $ctab));
        
        switch($ctab) {
            case 'api':
                settings_fields('astrava_api_settings');
                do_settings_sections('astrava_api_settings');
                break;
            case 'gen':
                settings_fields('astrava_gen_settings');
                do_settings_sections('astrava_gen_settings');  
                break;
            case 'templates':
                settings_fields('astrava_templates');
                do_settings_sections('astrava_templates');
                break;
        }

        submit_button();

        echo $template->render('admin/page-footer');
        
    }

    /**
     * Display a meta box on the post edit page.  This box will contain the last 5 items
     * 
     * @return void
     */
    function display_astrava_admin_meta()
    {
        $options = wp_parse_args( get_option( 'astrava_api_settings' )); 
        $template = new Template(ASTRAVA_PLUGIN_DIR . 'templates/');

        //Check to see if the options are avaialble
        if (!empty($options['astrava_strava_oauth'])){
            if (isset($_REQUEST['strava_page'])) {
                $current_page    = intval($_REQUEST['strava_page']);
                $activity_params = array('per_page' => 5, 'page' => $current_page);
            } else {
                $current_page    = 1;
                $activity_params = array('per_page' => 5);   
            }


            // create a hash from the params
            // check for a transient If none was found then make the request
            $key        = md5(serialize($activity_params));
            $activities = get_transient('astrava-activity-'. $key);
            
            if (!$activities) {
                $strava     = new StravaApi($options['astrava_strava_client_id'], 
                                            $options['astrava_strava_client_secret'],
                                            $options['astrava_strava_oauth']);
                $activities = $strava->getActivities($activity_params);
                set_transient('astrava-activity-'. $key, $activities, 60 * 10);
            }

            echo $template->render('admin/post-meta-box', array('activities' => $activities, 'current_page' => $current_page));
        } else { //If the plugin isn't configured, Display error message and link to configs.
            echo $template->render('admin/oauth-warning', array('optionsUrl' => home_url() . '/wp-admin/options-general.php?page=astrava_admin&tab=api'));
        }
        
    }
