<?php
    /**
     * 
     * Functionality related to the Admin pages
     * 
     */
    add_action('admin_enqueue_scripts', 
               function () {
                    wp_enqueue_script('astrava_admin_scripts', 
                                       ASTRAVA_PLUGIN_URL . 'assets/js/astrava-admin.js', 
                                       array(), 
                                       '1.0', 
                                       true);

                    wp_enqueue_script('jquery-ui-datepicker');
                    wp_enqueue_style('jquery-style', 
                                     'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css');

                });

    /**
     * Add the Astrava tab to the settings menu
     */
	add_action('admin_menu', 
                function () {
                    add_options_page('Astrava',
                                     'Astrava',
                                     'manage_options',
                                     'astrava_admin',
                                     'display_astrava_admin_page');
                });

    /**
     * Create the astrava options
     */
    add_action('admin_init', 
                function () {
                    register_setting('astrava_api_settings','astrava_api_settings');        
                    
                    add_settings_section('astrava_api_settings', 
                                         'Api Settings', 
                                         'display_strava_connect_instructions', 
                                         'astrava_api_settings');
                    
                    add_settings_field('strava_client_id', 
                                       'Client ID', 
                                       'display_admin_field', 
                                       'astrava_api_settings', 
                                       'astrava_api_settings', 
                                       array('strava_client_id'));

                    add_settings_field('strava_client_secret', 
                                       'Client Secret', 
                                       'display_admin_field', 
                                       'astrava_api_settings', 
                                       'astrava_api_settings', 
                                       array('strava_client_secret'));

                    add_settings_field('strava_oauth', 
                                       'Access Token', 
                                       'process_oath_token', 
                                       'astrava_api_settings', 
                                       'astrava_api_settings', 
                                       array('strava_oauth', 'disabled'));

                    register_setting('astrava_gen_settings','astrava_gen_settings');

                    add_settings_section('astrava_gen_settings', 
                                         'General Settings', 
                                         'display_strava_settings', 
                                         'astrava_gen_settings');

                    add_settings_field('auto_create_post', 
                                       'Autocreate Posts for new activity', 
                                       'display_astrava_auto_create', 
                                       'astrava_gen_settings', 
                                       'astrava_gen_settings');

                    add_settings_field('auto_create_post_cat', 
                                       'Default category for auto-created post', 
                                       'display_astrava_auto_create_cat', 
                                       'astrava_gen_settings', 
                                       'astrava_gen_settings');

                    add_settings_field('embed_type', 
                                       'Embed Type', 
                                       'display_astrava_embed_type', 
                                       'astrava_gen_settings', 
                                       'astrava_gen_settings');

                    add_settings_field('embed_units', 
                                       'Units of measurment', 
                                       'display_astrava_embed_units', 
                                       'astrava_gen_settings', 
                                       'astrava_gen_settings');

                    add_settings_field('embed_pace', 
                                       'Use pace for runs', 
                                       'display_astrava_embed_pace', 
                                       'astrava_gen_settings', 
                                       'astrava_gen_settings');
                });
		
    /**
     * Ad the Meta box to the post page
     */
    add_action("add_meta_boxes", 
                function () {
                    add_meta_box("astrava-admin-meta", 
                                 "Recent Strava Activity", 
                                 "display_astrava_admin_meta", 
                                 "post", 
                                 "side", 
                                 "core", 
                                 null);
                });

    
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
            $strava       = new StravaApi($options['strava_client_id'], $options['strava_client_secret']);
            $access_token = $strava->fetchOauthToken($_GET['code']);

            $options['strava_oauth'] = $access_token;
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
        if (empty($options['strava_client_id']) || 
            empty($options['strava_client_secret']) || 
            empty($options['strava_oauth'])){

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
     * Wrapper for the embed_type option    
     * 
     * @return void
     */
    function display_astrava_embed_type() {
        display_admin_select('astrava_gen_settings', 
                             'embed_type', 
                             array('iframe' => 'iframe', 'template' => 'template'));
    }

    /**
     * Wrapper for the embed_units option    
     * 
     * @return void
     */
    function display_astrava_embed_units() {
        display_admin_select('astrava_gen_settings', 
                             'embed_units', 
                             array('Miles/Feet' => 'i', 'Meter/Kilometers' => 'm'));
    }

    /**
     * Wrapper for the embed_pace option    
     * 
     * @return void
     */
    function display_astrava_embed_pace() {
        display_admin_select('astrava_gen_settings', 
                             'embed_pace', 
                             array('Yes' => 1, 'No' => 0));
    }

    /**
     * Wrapper for the display_astrava_auto_create option    
     * 
     * @return void
     */
    function display_astrava_auto_create() {
        display_admin_select('astrava_gen_settings', 
                             'auto_create_post', 
                             array('Yes' => 1, 'No' => 0));
    }

    /**
     * Wrapper for the auto_create_post_cat option    
     * 
     * @return void
     */
    function display_astrava_auto_create_cat() {
        $options  = wp_parse_args(get_option('astrava_gen_settings'), array('auto_create_post_cat' => 0)); 

        wp_dropdown_categories(array('name'          => 'astrava_gen_settings[auto_create_post_cat]',
                                     'selected'      => $options['auto_create_post_cat'],
                                     'hide_empty'    => false,
                                     'hide_if_empty' => false));
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

        if (!empty($options['strava_client_id']) && !empty($options['strava_client_secret'])){
            $authCallbackUrl = site_url() . '/wp-admin/options-general.php?page=astrava_admin&tab=api';
            $strava          = new StravaApi($options['strava_client_id'], $options['strava_client_secret']);
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
                submit_button();
                break;
            case 'gen':
                settings_fields('astrava_gen_settings');
                do_settings_sections('astrava_gen_settings');
                submit_button();
                break;
            case 'templates': 
                $astrava_templates = get_option('astrava_templates', array());            
                switch($_REQUEST['state']) {
                    case 'new':
                        echo $template->render('admin/new-template-form');
                        break;
                    case 'save':
                        $astrava_templates[strtolower($_REQUEST['template_name'])] = $_REQUEST['template_code'];
                        update_option('astrava_templates', $astrava_templates);

                        echo $template->render('admin/template-save-success');
                        
                    default: 
                        if ($_REQUEST['template_name']) {
                            $selected_type = $_REQUEST['template_name'];
                        } else {
                            $selected_type = 'default';
                        }

                        echo $template->render('admin/edit-template-form',  
                                                array('astrava_templates' => $astrava_templates,
                                                      'selected_type'     => $selected_type));
                        break;
                }

                submit_button();
                break;
            case 'import':
                // Look to see if we are new or submitted
                if ($_REQUEST['importfrom']) {
                    if (isset($_REQUEST['overwrite_old'])) {
                        set_transient('overwrite-strava-posts', 1, 60 * 60 * 12);
                    }

                    $new_timestamp = strtotime($_REQUEST['importfrom']);
                    update_option('astrava_last_activity_imported', $new_timestamp);
                    do_action('astrava_import');
                    echo $template->render('admin/import-started');
                } else {
                    echo $template->render('admin/import-form');
                    submit_button('Import');
                }
                break;
        }

        

        echo $template->render('admin/page-footer');
        
    }

    /**
     * Display a meta box on the post edit page.  This box will contain the last 5 items
     * 
     * @return void
     */
    function display_astrava_admin_meta()
    {
        $options = get_option('astrava_api_settings'); 
        $template = new Template(ASTRAVA_PLUGIN_DIR . 'templates/');

        //Check to see if the options are avaialble
        if (!empty($options['strava_oauth'])){
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
                $strava     = new StravaApi($options['strava_client_id'], 
                                            $options['strava_client_secret'],
                                            $options['strava_oauth']);
                $activities = $strava->getActivities($activity_params);
                set_transient('astrava-activity-'. $key, $activities, 60 * 10);
            }

            echo $template->render('admin/post-meta-box', array('activities' => $activities, 'current_page' => $current_page));
        } else { //If the plugin isn't configured, Display error message and link to configs.
            echo $template->render('admin/oauth-warning', array('optionsUrl' => home_url() . '/wp-admin/options-general.php?page=astrava_admin&tab=api'));
        }
        
    }
