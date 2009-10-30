<?php
/**
Plugin Name: FB Share
Plugin URI: http://staynalive.com/fbshare
Description: Adds a Facebook Share button to your WordPress posts.
Author: Jesse Stay
Version: .1
Author URI: http://staynalive.com/
Text Domain: fbshare

=== RELEASE NOTES ===
2009-10-26 - v0.1 - Initial Release

Uses the Facebook Share button from Facebook - http://wiki.developers.facebook.com/index.php/Facebook_Share
Based on Sudar's "Easy Retweet" Plugin - http://sudarmuthu.com/wordpress/easy-retweet
*/

/**
 * FB Share Plugin Class
 */

$path = __FILE__;
if (!$path){$path=$_SERVER['PHP_SELF'];}
$current_directory = dirname($path);
$current_directory = str_replace('\\','/',$current_directory);
$current_directory = explode('/',$current_directory);
$current_directory = end($current_directory);
if(empty($current_directory) || !$current_directory)
	$current_directory = 'fbshare';
define('FBSHARE_FOLDER', $current_directory);
define('FBPATH', '/wp-content/plugins/'.FBSHARE_FOLDER.'/');

class FBShare {

    /**
     * Initalize the plugin by registering the hooks
     */
    function __construct() {

        // Load localization domain
        load_plugin_textdomain( 'fbshare', false, dirname(plugin_basename(__FILE__)) . '/languages' );

        // Register hooks
        add_action( 'admin_menu', array(&$this, 'register_settings_page') );
        add_action( 'admin_init', array(&$this, 'add_settings') );

        /* Use the admin_menu action to define the custom boxes */
        add_action('admin_menu', array(&$this, 'add_custom_box'));

        /* Use the save_post action to do something with the data entered */
        add_action('save_post', array(&$this, 'save_postdata'));

        // Enqueue the script
        add_action('template_redirect', array(&$this, 'add_script'));

        // Register filters
        add_filter('the_content', array(&$this, 'append_fbshare_button') , 99);

        // register short code
        add_shortcode('fbshare', array(&$this, 'shortcode_handler'));

        $plugin = plugin_basename(__FILE__);
        add_filter("plugin_action_links_$plugin", array(&$this, 'add_action_links'));

    }

    /**
     * Register the settings page
     */
    function register_settings_page() {
        add_options_page( __('FB Share', 'fbshare'), __('FB Share', 'fbshare'), 8, 'fbshare', array(&$this, 'settings_page') );
    }

    /**
     * add options
     */
    function add_settings() {
        // Register options
        register_setting( 'fbshare', 'fbshare-style');
    }

    /**
     * Enqueue the FBShare script
     */
    function add_script() {
        // Enqueue the script
        wp_enqueue_script('fbshare', 'http://static.ak.fbcdn.net/connect.php/js/FB.Share');
    }

    /**
     * Adds the custom section in the Post and Page edit screens
     */
    function add_custom_box() {

        add_meta_box( 'fbshare_enable_button', __( 'FB Share Button', 'fbshare' ),
                    array(&$this, 'inner_custom_box'), 'post', 'side' );
        add_meta_box( 'fbshare_enable_button', __( 'FB Share Button', 'fbshare' ),
                    array(&$this, 'inner_custom_box'), 'page', 'side' );
    }

    /**
     * Prints the inner fields for the custom post/page section
     */
    function inner_custom_box() {
        global $post;
        $post_id = $post->ID;
        
        $option_value = '';
        
        if ($post_id > 0) {
            $enable_fbshare = get_post_meta($post->ID, 'enable_fbshare_button', true);
            if ($enable_fbshare != '') {
                $option_value = $enable_fbshare;
            }
        }
        // Use nonce for verification
?>
        <input type="hidden" name="fbshare_noncename" id="fbshare_noncename" value="<?php echo wp_create_nonce( plugin_basename(__FILE__) );?>" />

        <label><input type="radio" name="fbshare_button" value ="1" <?php checked('1', $option_value); ?> /> <?php _e('Enabled', 'fbshare'); ?></label>
        <label><input type="radio" name="fbshare_button" value ="0"  <?php checked('0', $option_value); ?> /> <?php _e('Disabled', 'fbshare'); ?></label>
<?php
    }

    /**
     * When the post is saved, saves our custom data
     * @param string $post_id
     * @return string return post id if nothing is saved
     */
    function save_postdata( $post_id ) {

        // verify this came from the our screen and with proper authorization,
        // because save_post can be triggered at other times

        if ( !wp_verify_nonce( $_POST['fbshare_noncename'], plugin_basename(__FILE__) )) {
            return $post_id;
        }

        if ( 'page' == $_POST['post_type'] ) {
            if ( !current_user_can( 'edit_page', $post_id ))
                return $post_id;
        } else {
            if ( !current_user_can( 'edit_post', $post_id ))
                return $post_id;
        }

        // OK, we're authenticated: we need to find and save the data

        if (isset($_POST['fbshare_button'])) {
            $choice = $_POST['fbshare_button'];
            $choice = ($choice == '1')? '1' : '0';
            update_post_meta($post_id, 'enable_fbshare_button', $choice);
        }
    }

    /**
     * hook to add action links
     * @param <type> $links
     * @return <type>
     */
    function add_action_links( $links ) {
        // Add a link to this plugin's settings page
        $settings_link = '<a href="options-general.php?page=fbshare">' . __("Settings", 'fbshare') . '</a>';
        array_unshift( $links, $settings_link );
        return $links;
    }

    /**
     * Adds Footer links. Based on http://striderweb.com/nerdaphernalia/2008/06/give-your-wordpress-plugin-credit/
     */
    function add_footer_links() {
        $plugin_data = get_plugin_data( __FILE__ );
        printf('%1$s ' . __("plugin", 'fbshare') .' | ' . __("Version", 'fbshare') . ' %2$s | '. __('by', 'fbshare') . ' %3$s<br />', $plugin_data['Title'], $plugin_data['Version'], $plugin_data['Author']);
    }

    /**
     * Dipslay the Settings page
     */
    function settings_page() {
?>
        <div class="wrap">
            <?php screen_icon(); ?>
            <h2><?php _e( 'FB Share Settings', 'fbshare' ); ?></h2>

            <form id="smer_form" method="post" action="options.php">
                <?php settings_fields('fbshare'); ?>
                <?php $options = get_option('fbshare-style'); ?>
                <?php $options['username'] = ($options['username'] == "")? "fbsharejs" : $options['username'];?>
                <?php $options['align'] = ($options['align'] == "")? "box_count":$options['align'];?>
                <?php $options['position'] = ($options['position'] == "")? "before":$options['position'];?>
                <?php $options['text'] = ($options['text'] == "")? "Share":$options['text'];?>

                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php _e( 'Display', 'fbshare' ); ?></th>
                        <td>
                            <p><label><input type="checkbox" name="fbshare-style[display-page]" value="1" <?php checked("1", $options['display-page']); ?> /> <?php _e("Display the button on pages", 'fbshare');?></label></p>
                            <p><label><input type="checkbox" name="fbshare-style[display-archive]" value="1" <?php checked("1", $options['display-archive']); ?> /> <?php _e("Display the button on archive pages", 'fbshare');?></label></p>
                            <p><label><input type="checkbox" name="fbshare-style[display-home]" value="1" <?php checked("1", $options['display-home']); ?> /> <?php _e("Display the button in home page", 'fbshare');?></label></p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e( 'Position', 'fbshare' ); ?></th>
                        <td>
                            <p><label><input type="radio" name="fbshare-style[position]" value="before" <?php checked("before", $options['position']); ?> /> <?php _e("Before the content of your post", 'fbshare');?></label></p>
                            <p><label><input type="radio" name="fbshare-style[position]" value="after" <?php checked("after", $options['position']); ?> /> <?php _e("After the content of your post", 'fbshare');?></label></p>
                            <p><label><input type="radio" name="fbshare-style[position]" value="both" <?php checked("both", $options['position']); ?> /> <?php _e("Before AND After the content of your post", 'fbshare');?></label></p>
                            <p><label><input type="radio" name="fbshare-style[position]" value="manual" <?php checked("manual", $options['position']); ?> /> <?php _e("Manually call the fbshare button", 'fbshare');?></label></p>
                            <p><?php _e("You can manually call the <code>fbshare_button</code> function. E.g. <code>if (function_exists('fbshare_button')) echo fbshare_button();.", 'fbshare'); ?></p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e( 'Type', 'fbshare' ); ?></th>
                        <td>
                            <p><label><input type="radio" name="fbshare-style[align]" value="box_count" <?php checked("box_count", $options['align']); ?> /> <img src ="<?php echo FBPATH ?>images/box_count.png" /> (<?php _e("box_count button", 'fbshare');?>)</label></p>
                            <p><label><input type="radio" name="fbshare-style[align]" value="button_count" <?php checked("button_count", $options['align']); ?> /> <img src ="<?php echo FBPATH ?>images/button_count.png" /> (<?php _e("button_count button", 'fbshare');?>)</label></p>
                            <p><label><input type="radio" name="fbshare-style[align]" value="button" <?php checked("button", $options['align']); ?> /> <img src ="<?php echo FBPATH ?>images/button.png" /> (<?php _e("button button", 'fbshare');?>)</label></p>
                            <p><label><input type="radio" name="fbshare-style[align]" value="icon_link" <?php checked("icon_link", $options['align']); ?> /> <img src ="<?php echo FBPATH ?>images/icon_link.png" /> (<?php _e("icon_link button", 'fbshare');?>)</label></p>
                            <p><label><input type="radio" name="fbshare-style[align]" value="icon" <?php checked("icon", $options['align']); ?> /> <img src ="<?php echo FBPATH ?>images/icon.png" /> (<?php _e("icon button", 'fbshare');?>)</label></p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e( 'Text', 'fbshare' ); ?></th>
                        <td>
                            <p><label><input type="text" name="fbshare-style[text]" value="<?php echo $options['text']; ?>" /></label></p>
                            <p><?php _e("The text that you enter here will be displayed in the button.", 'fbshare');?></p>
                        </td>
                    </tr>

                </table>

                <p class="submit">
                    <input type="submit" name="fbshare-submit" class="button-primary" value="<?php _e('Save Changes', 'fbshare') ?>" />
                </p>
            </form>
        </div>
<?php
        // Display credits in Footer
        add_action( 'in_admin_footer', array(&$this, 'add_footer_links'));
    }

    /**
     * Append the fbshare_button
     * 
     * @global object $post Current post
     * @param string $content Post content
     * @return string modifiyed content
     */
    function append_fbshare_button($content) {

        global $post;
        $options = get_option('fbshare-style');

        $enable_fbshare = get_post_meta($post->ID, 'enable_fbshare_button', true);

        if ($enable_fbshare != "") {
            // if option per post/page is set
            if ($enable_fbshare == "1") {
                // FBShare button is enabled

                $content = $this->build_fbshare_button($content, $options['position']);

            } elseif ($enable_fbshare == "0") {
                // FBShare button is disabled
                // Do nothing
            }

        } else {
            //Option per post/page is not set
            if (is_single()
                || ($options['display-page'] == "1" && is_page())
                || ($options['display-archive'] == "1" && is_archive())
                || ($options['display-home'] == "1" && is_home())) {

                $content = $this->build_fbshare_button($content, $options['position']);
            }
        }
        return $content;
    }

    /**
     * Helper function for append_fbshare_button
     *
     * @param string $content The post content
     * @param string $position Position of the button
     * @return string Modifiyed content
     */
    function build_fbshare_button($content, $position) {
        $button = fbshare_button(false);

        switch ($position) {
            case "before":
                $content = $button . $content;
            break;
            case "after":
                $content = $content . $button;
            break;
            case "both":
                $content = $button . $content . $button;
            break;
            case "manual":
            default:
                // nothing to do
            break;
        }
        return $content;
    }

    /**
     * Short code handler
     * @param <type> $attr
     * @param <type> $content 
     */
    function shortcode_handler($attr, $content) {
        return fbshare_button(false);
    }

    // PHP4 compatibility
    function FBShare() {
            $this->__construct();
    }
}

// Start this plugin once all other plugins are fully loaded
add_action( 'init', 'FBShare' ); function FBShare() { global $FBShare; $FBShare = new FBShare(); }

/**
 * Template function to add the fbshre button
 */
function fbshare_button($display = true) {
    global $wp_query;
    $post = $wp_query->post;
    $permalink = get_permalink($post->ID);
    $title = get_the_title($post->ID);

    $options = get_option('fbshare-style');
    $align = $options['align'] || "";
    $text = $options['text'] || "Share";

    $output = '<a name="fb_share" type="'.$options['align'].'" share_url="'.$permalink.'">'.$options['text'].'</a>';
    
    if ($display) {
        echo $output;
    } else {
        return $output;
    }
}


?>
