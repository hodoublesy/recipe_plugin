<?php
/*
Plugin Name: ZipList Recipe Plugin
Plugin URI: http://www.ziplist.com/recipe_plugin
Plugin GitHub: https://github.com/Ziplist/recipe_plugin
Description: A plugin that adds all the necessary microdata to your recipes, so they will show up in Google's Recipe Search
Version: 1.3
Author: ZipList.com
Author URI: http://www.ziplist.com/
License: GPLv2 or later

This code is derived from the 1.3.1 build of RecipeSEO released by codeswan: http://sushiday.com/recipe-seo-plugin/
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
	echo "Hey!  This is just a plugin, not much it can do when called directly.";
	exit;
}

if (!defined('AMD_ZLRECIPE_VERSION_KEY'))
    define('AMD_ZLRECIPE_VERSION_KEY', 'amd_zlrecipe_version');

if (!defined('AMD_ZLRECIPE_VERSION_NUM'))
    define('AMD_ZLRECIPE_VERSION_NUM', '1.3'); //!!mwp
    
if (!defined('AMD_ZLRECIPE_PLUGIN_DIRECTORY'))
    define('AMD_ZLRECIPE_PLUGIN_DIRECTORY', get_option('siteurl') . '/wp-content/plugins/' . dirname(plugin_basename(__FILE__)) . '/');

add_option(AMD_ZLRECIPE_VERSION_KEY, AMD_ZLRECIPE_VERSION_NUM);

add_option('ziplist_partner_key', ''); //!!mwp
add_option('ziplist_recipe_button_hide', ''); //!!mwp
add_option('ziplist_attribution_hide', ''); //!!mwp
add_option('zlrecipe_printed_permalink_hide', ''); //!!mwp
add_option('zlrecipe_printed_copyright_statement', ''); //!!mwp
add_option('zlrecipe_stylesheet', 'zlrecipe-std'); //!!dc
add_option('recipe_title_hide', ''); //!!dc (oops, btw)
add_option('zlrecipe_print_link_hide', ''); //!!dc
add_option('zlrecipe_ingredient_label', 'Ingredients');
add_option('zlrecipe_ingredient_label_hide', '');
add_option('zlrecipe_ingredient_list_type', 'ul');
add_option('zlrecipe_instruction_label', 'Instructions'); //!!mwp
add_option('zlrecipe_instruction_label_hide', '');
add_option('zlrecipe_instruction_list_type', 'ol');
add_option('zlrecipe_prep_time_label', 'Prep Time:');
add_option('zlrecipe_prep_time_label_hide', '');
add_option('zlrecipe_cook_time_label', 'Cook Time:');
add_option('zlrecipe_cook_time_label_hide', '');
add_option('zlrecipe_total_time_label', 'Total Time:');
add_option('zlrecipe_total_time_label_hide', '');
add_option('zlrecipe_yield_label', 'Yield:');
add_option('zlrecipe_yield_label_hide', '');
add_option('zlrecipe_serving_size_label', 'Serving Size:');
add_option('zlrecipe_serving_size_label_hide', '');
add_option('zlrecipe_calories_label', 'Calories per serving:');
add_option('zlrecipe_calories_label_hide', '');
add_option('zlrecipe_fat_label', 'Fat per serving:');
add_option('zlrecipe_fat_label_hide', '');
add_option('zlrecipe_rating_label', 'Rating:'); //!!dc
add_option('zlrecipe_rating_label_hide', ''); //!!dc
add_option('zlrecipe_image_width', ''); //!!dc
add_option('zlrecipe_outer_border_style', ''); //!!dc


register_activation_hook(__FILE__, 'amd_zlrecipe_install');

add_action('media_buttons', 'amd_zlrecipe_add_recipe_button', 30);
add_action('init', 'amd_zlrecipe_enhance_mce');

if (strpos($_SERVER['REQUEST_URI'], 'media-upload.php') && strpos($_SERVER['REQUEST_URI'], '&type=amd_zlrecipe') && !strpos($_SERVER['REQUEST_URI'], '&wrt='))
{
	amd_zlrecipe_iframe_content($_POST, $_REQUEST);
	exit;
}

// Creates ZLRecipe tables in the db if they don't exist already.
function amd_zlrecipe_install() {
    global $wpdb;

    $recipes_table = $wpdb->prefix . "amd_zlrecipe_recipes";
    
    if($wpdb->get_var("SHOW TABLES LIKE '$recipes_table'") != $recipes_table) {
        $sql = "CREATE TABLE " . $recipes_table . " (
            recipe_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            post_id BIGINT(20) UNSIGNED NOT NULL,
            recipe_title TEXT,
            recipe_image TEXT,
            summary TEXT,
            rating TEXT,
            prep_time TEXT,
            cook_time TEXT,
            total_time TEXT,
            yield TEXT,
            serving_size VARCHAR(50),
            calories VARCHAR(50),
            fat VARCHAR(50),
            ingredients TEXT,
            instructions TEXT,
            created_at TIMESTAMP DEFAULT NOW()
        	);";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
   
 /*!!mwp
    $ingredients_table = $wpdb->prefix . "amd_zlrecipe_ingredients";
    
    if($wpdb->get_var("SHOW TABLES LIKE '$ingredients_table'") != $ingredients_table) {
        $sql_2 = "CREATE TABLE " . $ingredients_table . " (
            ingredient_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            recipe_id BIGINT(20) UNSIGNED NOT NULL,
            name VARCHAR(200) NOT NULL,
            amount VARCHAR(200),
            created_at TIMESTAMP DEFAULT NOW()
        	);";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_2);
    }
 */
 
    add_option("amd_zlrecipe_db_version", "3.0"); //!!mwp
}

add_action('admin_menu', 'amd_zlrecipe_menu_pages');

// Adds module to left sidebar in wp-admin for ZLRecipe
function amd_zlrecipe_menu_pages() {
    // Add the top-level admin menu
    $page_title = 'ZipList Recipe Plugin Settings';
    $menu_title = 'ZipList Recipe Plugin';
    $capability = 'manage_options';
    $menu_slug = 'zlrecipe-settings';
    $function = 'amd_zlrecipe_settings';
    add_menu_page($page_title, $menu_title, $capability, $menu_slug, $function);

    // Add submenu page with same slug as parent to ensure no duplicates
    $settings_title = 'Settings';
    add_submenu_page($menu_slug, $page_title, $settings_title, $capability, $menu_slug, $function);
}

// Adds 'Settings' page to the ZLRecipe module
function amd_zlrecipe_settings() {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }
    
    $zlrecipe_icon = AMD_ZLRECIPE_PLUGIN_DIRECTORY . "zlrecipe.gif";
    
    if ($_POST['ingredient-list-type']) {
    	$ziplist_partner_key = $_POST['ziplist-partner-key'];
        $ziplist_recipe_button_hide = $_POST['ziplist-recipe-button-hide'];
        $ziplist_attribution_hide = $_POST['ziplist-attribution-hide'];
        $printed_permalink_hide = $_POST['printed-permalink-hide'];
        $printed_copyright_statement = $_POST['printed-copyright-statement'];
        $stylesheet = $_POST['stylesheet'];
        $recipe_title_hide = $_POST['recipe-title-hide'];
        $print_link_hide = $_POST['print-link-hide'];
        $ingredient_label = $_POST['ingredient-label'];
        $ingredient_label_hide = $_POST['ingredient-label-hide'];
        $ingredient_list_type = $_POST['ingredient-list-type'];
        $instruction_label = $_POST['instruction-label'];
        $instruction_label_hide = $_POST['instruction-label-hide'];
        $instruction_list_type = $_POST['instruction-list-type'];
        $prep_time_label = $_POST['prep-time-label'];
        $prep_time_label_hide = $_POST['prep-time-label-hide'];
        $cook_time_label = $_POST['cook-time-label'];
        $cook_time_label_hide = $_POST['cook-time-label-hide'];
        $total_time_label = $_POST['total-time-label'];
        $total_time_label_hide = $_POST['total-time-label-hide'];
        $yield_label = $_POST['yield-label'];
        $yield_label_hide = $_POST['yield-label-hide'];
        $serving_size_label = $_POST['serving-size-label'];
        $serving_size_label_hide = $_POST['serving-size-label-hide'];
        $calories_label = $_POST['calories-label'];
        $calories_label_hide = $_POST['calories-label-hide'];
        $fat_label = $_POST['fat-label'];
        $fat_label_hide = $_POST['fat-label-hide'];
        $rating_label = $_POST['rating-label'];
        $rating_label_hide = $_POST['rating-label-hide'];
        $image_width = $_POST['image-width'];
        $outer_border_style = $_POST['outer-border-style'];

        
        update_option('ziplist_partner_key', $ziplist_partner_key);
        update_option('ziplist_recipe_button_hide', $ziplist_recipe_button_hide);
        update_option('ziplist_attribution_hide', $ziplist_attribution_hide);
        update_option('zlrecipe_printed_permalink_hide', $printed_permalink_hide );
        update_option('zlrecipe_printed_copyright_statement', $printed_copyright_statement);
        update_option('zlrecipe_stylesheet', $stylesheet);
        update_option('recipe_title_hide', $recipe_title_hide);
        update_option('zlrecipe_print_link_hide', $print_link_hide);
        update_option('zlrecipe_ingredient_label', $ingredient_label);
        update_option('zlrecipe_ingredient_label_hide', $ingredient_label_hide);
        update_option('zlrecipe_ingredient_list_type', $ingredient_list_type);
        update_option('zlrecipe_instruction_label', $instruction_label);
        update_option('zlrecipe_instruction_label_hide', $instruction_label_hide);
        update_option('zlrecipe_instruction_list_type', $instruction_list_type);
        update_option('zlrecipe_prep_time_label', $prep_time_label);
        update_option('zlrecipe_prep_time_label_hide', $prep_time_label_hide);
        update_option('zlrecipe_cook_time_label', $cook_time_label);
        update_option('zlrecipe_cook_time_label_hide', $cook_time_label_hide);
        update_option('zlrecipe_total_time_label', $total_time_label);
        update_option('zlrecipe_total_time_label_hide', $total_time_label_hide);
        update_option('zlrecipe_yield_label', $yield_label);
        update_option('zlrecipe_yield_label_hide', $yield_label_hide);
        update_option('zlrecipe_serving_size_label', $serving_size_label);
        update_option('zlrecipe_serving_size_label_hide', $serving_size_label_hide);
        update_option('zlrecipe_calories_label', $calories_label);
        update_option('zlrecipe_calories_label_hide', $calories_label_hide);
        update_option('zlrecipe_fat_label', $fat_label);
        update_option('zlrecipe_fat_label_hide', $fat_label_hide);
        update_option('zlrecipe_rating_label', $rating_label);
        update_option('zlrecipe_rating_label_hide', $rating_label_hide);
        update_option('zlrecipe_image_width', $image_width);
        update_option('zlrecipe_outer_border_style', $outer_border_style);
    } else {
        $ziplist_partner_key = get_option('ziplist_partner_key');
        $ziplist_recipe_button_hide = get_option('ziplist_recipe_button_hide');
        $ziplist_attribution_hide = get_option('ziplist_attribution_hide');
        $printed_permalink_hide = get_option('zlrecipe_printed_permalink_hide');
        $printed_copyright_statement = get_option('zlrecipe_printed_copyright_statement');
        $stylesheet = get_option('zlrecipe_stylesheet');
        $recipe_title_hide = get_option('recipe_title_hide');
        $print_link_hide = get_option('zlrecipe_print_link_hide');
        $ingredient_label = get_option('zlrecipe_ingredient_label');
        $ingredient_label_hide = get_option('zlrecipe_ingredient_label_hide');
        $ingredient_list_type = get_option('zlrecipe_ingredient_list_type');
        $instruction_label = get_option('zlrecipe_instruction_label');
        $instruction_label_hide = get_option('zlrecipe_instruction_label_hide');
        $instruction_list_type = get_option('zlrecipe_instruction_list_type');
        $prep_time_label = get_option('zlrecipe_prep_time_label');
        $prep_time_label_hide = get_option('zlrecipe_prep_time_label_hide');
        $cook_time_label = get_option('zlrecipe_cook_time_label');
        $cook_time_label_hide = get_option('zlrecipe_cook_time_label_hide');
        $total_time_label = get_option('zlrecipe_total_time_label');
        $total_time_label_hide = get_option('zlrecipe_total_time_label_hide');
        $yield_label = get_option('zlrecipe_yield_label');
        $yield_label_hide = get_option('zlrecipe_yield_label_hide');
        $serving_size_label = get_option('zlrecipe_serving_size_label');
        $serving_size_label_hide = get_option('zlrecipe_serving_size_label_hide');
        $calories_label = get_option('zlrecipe_calories_label');
        $calories_label_hide = get_option('zlrecipe_calories_label_hide');
        $fat_label = get_option('zlrecipe_fat_label');
        $fat_label_hide = get_option('zlrecipe_fat_label_hide');
        $rating_label = get_option('zlrecipe_rating_label');
        $rating_label_hide = get_option('zlrecipe_rating_label_hide');
        $image_width = get_option('zlrecipe_image_width');
        $outer_border_style = get_option('zlrecipe_outer_border_style');
    }
    
    $ziplist_recipe_button_hide = (strcmp($ziplist_recipe_button_hide, 'Hide') == 0 ? 'checked="checked"' : '');
    $ziplist_attribution_hide = (strcmp($ziplist_attribution_hide, 'Hide') == 0 ? 'checked="checked"' : '');
    $printed_permalink_hide = (strcmp($printed_permalink_hide, 'Hide') == 0 ? 'checked="checked"' : '');
    $recipe_title_hide = (strcmp($recipe_title_hide, 'Hide') == 0 ? 'checked="checked"' : '');
    $print_link_hide = (strcmp($print_link_hide, 'Hide') == 0 ? 'checked="checked"' : '');

    // Stylesheet processing
    $stylesheet = (strcmp($stylesheet, 'zlrecipe-std') == 0 ? 'checked="checked"' : '');

    // Outer (hrecipe) border style
	$obs = '';
	$borders = array('None' => '', 'Solid' => '1px solid', 'Dotted' => '1px dotted', 'Dashed' => '1px dashed', 'Thick Solid' => '2px solid', 'Double' => 'double');
	foreach ($borders as $label => $code) {
		$obs .= '<option value="' . $code . '" ' . (strcmp($outer_border_style, $code) == 0 ? 'selected="true"' : '') . '>' . $label . '</option>';
	}

    $ingredient_label_hide = (strcmp($ingredient_label_hide, 'Hide') == 0 ? 'checked="checked"' : '');
    
    $ing_ul = (strcmp($ingredient_list_type, 'ul') == 0 ? 'checked="checked"' : '');
    $ing_ol = (strcmp($ingredient_list_type, 'ol') == 0 ? 'checked="checked"' : '');
    $ing_p = (strcmp($ingredient_list_type, 'p') == 0 ? 'checked="checked"' : '');
    $ing_div = (strcmp($ingredient_list_type, 'div') == 0 ? 'checked="checked"' : '');
    
    $instruction_label_hide = (strcmp($instruction_label_hide, 'Hide') == 0 ? 'checked="checked"' : '');
    
    $ins_ul = (strcmp($instruction_list_type, 'ul') == 0 ? 'checked="checked"' : '');
    $ins_ol = (strcmp($instruction_list_type, 'ol') == 0 ? 'checked="checked"' : '');
    $ins_p = (strcmp($instruction_list_type, 'p') == 0 ? 'checked="checked"' : '');
    $ins_div = (strcmp($instruction_list_type, 'div') == 0 ? 'checked="checked"' : '');
    
    $prep_time_label_hide = (strcmp($prep_time_label_hide, 'Hide') == 0 ? 'checked="checked"' : '');
    $cook_time_label_hide = (strcmp($cook_time_label_hide, 'Hide') == 0 ? 'checked="checked"' : '');
    $total_time_label_hide = (strcmp($total_time_label_hide, 'Hide') == 0 ? 'checked="checked"' : '');
    $yield_label_hide = (strcmp($yield_label_hide, 'Hide') == 0 ? 'checked="checked"' : '');
    $serving_size_label_hide = (strcmp($serving_size_label_hide, 'Hide') == 0 ? 'checked="checked"' : '');
    $calories_label_hide = (strcmp($calories_label_hide, 'Hide') == 0 ? 'checked="checked"' : '');
    $fat_label_hide = (strcmp($fat_label_hide, 'Hide') == 0 ? 'checked="checked"' : '');
    $rating_label_hide = (strcmp($rating_label_hide, 'Hide') == 0 ? 'checked="checked"' : '');
    
    $other_options = '';
    $other_options_array = array('Rating', 'Prep Time', 'Cook Time', 'Total Time', 'Yield', 'Serving Size', 'Calories', 'Fat');
    
    foreach ($other_options_array as $option) {
        $name = strtolower(str_replace(' ', '-', $option));
        $value = strtolower(str_replace(' ', '_', $option)) . '_label';
        $value_hide = strtolower(str_replace(' ', '_', $option)) . '_label_hide';
        $other_options .= '<tr valign="top">
            <th scope="row">\'' . $option . '\' Label</th>
            <td><input type="text" name="' . $name . '-label" value="' . ${$value} . '" class="regular-text" /><br />
            <label><input type="checkbox" name="' . $name . '-label-hide" value="Hide" ' . ${$value_hide} . ' /> Don\'t show ' . $option . ' label</label></td>
        </tr>';
    }

    echo '<style>
        .form-table label { line-height: 2.5; }
        hr { border: 1px solid #DDD; border-left: none; border-right: none; border-bottom: none; margin: 30px 0; }
    </style>
    <div class="wrap">
        <form enctype="multipart/form-data" method="post" action="" name="zlrecipe_settings_form">
            <h2><img src="' . $zlrecipe_icon . '" /> ZipList Recipe Plugin Settings</h2>
            For full customization options, see the <a href="http://marketing.ziplist.com.s3.amazonaws.com/plugin_instructions.pdf" target="_blank">Instructions document</a>.
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Partner Key</th>
                    <td>
                        <input type="text" name="ziplist-partner-key" value="' . $ziplist_partner_key . '" class="regular-text" />
                        <br />
                        <a href="mailto:plugins@ziplist.com?Subject=Partner%20Key%20Request&body=Please%20send%20me%20a%20partner%20key%20for%20this%20awesome%20ZipList%20Recipe%20Plugin!" target="_blank">
                            Request a partner key now
                        </a>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">ZipList Recipe Box and Shopping List</th>
                    <td><label><input type="checkbox" name="ziplist-recipe-button-hide" value="Hide" ' . $ziplist_recipe_button_hide . ' /> Don\'t enable these features</label></td>
                </tr>
                <tr valign="top">
                    <th scope="row">ZipList Recipe Plugin Link</th>
                    <td><label><input type="checkbox" name="ziplist-attribution-hide" value="Hide" ' . $ziplist_attribution_hide . ' /> Don\'t show plugin link</label></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Printed Output: Recipe Permalink</th>
                    <td><label><input type="checkbox" name="printed-permalink-hide" value="Hide" ' . $printed_permalink_hide . ' /> Don\'t show permalink in printed output</label></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Printed Output: Copyright Statement</th>
                    <td><input type="text" name="printed-copyright-statement" value="' . $printed_copyright_statement . '" class="regular-text" /></td>
                </tr>
            </table>
            
            <hr />
			<h3>General</h3>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Recipe Title</th>
                    <td><label><input type="checkbox" name="recipe-title-hide" value="Hide" ' . $recipe_title_hide . ' /> Don\'t show Recipe Title</label></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Print Button</th>
                    <td><label><input type="checkbox" name="print-link-hide" value="Hide" ' . $print_link_hide . ' /> Don\'t show Print Button</label></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Image Width</th>
                    <td><label><input type="text" name="image-width" value="' . $image_width . '" class="regular-text" /> pixels</label></td>
                </tr>
                <tr valign="top">
                	<th scope="row">Border Style</th>
                	<td>
						<select name="outer-border-style">' . $obs . '</select>
					</td>
				</tr>
                <tr valign="top">
                    <th scope="row">Stylesheet</th>
                    <td><label><input type="checkbox" name="stylesheet" value="zlrecipe-std" ' . $stylesheet . ' /> Use ZipList recipe style</label></td>
                </tr>
            </table>
            <hr />            
            <h3>Ingredients</h3>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">\'Ingredients\' Label</th>
                    <td><input type="text" name="ingredient-label" value="' . $ingredient_label . '" class="regular-text" /><br />
                    <label><input type="checkbox" name="ingredient-label-hide" value="Hide" ' . $ingredient_label_hide . ' /> Don\'t show Ingredients label</label></td>
                </tr>
                <tr valign="top">
                    <th scope="row">\'Ingredients\' List Type</th>
                    <td><input type="radio" name="ingredient-list-type" value="ul" ' . $ing_ul . ' /> <label>Bulleted List</label><br />
                    <input type="radio" name="ingredient-list-type" value="ol" ' . $ing_ol . ' /> <label>Numbered List</label><br />
                    <input type="radio" name="ingredient-list-type" value="p" ' . $ing_p . ' /> <label>Paragraphs</label><br />
                    <input type="radio" name="ingredient-list-type" value="div" ' . $ing_div . ' /> <label>Divs</label></td>
                </tr>
            </table>
            
            <hr />
            
            <h3>Instructions</h3>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">\'Instructions\' Label</th>
                    <td><input type="text" name="instruction-label" value="' . $instruction_label . '" class="regular-text" /><br />
                    <label><input type="checkbox" name="instruction-label-hide" value="Hide" ' . $instruction_label_hide . ' /> Don\'t show Instructions label</label></td>
                </tr>
                <tr valign="top">
                    <th scope="row">\'Instructions\' List Type</th>
                    <td><input type="radio" name="instruction-list-type" value="ol" ' . $ins_ol . ' /> <label>Numbered List</label><br />
                    <input type="radio" name="instruction-list-type" value="ul" ' . $ins_ul . ' /> <label>Bulleted List</label><br />
                    <input type="radio" name="instruction-list-type" value="p" ' . $ins_p . ' /> <label>Paragraphs</label><br />
                    <input type="radio" name="instruction-list-type" value="div" ' . $ins_div . ' /> <label>Divs</label></td>
                </tr>
            </table>
            
            <hr />
            
            <h3>Other Options</h3>
            <table class="form-table">
                ' . $other_options . '
            </table>
            
            <p><input type="submit" name="submit" id="submit" class="button-primary" value="Save Changes"></p>
        </form>
    </div>';
}

function amd_zlrecipe_enhance_mce() {
    if ( ! current_user_can('edit_posts') && ! current_user_can('edit_pages') )
        return;
    if ( get_user_option('rich_editing') == 'true') {
        add_filter('mce_external_plugins', 'amd_zlrecipe_tinymce_plugin');
    }
}

function amd_zlrecipe_tinymce_plugin($plugin_array) {
    
   $plugin_array['amdzlrecipe'] =  get_option('siteurl') . '/wp-content/plugins/' . dirname(plugin_basename(__FILE__)) . '/zlrecipe_editor_plugin.js';
   
   return $plugin_array;
}

// Adds  the recipe button to the editor in the media row
function amd_zlrecipe_add_recipe_button() {
    global $post_ID, $temp_ID;
	$uploading_iframe_ID = (int) (0 == $post_ID ? $temp_ID : $post_ID);

	$media_upload_iframe_src = get_option('siteurl').'/wp-admin/media-upload.php?post_id='.$uploading_iframe_ID;

	$media_amd_zlrecipe_iframe_src = apply_filters('media_amd_zlrecipe_iframe_src', "$media_upload_iframe_src&amp;type=amd_zlrecipe&amp;tab=amd_zlrecipe");
	$media_amd_zlrecipe_title = __('Add a Recipe', 'wp-media-amd_zlrecipe');

	echo "<a class=\"thickbox\" href=\"{$media_amd_zlrecipe_iframe_src}&amp;TB_iframe=true&amp;height=500&amp;width=640\" title=\"$media_amd_zlrecipe_title\"><img src='" . get_option('siteurl').'/wp-content/plugins/'.dirname(plugin_basename(__FILE__)) . "/zlrecipe.gif?ver=1.0' alt='ZLRecipe Icon' /></a>";
}

function amd_zlrecipe_strip_chars( $val )
{
	return str_replace( '\\', '', $val );
}

// Content for the popup iframe when creating or editing a recipe
function amd_zlrecipe_iframe_content($post_info = null, $get_info = null) {
    $recipe_id = 0;
    //!!mwp $iframe_title = "Add a Recipe";
    //!!mwp $submit = "Add Recipe";
    if ($post_info || $get_info) {
    
    	//!!mwp debug titling for error handled dialog 
    	if( $get_info["add-recipe-button"] || strpos($get_info["post_id"], '-') !== false ) {
        	$iframe_title = "Update Your Recipe"; 
        	$submit = "Update Recipe";
        } else {
    		$iframe_title = "Add a Recipe";
    		$submit = "Add Recipe";
        }
		
        if ($get_info["post_id"] && !$get_info["add-recipe-button"] && strpos($get_info["post_id"], '-') !== false) {
            $recipe_id = preg_replace('/[0-9]*?\-/i', '', $get_info["post_id"]);
            $recipe = amd_zlrecipe_select_recipe_db($recipe_id);
            //!!mwp $ingredients_list = amd_zlrecipe_select_ingredients_db($recipe_id);
            
            $recipe_title = $recipe->recipe_title; //!!xxx amd_zlrecipe_strip_chars( $recipe->recipe_title );
            $recipe_image = $recipe->recipe_image;
            $summary = $recipe->summary; //!!xxx amd_zlrecipe_strip_chars( $recipe->summary );
            $rating = $recipe->rating;
            $ss = array();
            $ss[(int)$rating] = 'selected="true"';
            
            $prep_time_input = '';
            $cook_time_input = '';
            $total_time_input = '';
            if (class_exists('DateInterval')) {
                try {
                    $prep_time = new DateInterval($recipe->prep_time);
                    $prep_time_seconds = $prep_time->s;
                    $prep_time_minutes = $prep_time->i;
                    $prep_time_hours = $prep_time->h;
                    $prep_time_days = $prep_time->d;
                    $prep_time_months = $prep_time->m;
                    $prep_time_years = $prep_time->y;
                } catch (Exception $e) {
                    if ($recipe->prep_time != null) {
                        $prep_time_input = '<input type="text" name="prep_time" value="' . $recipe->prep_time . '"/>';
                    }
                }

                try {
                    $cook_time = new DateInterval($recipe->cook_time);
                    $cook_time_seconds = $cook_time->s;
                    $cook_time_minutes = $cook_time->i;
                    $cook_time_hours = $cook_time->h;
                    $cook_time_days = $cook_time->d;
                    $cook_time_months = $cook_time->m;
                    $cook_time_years = $cook_time->y;
                } catch (Exception $e) {
                    if ($recipe->cook_time != null) {
                        $cook_time_input = '<input type="text" name="cook_time" value="' . $recipe->cook_time . '"/>';
                    }
                }
            
                try {
                    $total_time = new DateInterval($recipe->total_time);
                    $total_time_seconds = $total_time->s;
                    $total_time_minutes = $total_time->i;
                    $total_time_hours = $total_time->h;
                    $total_time_days = $total_time->d;
                    $total_time_months = $total_time->m;
                    $total_time_years = $total_time->y;
                } catch (Exception $e) {
                    if ($recipe->total_time != null) {
                        $total_time_input = '<input type="text" name="total_time" value="' . $recipe->total_time . '"/>';
                    }
                }
            } else {
                if (preg_match('(^[A-Z0-9]*$)', $recipe->prep_time) == 1) {
                    preg_match('(\d*S)', $recipe->prep_time, $pts);
                    $prep_time_seconds = str_replace('S', '', $pts[0]);
                    preg_match('(\d*M)', $recipe->prep_time, $ptm, PREG_OFFSET_CAPTURE, strpos($recipe->prep_time, 'T'));
                    $prep_time_minutes = str_replace('M', '', $ptm[0][0]);
                    preg_match('(\d*H)', $recipe->prep_time, $pth);
                    $prep_time_hours = str_replace('H', '', $pth[0]);
                    preg_match('(\d*D)', $recipe->prep_time, $ptd);
                    $prep_time_days = str_replace('D', '', $ptd[0]);
                    preg_match('(\d*M)', $recipe->prep_time, $ptmm);
                    $prep_time_months = str_replace('M', '', $ptmm[0]);
                    preg_match('(\d*Y)', $recipe->prep_time, $pty);
                    $prep_time_years = str_replace('Y', '', $pty[0]);
                } else {
                    if ($recipe->prep_time != null) {
                        $prep_time_input = '<input type="text" name="prep_time" value="' . $recipe->prep_time . '"/>';
                    }
                }
                
                if (preg_match('(^[A-Z0-9]*$)', $recipe->cook_time) == 1) {
                    preg_match('(\d*S)', $recipe->cook_time, $cts);
                    $cook_time_seconds = str_replace('S', '', $cts[0]);
                    preg_match('(\d*M)', $recipe->cook_time, $ctm, PREG_OFFSET_CAPTURE, strpos($recipe->cook_time, 'T'));
                    $cook_time_minutes = str_replace('M', '', $ctm[0][0]);
                    preg_match('(\d*H)', $recipe->cook_time, $cth);
                    $cook_time_hours = str_replace('H', '', $cth[0]);
                    preg_match('(\d*D)', $recipe->cook_time, $ctd);
                    $cook_time_days = str_replace('D', '', $ctd[0]);
                    preg_match('(\d*M)', $recipe->cook_time, $ctmm);
                    $cook_time_months = str_replace('M', '', $ctmm[0]);
                    preg_match('(\d*Y)', $recipe->cook_time, $cty);
                    $cook_time_years = str_replace('Y', '', $cty[0]);
                } else {
                    if ($recipe->cook_time != null) {
                        $cook_time_input = '<input type="text" name="cook_time" value="' . $recipe->cook_time . '"/>';
                    }
                }
                
                if (preg_match('(^[A-Z0-9]*$)', $recipe->total_time) == 1) {
                    preg_match('(\d*S)', $recipe->total_time, $tts);
                    $total_time_seconds = str_replace('S', '', $tts[0]);
                    preg_match('(\d*M)', $recipe->total_time, $ttm, PREG_OFFSET_CAPTURE, strpos($recipe->total_time, 'T'));
                    $total_time_minutes = str_replace('M', '', $ttm[0][0]);
                    preg_match('(\d*H)', $recipe->total_time, $tth);
                    $total_time_hours = str_replace('H', '', $tth[0]);
                    preg_match('(\d*D)', $recipe->total_time, $ttd);
                    $total_time_days = str_replace('D', '', $ttd[0]);
                    preg_match('(\d*M)', $recipe->total_time, $ttmm);
                    $total_time_months = str_replace('M', '', $ttmm[0]);
                    preg_match('(\d*Y)', $recipe->total_time, $tty);
                    $total_time_years = str_replace('Y', '', $tty[0]);
                } else {
                    if ($recipe->total_time != null) {
                        $total_time_input = '<input type="text" name="total_time" value="' . $recipe->total_time . '"/>';
                    }
                }
            }
            
            $yield = $recipe->yield;
            $serving_size = $recipe->serving_size;
            $calories = $recipe->calories;
            $fat = $recipe->fat;
/*!!mwp            
            $ingredients = array();
            $i = 0;
            foreach ($ingredients_list as $ingredient) {
                $ingredients[$i]["name"] = $ingredient->name;
                $ingredients .= $ingredient->name; //!!mwp
                //!!mwp $ingredients[$i]["amount"] = $ingredient->amount;
                $i++;
            }
*/
            $ingredients = $recipe->ingredients; //!!xxx amd_zlrecipe_strip_chars( $recipe->ingredients ); //!!mwp
            $instructions = $recipe->instructions; //!!xxx amd_zlrecipe_strip_chars( $recipe->instructions );
            //!!mwp $iframe_title = "Update Your Recipe";
            //!!mwp $submit = "Update Recipe";
        } else {
            $recipe_id = htmlentities($post_info["recipe_id"], ENT_QUOTES);
            if( !$get_info["add-recipe-button"] ) //!!mwp
                 $recipe_title = get_the_title( $get_info["post_id"] ); //!!mwp
            else
                 $recipe_title = amd_zlrecipe_strip_chars( htmlentities($post_info["recipe_title"], ENT_QUOTES) );
            $recipe_image = htmlentities($post_info["recipe_image"], ENT_QUOTES); //!!mwp
            $summary = amd_zlrecipe_strip_chars( htmlentities($post_info["summary"], ENT_QUOTES) );
            $rating = htmlentities($post_info["rating"], ENT_QUOTES);
            $prep_time_seconds = htmlentities($post_info["prep_time_seconds"], ENT_QUOTES);
            $prep_time_minutes = htmlentities($post_info["prep_time_minutes"], ENT_QUOTES);
            $prep_time_hours = htmlentities($post_info["prep_time_hours"], ENT_QUOTES);
            $prep_time_days = htmlentities($post_info["prep_time_days"], ENT_QUOTES);
            $prep_time_weeks = htmlentities($post_info["prep_time_weeks"], ENT_QUOTES);
            $prep_time_months = htmlentities($post_info["prep_time_months"], ENT_QUOTES);
            $prep_time_years = htmlentities($post_info["prep_time_years"], ENT_QUOTES);
            $cook_time_seconds = htmlentities($post_info["cook_time_seconds"], ENT_QUOTES);
            $cook_time_minutes = htmlentities($post_info["cook_time_minutes"], ENT_QUOTES);
            $cook_time_hours = htmlentities($post_info["cook_time_hours"], ENT_QUOTES);
            $cook_time_days = htmlentities($post_info["cook_time_days"], ENT_QUOTES);
            $cook_time_weeks = htmlentities($post_info["cook_time_weeks"], ENT_QUOTES);
            $cook_time_months = htmlentities($post_info["cook_time_months"], ENT_QUOTES);
            $cook_time_years = htmlentities($post_info["cook_time_years"], ENT_QUOTES);
            $total_time_seconds = htmlentities($post_info["total_time_seconds"], ENT_QUOTES);
            $total_time_minutes = htmlentities($post_info["total_time_minutes"], ENT_QUOTES);
            $total_time_hours = htmlentities($post_info["total_time_hours"], ENT_QUOTES);
            $total_time_days = htmlentities($post_info["total_time_days"], ENT_QUOTES);
            $total_time_weeks = htmlentities($post_info["total_time_weeks"], ENT_QUOTES);
            $total_time_months = htmlentities($post_info["total_time_months"], ENT_QUOTES);
            $total_time_years = htmlentities($post_info["total_time_years"], ENT_QUOTES);
            $yield = htmlentities($post_info["yield"], ENT_QUOTES);
            $serving_size = htmlentities($post_info["serving_size"], ENT_QUOTES);
            $calories = htmlentities($post_info["calories"], ENT_QUOTES);
            $fat = htmlentities($post_info["fat"], ENT_QUOTES);
/*!!mwp
            $ingredients = array();
            for ($i = 0; $i < count($post_info["ingredients"]); $i++) {
                $ingredients[$i]["name"] = htmlentities($post_info["ingredients"][$i]["name"], ENT_QUOTES);
                //!!mwp $ingredients[$i]["amount"] = htmlentities($post_info["ingredients"][$i]["amount"], ENT_QUOTES);
            }
*/
            $ingredients = amd_zlrecipe_strip_chars( htmlentities($post_info["ingredients"], ENT_QUOTES) ); //!!mwp
            $instructions = amd_zlrecipe_strip_chars( htmlentities($post_info["instructions"], ENT_QUOTES) );
            
            //!!mwp if ($recipe_title != null && $recipe_title != '' && $ingredients[0]['name'] != null && $ingredients[0]['name'] != '') {
            if ($recipe_title != null && $recipe_title != '' && $ingredients != null && $ingredients != '') { //!!mwp
                $recipe_id = amd_zlrecipe_insert_db($post_info);
            }
        }
    }
    
    //$id = (int) $_REQUEST["post_id"];
    $id = (int) $get_info["post_id"];
    $nid = $id . '-' . $recipe_id;
    $url = get_option('siteurl');
    $dirname = dirname(plugin_basename(__FILE__));
    $submitform = '';
    if ($post_info != null) {
        $submitform .= "<script>window.onload = amdZLRecipeSubmitForm;</script>";
    }
/*!!mwp
    $addingredients = '';
    if (!empty($ingredients) && count($ingredients) > 5) {
        $num_ingredients = count($ingredients);
    } else {
        $num_ingredients = 5;
    }
    for ($i=1; $i<$num_ingredients; $i++) {
        //!!mwp $addingredients .= "<script type='text/javascript'>amdZLRecipeAddIngredient('" . $i . "', '" . $ingredients[$i]['amount'] . "', '" . $ingredients[$i]['name'] . "');</script>";
        $addingredients .= "<script type='text/javascript'>amdZLRecipeAddIngredient('" . $i . "', '" . $ingredients[$i]['name'] . "');</script>"; //!!mwp
    }
 */
 
    echo <<< HTML

<!DOCTYPE html>
<head>
    <link rel="stylesheet" href="$url/wp-content/plugins/$dirname/zlrecipe-dlog.css" type="text/css" media="all" />
    <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.5.1/jquery.min.js"></script>
    <script type="text/javascript">//<!CDATA[
                                        
/*!!mwp
        var globalCount = 0;
        
        //!!mwp function amdZLRecipeAddIngredient(count, amount, name) {
        function amdZLRecipeAddIngredient(count, name) {
            five = true;
            //!!mwp amount1 = '';
            name1 = '';
            if (count!=undefined) {
                globalCount=count;
                five = false;
            }
            //!!mwp if (amount!=undefined) {
            //!!mwp     amount1=amount;
            //!!mwp }
            if (name!=undefined) {
                name1=name;
            }
            if (five) {
                for (i=0;i<5;i++) {
                    //!!mwp var newIngredient = '<div id="ingredient-' + globalCount + '" class="ingredient cls"><input class="amount" type="text" name="ingredients[' + globalCount + '][amount]" value="' + amount1 + '" /><input class="name" type="text" name="ingredients[' + globalCount + '][name]" value="' + name1 + '" /></div>';
                    var newIngredient = '<div id="ingredient-' + globalCount + '" class="ingredient cls"><input class="name" type="text" name="ingredients[' + globalCount + '][name]" value="' + name1 + '" /></div>'; //!!mwp
                    $('#amd_zlrecipe_ingredients').append(newIngredient);
                    globalCount++;
                }
            } else {
                //!!mwp var newIngredient = '<div id="ingredient-' + globalCount + '" class="ingredient cls"><input class="amount" type="text" name="ingredients[' + globalCount + '][amount]" value="' + amount1 + '" /><input class="name" type="text" name="ingredients[' + globalCount + '][name]" value="' + name1 + '" /></div>';
                var newIngredient = '<div id="ingredient-' + globalCount + '" class="ingredient cls"><input class="name" type="text" name="ingredients[' + globalCount + '][name]" value="' + name1 + '" /></div>'; //!!mwp
                $('#amd_zlrecipe_ingredients').append(newIngredient);
                globalCount++;
            }
            
            return false;
        }
*/
        
        function amdZLRecipeSubmitForm() {
            var title = document.forms['recipe_form']['recipe_title'].value;
            //!!mwp var ingredient0 = $('#ingredient-0 input.name').val();
            if (title==null || title=='') {
                $('#recipe-title input').addClass('input-error');
                $('#recipe-title').append('<p class="error-message">You must enter a title for your recipe.</p>');
                
                return false;
            }
            var ingredients = $('#amd_zlrecipe_ingredients textarea').val(); //!!mwp
            //!!mwp if (ingredient0==null || ingredient0=='' || ingredient0==undefined) {
            if (ingredients==null || ingredients=='' || ingredients==undefined) { //!!mwp
                //!!mwp $('#ingredient-0 input').addClass('input-error');
                $('#amd_zlrecipe_ingredients textarea').addClass('input-error'); //!!mwp
                //!!mwp old append line deleted
                $('#amd_zlrecipe_ingredients').append('<p class="error-message">You must enter at least one ingredient.</p>'); //!!mwp
                
                return false;
            }
            //window.parent.amdZLRecipeInsertIntoPostEditor('$recipe_id','$url','$dirname');
            window.parent.amdZLRecipeInsertIntoPostEditor('$nid','$url','$dirname');
        }
        
        $(document).ready(function() {
            $('#more-options').hide();
            $('#more-options-toggle').click(function() {
                $('#more-options').toggle(400);
                
                return false;
            });
/*!!mwp
            $('#add-another-ingredient a').click(function() {
                amdZLRecipeAddIngredient();
                
                return false;
            });
*/
        });
        
    //]]>
    </script>
    $submitform
</head>
<body id="amd-zlrecipe-uploader">
    <form enctype='multipart/form-data' method='post' action='' name='recipe_form'>
        <h3 class='amd-zlrecipe-title'>$iframe_title</h3>
        <div id='amd-zlrecipe-form-items'>
            <input type='hidden' name='post_id' value='$id' />
            <input type='hidden' name='recipe_id' value='$recipe_id' />
            <p id='recipe-title'><label>Recipe Title <span class='required'>*</span></label> <input type='text' name='recipe_title' value='$recipe_title' /></p>
            <p id='recipe-image'><label>Recipe Image</label> <input type='text' name='recipe_image' value='$recipe_image' /></p>
            <p id='amd_zlrecipe_ingredients' class='cls'><label>Ingredients <span class='required'>*</span> <small>Put each ingredient on a separate line.  There is no need to use bullets for your ingredients.</small><small>You can also create labels, hyperlinks and even add images! <a href="http://marketing.ziplist.com.s3.amazonaws.com/plugin_instructions.pdf" target="_blank">Learn how here</a></small></label><textarea name='ingredients'>$ingredients</textarea></label></p>
            <p id='amd-zlrecipe-instructions' class='cls'><label>Instructions <small>Press return after each instruction. There is no need to number your instructions.</small><small>You can also create labels, hyperlinks and even add images! <a href="http://marketing.ziplist.com.s3.amazonaws.com/plugin_instructions.pdf" target="_blank">Learn how here</a></small></label><textarea name='instructions'>$instructions</textarea></label></p>
            <p><a href='#' id='more-options-toggle'>More options</a></p>
            <div id='more-options'>
                <p class='cls'><label>Summary</label> <textarea name='summary'>$summary</textarea></label></p>
                <p class='cls'><label>Rating</label>
                	<span class='rating'>
						<select name="rating">
							  <option value="0">None</option>
							  <option value="1" $ss[1]>1 Star</option>
							  <option value="2" $ss[2]>2 Stars</option>
							  <option value="3" $ss[3]>3 Stars</option>
							  <option value="4" $ss[4]>4 Stars</option>
							  <option value="5" $ss[5]>5 Stars</option>
						</select>
					</span>
				</p>
                <p class="cls"><label>Prep Time</label> 
                    $prep_time_input
                    <span class="time">
                        <span><input type='number' min="0" max="24" name='prep_time_hours' value='$prep_time_hours' /><label>hours</label></span>
                        <span><input type='number' min="0" max="60" name='prep_time_minutes' value='$prep_time_minutes' /><label>minutes</label></span>
                    </span>
                </p>
                <p class="cls"><label>Cook Time</label>
                    $cook_time_input
                    <span class="time">
                    	<span><input type='number' min="0" max="24" name='cook_time_hours' value='$cook_time_hours' /><label>hours</label></span>
                        <span><input type='number' min="0" max="60" name='cook_time_minutes' value='$cook_time_minutes' /><label>minutes</label></span>
                    </span>
                </p>
                <p class="cls"><label>Total Time</label>
                    $total_time_input
                    <span class="time">
                        <span><input type='number' min="0" max="24" name='total_time_hours' value='$total_time_hours' /><label>hours</label></span>
                        <span><input type='number' min="0" max="60" name='total_time_minutes' value='$total_time_minutes' /><label>minutes</label></span>
                    </span>
                </p>
                <p><label>Yield</label> <input type='text' name='yield' value='$yield' /></p>
                <p><label>Serving Size</label> <input type='text' name='serving_size' value='$serving_size' /></p>
                <p><label>Calories</label> <input type='text' name='calories' value='$calories' /></p>
                <p><label>Fat</label> <input type='text' name='fat' value='$fat' /></p>
            </div>
            <input type='submit' value='$submit' name='add-recipe-button' />
        </div>
    </form>
</body>
HTML;
}

// Inserts the recipe into the database
function amd_zlrecipe_insert_db($post_info) {
    global $wpdb;
    
    $recipe_id = $post_info["recipe_id"];
    
    if ($post_info["prep_time_years"] || $post_info["prep_time_months"] || $post_info["prep_time_days"] || $post_info["prep_time_hours"] || $post_info["prep_time_minutes"] || $post_info["prep_time_seconds"]) {
        $prep_time = 'P';
        if ($post_info["prep_time_years"]) {
            $prep_time .= $post_info["prep_time_years"] . 'Y';
        }
        if ($post_info["prep_time_months"]) {
            $prep_time .= $post_info["prep_time_months"] . 'M';
        }
        if ($post_info["prep_time_days"]) {
            $prep_time .= $post_info["prep_time_days"] . 'D';
        }
        if ($post_info["prep_time_hours"] || $post_info["prep_time_minutes"] || $post_info["prep_time_seconds"]) {
            $prep_time .= 'T';
        }
        if ($post_info["prep_time_hours"]) {
            $prep_time .= $post_info["prep_time_hours"] . 'H';
        }
        if ($post_info["prep_time_minutes"]) {
            $prep_time .= $post_info["prep_time_minutes"] . 'M';
        }
        if ($post_info["prep_time_seconds"]) {
            $prep_time .= $post_info["prep_time_seconds"] . 'S';
        }
    } else {
        $prep_time = $post_info["prep_time"];
    }
    
    if ($post_info["cook_time_years"] || $post_info["cook_time_months"] || $post_info["cook_time_days"] || $post_info["cook_time_hours"] || $post_info["cook_time_minutes"] || $post_info["cook_time_seconds"]) {
        $cook_time = 'P';
        if ($post_info["cook_time_years"]) {
            $cook_time .= $post_info["cook_time_years"] . 'Y';
        }
        if ($post_info["cook_time_months"]) {
            $cook_time .= $post_info["cook_time_months"] . 'M';
        }
        if ($post_info["cook_time_days"]) {
            $cook_time .= $post_info["cook_time_days"] . 'D';
        }
        if ($post_info["cook_time_hours"] || $post_info["cook_time_minutes"] || $post_info["cook_time_seconds"]) {
            $cook_time .= 'T';
        }
        if ($post_info["cook_time_hours"]) {
            $cook_time .= $post_info["cook_time_hours"] . 'H';
        }
        if ($post_info["cook_time_minutes"]) {
            $cook_time .= $post_info["cook_time_minutes"] . 'M';
        }
        if ($post_info["cook_time_seconds"]) {
            $cook_time .= $post_info["cook_time_seconds"] . 'S';
        }
    } else {
        $cook_time = $post_info["cook_time"];
    }
    
    if ($post_info["total_time_years"] || $post_info["total_time_months"] || $post_info["total_time_days"] || $post_info["total_time_hours"] || $post_info["total_time_minutes"] || $post_info["total_time_seconds"]) {
        $total_time = 'P';
        if ($post_info["total_time_years"]) {
            $total_time .= $post_info["total_time_years"] . 'Y';
        }
        if ($post_info["total_time_months"]) {
            $total_time .= $post_info["total_time_months"] . 'M';
        }
        if ($post_info["total_time_days"]) {
            $total_time .= $post_info["total_time_days"] . 'D';
        }
        if ($post_info["total_time_hours"] || $post_info["total_time_minutes"] || $post_info["total_time_seconds"]) {
            $total_time .= 'T';
        }
        if ($post_info["total_time_hours"]) {
            $total_time .= $post_info["total_time_hours"] . 'H';
        }
        if ($post_info["total_time_minutes"]) {
            $total_time .= $post_info["total_time_minutes"] . 'M';
        }
        if ($post_info["total_time_seconds"]) {
            $total_time .= $post_info["total_time_seconds"] . 'S';
        }
    } else {
        $total_time = $post_info["total_time"];
    }
    
	//$pid = get_permalink(1137);
    /*$recipe = array (
        "post_id" => $post_info["post_id"],
        "recipe_title" => amd_zlrecipe_strip_chars( $post_info["recipe_title"] ),
        "recipe_image" => $post_info["recipe_image"],
        "summary" => amd_zlrecipe_strip_chars( $post_info["summary"] ),
        "rating" => $post_info["rating"],
        "prep_time" => $prep_time,
        "cook_time" => $cook_time,
        "total_time" => $total_time,
        "yield" => $post_info["yield"],
        "serving_size" => $post_info["serving_size"],
        "calories" => $post_info["calories"],
        "fat" => $post_info["fat"],
        "ingredients" => amd_zlrecipe_strip_chars( $post_info["ingredients"] ),
        "instructions" => amd_zlrecipe_strip_chars( $post_info["instructions"] ),
		"permalink" => $pid,
    );*/
    $recipe = array (
        "post_id" => $post_info["post_id"],
        "recipe_title" => amd_zlrecipe_strip_chars( $post_info["recipe_title"] ),
        "recipe_image" => $post_info["recipe_image"],
        "summary" => amd_zlrecipe_strip_chars( $post_info["summary"] ),
        "rating" => $post_info["rating"],
        "prep_time" => $prep_time,
        "cook_time" => $cook_time,
        "total_time" => $total_time,
        "yield" => $post_info["yield"],
        "serving_size" => $post_info["serving_size"],
        "calories" => $post_info["calories"],
        "fat" => $post_info["fat"],
        "ingredients" => amd_zlrecipe_strip_chars( $post_info["ingredients"] ),
        "instructions" => amd_zlrecipe_strip_chars( $post_info["instructions"] ),
		"permalink" => get_permalink($post_info["post_id"]),
	);
    
    if (amd_zlrecipe_select_recipe_db($recipe_id) == null) {	
		$wpdb->insert( $wpdb->prefix . "amd_zlrecipe_recipes", $recipe );
        $recipe_id = $wpdb->insert_id;
    } else {
        $wpdb->update( $wpdb->prefix . "amd_zlrecipe_recipes", $recipe, array( 'recipe_id' => $recipe_id ));
        //!!mwp $wpdb->query("DELETE FROM " . $wpdb->prefix . "amd_zlrecipe_ingredients WHERE recipe_id = '" . $recipe_id . "'");
    }

/*!!mwp    
    for ($i = 0; $i < count($post_info["ingredients"]); $i++) {
        //!!mwp if ( $post_info["ingredients"][$i]["amount"] != null || $post_info["ingredients"][$i]["name"] != null) {
        if ( $post_info["ingredients"][$i]["name"] != null) { //!!mwp
            $ingredient = array(
                "recipe_id" => $recipe_id,
                //!!mwp "amount" => $post_info["ingredients"][$i]["amount"],
                "name" => $post_info["ingredients"][$i]["name"],
            );
        
            $wpdb->insert( $wpdb->prefix . "amd_zlrecipe_ingredients", $ingredient );
        }
    }
*/
        
    return $recipe_id;
}

// Inserts the recipe into the post editor
function amd_zlrecipe_plugin_footer() {
    $url = get_option('siteurl');
    $dirname = dirname(plugin_basename(__FILE__));
    
    echo <<< HTML
    <style type="text/css" media="screen">
        #wp_editrecipebtns { position:absolute;display:block;z-index:999998; }
        #wp_editrecipebtn { margin-right:20px; }
        #wp_editrecipebtn,#wp_delrecipebtn { cursor:pointer; padding:12px;background:#010101; -moz-border-radius:8px;-khtml-border-radius:8px;-webkit-border-radius:8px;border-radius:8px; filter:alpha(opacity=80); -moz-opacity:0.8; -khtml-opacity: 0.8; opacity: 0.8; }
        #wp_editrecipebtn:hover,#wp_delrecipebtn:hover { background:#000; filter:alpha(opacity=100); -moz-opacity:1; -khtml-opacity: 1; opacity: 1; }
    </style>
    <script>//<![CDATA[
    var baseurl = '$url';
    var dirname = '$dirname';
        function amdZLRecipeInsertIntoPostEditor(rid,getoption,dirname) {
            tb_remove();
            
            var ed;
            
            var output = '<img id="amd-zlrecipe-recipe-';
            output += rid;
            output += '" class="amd-zlrecipe-recipe" src="' + getoption + '/wp-content/plugins/' + dirname + '/zlrecipe-placeholder.png" alt="" />';
            
        	if ( typeof tinyMCE != 'undefined' && ( ed = tinyMCE.activeEditor ) && !ed.isHidden() ) {
        		ed.focus();
        		if ( tinymce.isIE )
        			ed.selection.moveToBookmark(tinymce.EditorManager.activeEditor.windowManager.bookmark);

        		ed.execCommand('mceInsertContent', false, output);

        	} else if ( typeof edInsertContent == 'function' ) {
        		edInsertContent(edCanvas, output);
        	} else {
        		jQuery( edCanvas ).val( jQuery( edCanvas ).val() + output );
        	}
        }
    //]]></script>
HTML;
}

add_action('admin_footer', 'amd_zlrecipe_plugin_footer');

// Converts the image to a recipe for output
function amd_zlrecipe_convert_to_recipe($post_text) {
    $output = $post_text;
    $needle_old = 'id="amd-zlrecipe-recipe-';
    $preg_needle_old = '/(id)=("(amd-zlrecipe-recipe-)[0-9^"]*")/i';
    $needle = '[amd-zlrecipe-recipe:';
    $preg_needle = '/\[amd-zlrecipe-recipe:([0-9]+)\]/i';
    
    if (strpos($post_text, $needle_old) !== false) {
        // This is for backwards compatability. Please do not delete or alter.
        preg_match_all($preg_needle_old, $post_text, $matches);
        foreach ($matches[0] as $match) {
            $recipe_id = str_replace('id="amd-zlrecipe-recipe-', '', $match);
            $recipe_id = str_replace('"', '', $recipe_id);
            
            $recipe = amd_zlrecipe_select_recipe_db($recipe_id);
            //!!mwp $ingredients = amd_zlrecipe_select_ingredients_db($recipe_id);
                        
            //!!mwp $formatted_recipe = amd_zlrecipe_format_recipe($recipe, $ingredients);
            $formatted_recipe = amd_zlrecipe_format_recipe($recipe);

            $output = str_replace('<img id="amd-zlrecipe-recipe-' . $recipe_id . '" class="amd-zlrecipe-recipe" src="' . get_option('siteurl') . '/wp-content/plugins/' . dirname(plugin_basename(__FILE__)) . '/zlrecipe-placeholder.png?ver=1.0" alt="" />', $formatted_recipe, $output);
        }
    }
    
    if (strpos($post_text, $needle) !== false) {
        preg_match_all($preg_needle, $post_text, $matches);
        foreach ($matches[0] as $match) {
            $recipe_id = str_replace('[amd-zlrecipe-recipe:', '', $match);
            $recipe_id = str_replace(']', '', $recipe_id);

            $recipe = amd_zlrecipe_select_recipe_db($recipe_id);
            //!!mwp $ingredients = amd_zlrecipe_select_ingredients_db($recipe_id);

            //!!mwp $formatted_recipe = amd_zlrecipe_format_recipe($recipe, $ingredients);
            $formatted_recipe = amd_zlrecipe_format_recipe($recipe); //!!mwp

            $output = str_replace('[amd-zlrecipe-recipe:' . $recipe_id . ']', $formatted_recipe, $output);
        }
    }
    
    return $output;
}

add_filter('the_content', 'amd_zlrecipe_convert_to_recipe');
//add_filter('the_content', 'amd_zlrecipe_convert_to_recipe', 8);

// Pulls a recipe from the db
function amd_zlrecipe_select_recipe_db($recipe_id) {
    global $wpdb;
    
    $recipe = $wpdb->get_row("SELECT * FROM " . $wpdb->prefix . "amd_zlrecipe_recipes WHERE recipe_id=" . $recipe_id);

    return $recipe;
}

/*!!mwp
// Pulls ingredients from the db
function amd_zlrecipe_select_ingredients_db($recipe_id) {
    global $wpdb;
    
    $ingredients = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "amd_zlrecipe_ingredients WHERE recipe_id=" . $recipe_id . " ORDER BY ingredient_id");

    return $ingredients;
}
*/

// Format an ISO8601 duration for human readibility
function amd_zlrecipe_format_duration($duration) {
	$date_abbr = array('y' => 'year', 'm' => 'month', 'd' => 'day', 'h' => 'hour', 'i' => 'minute', 's' => 'second');
	$result = '';

	if (class_exists('DateInterval')) {
		try {
			$result_object = new DateInterval($duration);

			foreach ($date_abbr as $abbr => $name) {
				if ($result_object->$abbr > 0) {
					$result .= $result_object->$abbr . ' ' . $name;
					if ($result_object->$abbr > 1) {
						$result .= 's';
					}
					$result .= ', ';
				}
			}

			$result = trim($result, ' \t,');
		} catch (Exception $e) {
			$result = $duration;
		}
	} else { // else we have to do the work ourselves so the output is pretty
		$arr = explode('T', $duration);
		$arr[1] = str_replace('M', 'I', $arr[1]); // This mimics the DateInterval property name
		$duration = implode('T', $arr);

		foreach ($date_abbr as $abbr => $name) {
		if (preg_match('/(\d+)' . $abbr . '/i', $duration, $val)) {
				$result .= $val[1] . ' ' . $name;
				if ($val[1] > 1) {
					$result .= 's';
				}
				$result .= ', ';
			}
		}

		$result = trim($result, ' \t,');
	}
	return $result;
}

// function to include the javascript for the Add Recipe button
function amd_zlrecipe_process_head() {

	// Always add the print script
    $header_html='<script type="text/javascript" src="' . AMD_ZLRECIPE_PLUGIN_DIRECTORY . 'zlrecipe_print.js"></script>
';

	// If the button is activated, include the button script and the button styles
	if (strcmp(get_option('ziplist_recipe_button_hide'), 'Hide') != 0) {
    	$header_html .= '<script type="text/javascript" src="http://www.zlcdn.com/javascripts/pt_include.js"></script>
	<link charset="utf-8" href="http://www.zlcdn.com/stylesheets/minibox/generic.css" rel="stylesheet" type="text/css" />
';
	}

	// Recipe styling
	$css = get_option('zlrecipe_stylesheet');
	if (strcmp($css, '') != 0) {
		$header_html .= '<link charset="utf-8" href="http://www.zlcdn.com/stylesheets/minibox/' . $css . '.css" rel="stylesheet" type="text/css" />
';
	}

    echo $header_html;
}
add_filter('wp_head', 'amd_zlrecipe_process_head');

// Replaces the [a|b] pattern with text a that links to b
function amd_zlrecipe_linkify_item($item, $class) {
	return preg_replace('/\[([^\]\|\[]*)\|([^\]\|\[]*)\]/', '<a href="\\2" class="' . $class . '-link">\\1</a>', $item);
}

function amd_zlrecipe_break( $otag, $text, $ctag) {
	$output = "";
	$split_string = explode( "\r\n\r\n", $text, 10 );
	foreach ( $split_string as $str )
	{
		$output .= $otag . $str . $ctag;
	}
	return $output;
}

// Processes markup for attributes like labels, images and links
// !Label
// %image
function amd_zlrecipe_format_item($item, $elem, $class, $id, $i) {

	if (preg_match("/^%(.*)/", $item, $matches)) {	// IMAGE
		$output = '<img class = "' . $class . '-image" src="' . $matches[1] . '" />';
		return $output;
	}

	if (preg_match("/^!(.*)/", $item, $matches)) {	// LABEL
		$class .= '-label';
		$elem = 'div';
		$item = $matches[1];
	}

	$output = '<' . $elem . ' id="' . $id . $i . '" class="' . $class . '">';
	$output .= amd_zlrecipe_linkify_item($item, $class);
	$output .= '</' . $elem . '>';

	return $output;
}

// Formats the recipe for output
//!!mwp function amd_zlrecipe_format_recipe($recipe, $ingredients) {
function amd_zlrecipe_format_recipe($recipe) { //!!mwp
    $output = "";
    $permalink = get_permalink();

	// Output main recipe div with border style
	$style_tag = '';
	$border_style = get_option('zlrecipe_outer_border_style');
	if ($border_style != null)
		$style_tag = 'style="border: ' . $border_style . ';"';
    $output .= '
    <div id="zlrecipe-container-' . $recipe->recipe_id . '" ' . $style_tag . '>
    <div id="zlrecipe-container" class="hrecipe serif">
      <div id="zlrecipe-innerdiv">
        <div class="item b-b">';

    // Add the print button
    if (strcmp(get_option('zlrecipe_print_link_hide'), 'Hide') != 0) {
		$output .= '<div class="zlrecipe-print-link fl-r"><a class="butn-link" title="Print this recipe" href="#" onclick="zlrPrint(\'zlrecipe-container-' . $recipe->recipe_id . '\'); return false">Print</a></div>';
	}

    //!!mwp add the ZipList recipe button
    if (strcmp(get_option('ziplist_recipe_button_hide'), 'Hide') != 0) {
		$ziplist_partner_key = get_option('ziplist_partner_key');
		$output .= '<div id="zl-recipe-link-' . $recipe->recipe_id . '" class="zl-recipe-link fl-r">
		  <a class="butn-link" title="Add this recipe to your ZipList, where you can store all of your favorite web recipes in one place and easily add ingredients to your shopping list."
		    onmouseup="getZRecipeArgs(this, {\'partner_key\':\''. $ziplist_partner_key . '\', \'url\':\'' . $permalink . '\', \'class\':\'hrecipe\'}); return false;"
		    href="javascript:void(0);"><span>Add this recipe to ZipList!</span>
		  </a>
		</div>';
	}

	//!!dc add the title and close the item class
	$hide_tag = '';
	if (strcmp(get_option('recipe_title_hide'), 'Hide') == 0)
        $hide_tag = ' texthide';
	$output .= '<div id="zlrecipe-title" class="fn b-b h-1 strong' . $hide_tag . '" >' . $recipe->recipe_title . '</div>
      </div>';
	
	//!!dc open the meta and fl-l container divs
	$output .= '<div class="meta clear">
      <div class="fl-l width-50">';

    if ($recipe->rating != 0) {
        $output .= '<p id="zlrecipe-rating" class="review hreview-aggregate">';
        if (strcmp(get_option('zlrecipe_rating_label_hide'), 'Hide') != 0) {
        	$output .= get_option('zlrecipe_rating_label') . ' ';
        }
        $output .= '<span class="rating rating-' . $recipe->rating . '">
          	<span class="average">' . $recipe->rating . '</span>
          	<span class="count" style="display: none;">1</span>
          </span>
       </p>';
    }
    
    //!! recipe timing
    if ($recipe->prep_time != null) {
    	$prep_time = amd_zlrecipe_format_duration($recipe->prep_time);
        
        $output .= '<p id="zlrecipe-prep-time">';
        if (strcmp(get_option('zlrecipe_prep_time_label_hide'), 'Hide') != 0) {
            $output .= get_option('zlrecipe_prep_time_label') . ' ';
        }
        $output .= '<span class="preptime">' . $prep_time . '<span class="value-title" title="' . $recipe->prep_time . '"><!-- --></span></span></p>';
    }
    if ($recipe->cook_time != null) {
        $cook_time = amd_zlrecipe_format_duration($recipe->cook_time);
        
        $output .= '<p id="zlrecipe-cook-time">';
        if (strcmp(get_option('zlrecipe_cook_time_label_hide'), 'Hide') != 0) {
            $output .= get_option('zlrecipe_cook_time_label') . ' ';
        }
        $output .= '<span class="cooktime">' . $cook_time . '<span class="value-title" title="' . $recipe->cook_time . '"><!-- --></span></span></p>';
    }
    if ($recipe->total_time != null) {
        $total_time = amd_zlrecipe_format_duration($recipe->total_time);
        
        $output .= '<p id="zlrecipe-total-time">';
        if (strcmp(get_option('zlrecipe_total_time_label_hide'), 'Hide') != 0) {
            $output .= get_option('zlrecipe_total_time_label') . ' ';
        }
        $output .= '<span class="duration">' . $total_time . '<span class="value-title" title="' . $recipe->total_time . '"><!-- --></span></span></p>';
    }
    
    //!! close the first container div and open the second
    $output .= '</div>
      <div class="fl-l width-50">';
    
    //!! yield and nutrition
    if ($recipe->yield != null) {
        $output .= '<p id="zlrecipe-yield">';
        if (strcmp(get_option('zlrecipe_yield_label_hide'), 'Hide') != 0) {
            $output .= get_option('zlrecipe_yield_label') . ' ';
        }
        $output .= '<span class="yield">' . $recipe->yield . '</span></p>';
    }
    
    if ($recipe->serving_size != null || $recipe->calories != null || $recipe->fat != null) {
        $output .= '<div id="zlrecipe-nutrition" class="nutrition">';
        if ($recipe->serving_size != null) {
            $output .= '<p id="zlrecipe-serving-size">';
            if (strcmp(get_option('zlrecipe_serving_size_label_hide'), 'Hide') != 0) {
                $output .= get_option('zlrecipe_serving_size_label') . ' ';
            }
            $output .= '<span class="servingsize">' . $recipe->serving_size . '</span></p>';
        }
        if ($recipe->calories != null) {
            $output .= '<p id="zlrecipe-calories">';
            if (strcmp(get_option('zlrecipe_calories_label_hide'), 'Hide') != 0) {
                $output .= get_option('zlrecipe_calories_label') . ' ';
            }
            $output .= '<span class="calories">' . $recipe->calories . '</span></p>';
        }
        if ($recipe->fat != null) {
            $output .= '<p id="zlrecipe-fat">';
            if (strcmp(get_option('zlrecipe_fat_label_hide'), 'Hide') != 0) {
                $output .= get_option('zlrecipe_fat_label') . ' ';
            }
            $output .= '<span class="fat">' . $recipe->fat . '</span></p>';
        }
        $output .= '</div>';
    }

    //!! close the second container
    $output .= '</div>
      <div class="clear">
      </div>
    </div>';

    //!! create image and summary container
    if ($recipe->recipe_image != null || $recipe->summary != null) {
    	$style_tag = '';
        $output .= '<div class="img-desc-wrap">';
		if ($recipe->recipe_image != null) {
			$image_width = get_option('zlrecipe_image_width');
			if ($image_width != null) {
				$style_tag = 'style="width: ' . $image_width . 'px;"';
			}
			$output .= '<p class="t-a-c">
			  <img class="photo" src="' . $recipe->recipe_image . '" title="' . $recipe->recipe_title . '" ' . $style_tag . ' />
			</p>';
		}
		if ($recipe->summary != null) {
			$output .= '<div id="zlrecipe-summary">';
			$output .= amd_zlrecipe_break( '<p class="summary italic">', amd_zlrecipe_linkify_item($recipe->summary, 'summary'), '</p>' );
			$output .= '</div>';
		}
		$output .= '</div>';
	}

    $ingredient_type= '';
    $ingredient_tag = '';
    $ingredient_class = '';
    $ingredient_list_type_option = get_option('zlrecipe_ingredient_list_type');
    if (strcmp($ingredient_list_type_option, 'ul') == 0 || strcmp($ingredient_list_type_option, 'ol') == 0) {
        $ingredient_type = $ingredient_list_type_option;
        $ingredient_tag = 'li';
    } else if (strcmp($ingredient_list_type_option, 'p') == 0 || strcmp($ingredient_list_type_option, 'div') == 0) {
        $ingredient_type = 'span';
        $ingredient_tag = $ingredient_list_type_option;
    }
    
    if (strcmp(get_option('zlrecipe_ingredient_label_hide'), 'Hide') != 0) {
        $output .= '<p id="zlrecipe-ingredients" class="h-4 strong">' . get_option('zlrecipe_ingredient_label') . '</p>';
    }
    
    $output .= '<' . $ingredient_type . ' id="zlrecipe-ingredients-list">';
    $i = 0;
    $ingredients = explode("\n", $recipe->ingredients); //!!mwp
    foreach ($ingredients as $ingredient) {
		$output .= amd_zlrecipe_format_item($ingredient, $ingredient_tag, 'ingredient', 'zlrecipe-ingredient-', $i);
        $i++;
    }

    $output .= '</' . $ingredient_type . '>';

	// add the instructions
    if ($recipe->instructions != null) {
        
        $instruction_type= '';
        $instruction_tag = '';
        $instruction_list_type_option = get_option('zlrecipe_instruction_list_type');
        if (strcmp($instruction_list_type_option, 'ul') == 0 || strcmp($instruction_list_type_option, 'ol') == 0) {
            $instruction_type = $instruction_list_type_option;
            $instruction_tag = 'li';
        } else if (strcmp($instruction_list_type_option, 'p') == 0 || strcmp($instruction_list_type_option, 'div') == 0) {
            $instruction_type = 'span';
            $instruction_tag = $instruction_list_type_option;
        }
        
        $instructions = explode("\n", $recipe->instructions);
        if (strcmp(get_option('zlrecipe_instruction_label_hide'), 'Hide') != 0) {
            $output .= '<p id="zlrecipe-instructions" class="h-4 strong">' . get_option('zlrecipe_instruction_label') . '</p>';
        }
        $output .= '<' . $instruction_type . ' id="zlrecipe-instructions-list" class="instructions">';
        $j = 0;
        foreach ($instructions as $instruction) {
            if (strlen($instruction) > 1) {
            	$output .= amd_zlrecipe_format_item($instruction, $instruction_tag, 'instruction', 'zlrecipe-instruction-', $j);
                $j++;
            }
        }
        $output .= '</' . $instruction_type . '>';
    }

	//!!mwp add ZipList attribution and version
	$hide_tag = '';
    if (strcmp(get_option('ziplist_attribution_hide'), 'Hide') == 0)
        $hide_tag = 'style="display: none;"';
    $output .= '<div class="zl-linkback" ' . $hide_tag . '>Google Recipe View Microformatting by <a title="ZipList Recipe Plugin" href="http://www.ziplist.com/recipe_plugin" target="_blank">ZipList Recipe Plugin</a></div>';
    $output .= '<div class="ziplist-recipe-plugin" style="display: none;">' . AMD_ZLRECIPE_VERSION_NUM . '</div>';

    //!!mwp add permalink for printed output before closing the innerdiv
    if (strcmp(get_option('zlrecipe_printed_permalink_hide'), 'Hide') != 0) {
		$output .= '<a id="zl-printed-permalink" href="' . $permalink . '"title="Permalink to Recipe">' . $permalink . '</a>';
	}

    $output .= '</div>'; //!!dc

    //!!mwp add copyright statement for printed output (outside the dotted print line)
    $printed_copyright_statement = get_option('zlrecipe_printed_copyright_statement');
    if (strlen($printed_copyright_statement) > 0) {
		$output .= '<div id="zl-printed-copyright-statement">' . $printed_copyright_statement . '</div>';
	}

    $output .= '</div>
		</div>';
    
    return $output;
}
