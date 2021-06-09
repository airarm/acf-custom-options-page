<?php
if(!defined('ABSPATH')){
    exit;
}

if(!class_exists('ACF_Location_Custom_Options_Page')):

class ACF_Location_Custom_Options_Page extends ACF_Location {

	public function initialize()
    {
		$this->name = 'custom_options_page';
		$this->label = __('Custom Options Page');
		$this->category = 'forms';
		$this->object_type = 'option';
	}

	public function get_values($rule)
    {
		$choices = array();

		$pages = acf_get_custom_options_pages();

		if(!empty($pages))
		{
			foreach($pages as $page)
			{
				$choices[$page->ID] = $page->post_title;
			}
		}
		else
        {
			$choices[] = __('No pages exist');
		}

		return $choices;
	}

    public function match($rule, $screen, $field_group)
    {
        global $acf_CustomOptionsPages;

        $page_slug = !empty($_GET['page']) ? sanitize_text_field($_GET['page']) : '';

        if(empty($page_slug) || empty($acf_CustomOptionsPages)){
            return false;
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
            return false;
        }

        return true;
    }
}

acf_register_location_type('ACF_Location_Custom_Options_Page');

endif;
