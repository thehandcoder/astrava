<?php
    /**
     * 
     * 
     * Functionality related to the Admin pages
     * 
     * 
     * 
     */
    
   /**
    * 
    * These are all tehe functions and actions used for the admin section of the plugin
    * 
    */
	 
	add_action('admin_menu', 'astrava_tab');
	function astrava_tab() {
		add_options_page('Astrava','Astrava','manage_options','astrava_admin','display_astrava_admin_page');
	}
		

	add_action('admin_init','astrava_admin_init');
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

        add_settings_field('astrava_embed_template', 'Embed Template', 'display_astrava_embed_template', 
                           'astrava_gen_settings', 'astrava_gen_settings');

	}

    add_action("add_meta_boxes", "add_custom_meta_box");
    function add_custom_meta_box()
    {
        add_meta_box("astrava-admin-meta", "Recent Strava Activity", "display_astrava_admin_meta", "post", "side", "core", null);
    }


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
     * Functions that generate HTML are all below this line.
     */
    
    //@todo       Convert all HTML into templates

    function display_strava_settings() {
        
    }


    /**
     * [display_admin_field description]
     * @param  [type] $args [description]
     * @return [type]       [description]
     */
    function display_admin_field($args) {
        $id       = $args[0];
        $options  = wp_parse_args( get_option( 'astrava_api_settings' ), array($id => ''));
        $disabled = (isset($args[1])) ? ' disabled' : '' ;

        echo '<input id="' . $id. '" name="astrava_api_settings[' . $id . ']" class="regular-text code" type="text" value="' . $options[$id] . '"' . $disabled . '>';
    }

    function display_astrava_embed_type() {
        $options  = wp_parse_args( get_option('astrava_gen_settings'), array('astrava_embed_type' => 'iframe')); 
        ?>
        <select name="astrava_gen_settings[astrava_embed_type]">
            <option<?php if ($options['astrava_embed_type'] == 'iframe') echo " selected" ?>>iframe</option>
            <option<?php if ($options['astrava_embed_type'] == 'template') echo " selected" ?>>template</option>
        </select>
        <?php
    }

    function display_astrava_embed_units() {
        $options  = wp_parse_args( get_option('astrava_gen_settings'), array('astrava_embed_units' => 'i')); 
        ?>
        <select name="astrava_gen_settings[astrava_embed_units]">
            <option value="i"<?php if ($options['astrava_embed_units'] == 'i') echo " selected" ?>>Miles/Feet</option>
            <option value="m"<?php if ($options['astrava_embed_units'] == 'm') echo " selected" ?>>Meter/Kilometers</option>
        </select>
        <?php
    }


    function display_astrava_embed_pace() {
        $options  = wp_parse_args( get_option('astrava_gen_settings'), array('astrava_embed_pace' => '1')); 
        ?>
        <select name="astrava_gen_settings[astrava_embed_pace]">
            <option value="1"<?php if ($options['astrava_embed_pace'] == '1') echo " selected" ?>>Yes</option>
            <option value="0"<?php if ($options['astrava_embed_pace'] == '0') echo " selected" ?>>No</option>
        </select>
        <?php
    }

    function display_astrava_auto_create() {
        $options  = wp_parse_args(get_option('astrava_gen_settings'), array('display_astrava_auto_create' => 1)); 
        ?>
        <select name="astrava_gen_settings[display_astrava_auto_create]">
            <option<?php if ($options['display_astrava_auto_create'] == 1) echo " selected" ?>>Yes</option>
            <option<?php if ($options['display_astrava_auto_create'] == 0) echo " selected" ?>>No</option>
        </select>
        <?php
    }

    function display_astrava_auto_create_cat() {
        $options  = wp_parse_args(get_option('astrava_gen_settings'), array('astrava_auto_create_post_cat' => 0)); 
        wp_dropdown_categories(array('name'          => 'astrava_gen_settings[astrava_auto_create_post_cat]',
                                     'selected'      => $options['astrava_auto_create_post_cat'],
                                     'hide_empty'    => false,
                                     'hide_if_empty' => false));
    }

    function display_astrava_embed_template() {
        $options  = wp_parse_args(get_option('astrava_gen_settings'), array('astrava_embed_template' => ''));
        wp_editor($options['astrava_embed_template'], 'template_editor', 
                    array ('media_buttons' => false, 
                           'textarea_name' => 'astrava_gen_settings[astrava_embed_template]',
                           'textarea_rows' => 10)); 
        ?>
        <h3> Template tags </h3>
        <ul>
            <li>[distance] - Total distance of the activity
            <li>[description] - Description of the activity
            <li>[duration] - Duration of the activity
            <li>[photo] - The first photo attached to the activity
            <li>[map] - A google map of the route
            <li>[photoOrMap] - Display a photo if there is one otherwise display map
            <li>[max_hr] - Maximum heart rate
            <li>[avg_hr] - Average heart rate
            <li>[elevation] - Total elevation of the activity
            <li>[name] - Name of the activity
            <li>[speed] - Running pace or riding speed
            <li>[time] Start time of the activity
            <li>[type] Type of the activity
        </ul>
        <?php
    }

    function display_strava_connect_instructions() { 
        $id      = $args[0];
        $options = wp_parse_args( get_option( 'astrava_api_settings' )); 
        $authUrl = home_url() . '/wp-admin/options-general.php?page=astrava_admin&tab=api';

        ?>
        <ol>
            <li> Create an application at <a target="_new" href="http://www.strava.com/developers">http://www.strava.com/developers</a>. Make sure the redirect URL is set to the URL of your site.</li>
            <li> Enter the Client ID and Secret in the fields below.  After you save your settings, You will be presented with a button to authorize Strava.</li>
        </ol>
        <?php
        if (!empty($options['astrava_strava_client_id']) && !empty($options['astrava_strava_client_secret'])){
            
            $authCallbackUrl = site_url() . '/wp-admin/options-general.php?page=astrava_admin&tab=api';
            $strava          = new StravaApi($options['astrava_strava_client_id'], $options['astrava_strava_client_secret']);
            $authUrl         = $strava->getAuthorizeURL($authCallbackUrl);

        ?>
        <p><a href="<?php echo $authUrl ?>"><img src="<?php echo ASTRAVA_PLUGIN_URL; ?>assets/ConnectWithStrava.png"></a></p>
        <?php 
        } 
    }

    function display_astrava_admin_page() {

        $ctab = (isset($_GET['tab']) ? $_GET['tab'] : 'opts');

        ?>
        <div class="wrap">
        <h1> Custom Strava Integration </h1>
        
        <h2 class="nav-tab-wrapper">
            <a href="?page=astrava_admin&tab=opts" class="nav-tab <?php if ($ctab != 'api') echo 'nav-tab-active'; ?>">General</a>
            <a href="?page=astrava_admin&tab=api" class="nav-tab <?php if ($ctab == 'api') echo 'nav-tab-active'; ?>">Connect to Strava</a>
        </h2>
        
        <form action="options.php" method="post">
        <?php      

        if ($ctab == 'api') {
            settings_fields('astrava_api_settings');
            do_settings_sections('astrava_api_settings'); 
        } else {
            settings_fields('astrava_gen_settings');
            do_settings_sections('astrava_gen_settings');   
        }

        submit_button();
        ?>
        </form> 
        <?php
    }

    function display_astrava_admin_meta()
    {
        $options = wp_parse_args( get_option( 'astrava_api_settings' )); 
        $optionsUrl = home_url() . '/wp-admin/options-general.php?page=astrava_admin&tab=api';
        
        //Check to see if the options are avaialble
        if (!empty($options['astrava_strava_oauth'])){
            $strava          = new StravaApi($options['astrava_strava_client_id'], 
                                             $options['astrava_strava_client_secret'],
                                             $options['astrava_strava_oauth']);
            
            $activities = $strava->getActivities(array('per_page' => 5)); 
            ?>
            <?php if (count(activities) > 0):?>
                <div>
                    Click the link to insert this activity at the edit point.
                    <ul>
                        <?php foreach ($activities as $activity): ?>
                            <li>
                                <a class="astrava-editor-link" href="#" 
                                    data-activity-id="<?php echo $activity->id; ?>"><?php echo $activity->name; ?></a><br>
                                <em><?php echo $activity->type; ?> - <?php echo human_time_diff(strtotime($activity->start_date), time()); ?> ago</em>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <script type="text/javascript">

                 jQuery(".astrava-editor-link").click(function() {   
                        var shortcode = '[astrava activity="' + jQuery(this).data('activity-id') + '"]';
                        if (tinymce.activeEditor != null) {
                            tinymce.activeEditor.execCommand('mceInsertContent', false, shortcode); 
                        } else {
                            var currentPos = document.getElementById("content").selectionStart;
                            var textAreaCotents = jQuery("#content").val();

                            jQuery("#content").val(textAreaCotents.substring(0, currentPos) + shortcode + textAreaTxt.substring(currentPos));
                        }
                    });     
                </script>
            <?php else: ?>
                <div> No recent activities found.</div>  
            <?php endif; ?>
            <?php
        //  If the are get the last 10 activities and display them
        //      If no activities display error message
        } else { //If the plugin isn't configured, Display error message and link to configs.
            ?>
            <div> The Astrava Plugin isn't properly configured.  Please vist <a href="<?php echo $optionsUrl; ?>">the configuration page</a> and link your account.</div>
            <?php
        }
        
    
    }
	
?>