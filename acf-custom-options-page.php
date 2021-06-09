<?php
/*
Plugin Name: Advanced Custom Fields: Custom Options Page
Description: Custom Options Page for ACF
Version: 1.0
Author: Arman H
Author URI: https://airarm.wordpress.com
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: acf_cop
*/

if(!defined('ABSPATH')){
    exit;
}

add_action('acf/include_location_rules', 'acf_include_location_rules_cop', 10);
function acf_include_location_rules_cop()
{
    require_once plugin_dir_path(__FILE__). 'location.php';
}

global $acf_CustomOptionsPages;
$acf_CustomOptionsPages = array();

function acf_get_custom_options_pages()
{
    global $wpdb, $acf_CustomOptionsPages;

    if(empty($acf_CustomOptionsPages)){
        return array();
    }

    $pages = array();

    foreach ($acf_CustomOptionsPages as $page)
    {
        $get_cop_post_id = $wpdb->get_var(sprintf(
            "SELECT id FROM %s WHERE post_name = '%s' AND post_type = 'acf-cop';",
            $wpdb->posts,
            $page['menu_slug']
        ));

        if(!empty($get_cop_post_id))
        {
            $pages[] = get_post($get_cop_post_id);
        }
    }

    return $pages;
}

function acf_add_custom_options_page(array $args)
{
    global $acf_CustomOptionsPages;

    if(empty($args) || !is_array($args)){
        return;
    }

    $default = array(
        'parent_slug' => '',
        'page_title' => '',
        'menu_title' => '',
        'capability' => 'manage_options',
        'menu_slug' => '',
        'icon_url' => '',
        'position' => null,
    );

    $args = wp_parse_args($args, $default);

    $acf_CustomOptionsPages[] = $args;
}

add_action('admin_menu', 'acf_admin_menu_custom_options_pages');
function acf_admin_menu_custom_options_pages()
{
    global $acf_CustomOptionsPages;

    if(!empty($acf_CustomOptionsPages))
    {
        foreach ($acf_CustomOptionsPages as $page)
        {
            if(empty($page['page_title']) || empty($page['menu_title']) || empty($page['capability']) || empty($page['menu_slug'])){
                continue;
            }

            if(empty($page['parent_slug']))
            {
                add_menu_page(
                    $page['page_title'],
                    $page['menu_title'],
                    $page['capability'],
                    $page['menu_slug'],
                    'acf_custom_options_page_layout',
                    $page['position']
                );
            }
            else
            {
                add_submenu_page(
                    $page['parent_slug'],
                    $page['page_title'],
                    $page['menu_title'],
                    $page['capability'],
                    $page['menu_slug'],
                    'acf_custom_options_page_layout',
                    $page['position']
                );
            }
        }
    }
}

function acf_custom_options_page_layout()
{
    global $wpdb, $acf_CustomOptionsPages, $pagenow;

    $page_slug = !empty($_GET['page']) ? sanitize_text_field($_GET['page']) : '';

    if(empty($page_slug) || empty($acf_CustomOptionsPages)){
        return;
    }

    $current_page = null;

    foreach ($acf_CustomOptionsPages as $page)
    {
        if($page['menu_slug'] == $page_slug){
            $current_page = $page;
            break;
        }
    }

    if(empty($current_page)){
        return;
    }

    $post_id = $wpdb->get_var(sprintf(
        "SELECT id FROM %s WHERE post_name = '%s' AND post_type = 'acf-cop';",
        $wpdb->posts,
        $current_page['menu_slug']
    ));

    $post_id = intval($post_id);
    ?>
    <div class="wrap">
        <h2><?php echo get_admin_page_title() ?></h2>
        <br/>
        <div id="acf_custom_options_page_postbox" class="postbox">
            <div class="inside">
                <?php
                acf_form(array(
                    'post_id' => $post_id,
                    'post_title' => false,
                    'post_content' => false,
                    'uploader' => 'wp',
                    'form_attributes' => array(
                        'id' => 'post_'.$post_id
                    )
                ));
                ?>
            </div>
        </div>
    </div>
    <?php
}

add_action('admin_init', 'acf_admin_init_custom_options_pages');
function acf_admin_init_custom_options_pages()
{
    global $pagenow, $acf_CustomOptionsPages;

    $page_slug = !empty($_GET['page']) ? sanitize_text_field($_GET['page']) : '';

    if(empty($page_slug) || empty($acf_CustomOptionsPages)){
        return;
    }

    $current_page = null;

    foreach ($acf_CustomOptionsPages as $page)
    {
        if($page['menu_slug'] == $page_slug){
            $current_page = $page;
            break;
        }
    }

    if(empty($current_page)){
        return;
    }

    acf_enqueue_scripts();

    if(acf_verify_nonce('acf_form'))
    {
        if(acf_validate_save_post(true))
        {
            acf_save_post($_POST['_acf_post_id']);
        }
    }

    do_action( 'acf/input/admin_enqueue_scripts');
    do_action( 'acf/input/admin_head');
}

add_action('wp_loaded', 'acf_register_custom_options_pages');
function acf_register_custom_options_pages()
{
    global $acf_CustomOptionsPages, $wpdb;

    if(!empty($acf_CustomOptionsPages))
    {
        foreach ($acf_CustomOptionsPages as $page)
        {
            $get_cop_post_id = $wpdb->get_var(sprintf(
                "SELECT id FROM %s WHERE post_name = '%s' AND post_type = 'acf-cop';",
                $wpdb->posts,
                $page['menu_slug']
            ));

            $post_args = array(
                'post_title' => $page['page_title'],
                'post_name' => $page['menu_slug'],
                'post_type' => 'acf-cop',
                'post_status' => 'publish',
                'post_content' => serialize($page),
                'comment_status' => 'closed',
                'ping_status' => 'closed'
            );

            if(empty($get_cop_post_id)){
                $new_cop_post_id = wp_insert_post($post_args);
            }else{
                $post_args['ID'] = $get_cop_post_id;
                wp_update_post($post_args);
            }
        }
    }
}
