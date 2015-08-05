<?php

/* Security */
if (!defined('_PS_VERSION_'))
	exit;

/* Checking compatibility with older PrestaShop and fixing it */
if (!defined('_MYSQL_ENGINE_'))
	define('_MYSQL_ENGINE_', 'MyISAM');


class CategoryBackgroundManager extends Module
{
	private   $errors = null;
	protected $fieldImageSettings;
	protected $image_dir = _PS_CAT_IMG_DIR_;

	public function __construct()
	{
		$this->author = 'Philippe';
		$this->name = 'categorybackgroundmanager';
		$this->tab = 'administration';
        $this->version = '0.1.0';
        $this->bootstrap = true;

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);        

		$this->need_instance = 0;

		parent::__construct();

		$this->displayName = $this->l('Category Background Manager');
		$this->description = $this->l('Add background images to categories on the shop');

		$this->confirmUninstall = $this->l('Are you sure you want to delete this module ?');

 		$this->fieldImageSettings = array(
 			'name' => 'image',
 			'dir' => 'c'
 		);
	}

	public function install()
	{
        if (!parent::install() OR
            !$this->alterTable('add') OR
            !$this->addTab() OR 
            !$this->registerHook('actionAdminControllerSetMedia') OR
            !$this->registerHook('actionCategoryUpdate') OR
            !$this->registerHook('actionAdminCategoriesFormModifier') OR
            !$this->registerHook('displayHeader') OR 
            !Configuration::updateValue('CATEGORYBACKGROUNDMANAGER_RECURSIVEBG', 1))
            return false;
        return true;
	}

	public function uninstall()
	{
        if (!parent::uninstall() OR !$this->alterTable('remove'))
            return false;
        return true;
	}

	public function getContent()
	{
		$output = '<h2>'.$this->displayName.'</h2>';
		if (Tools::isSubmit('submit'.Tools::ucfirst($this->name)))
		{
			$default_background_color = Tools::getValue('CATEGORYBACKGROUNDMANAGER_DEFAULTCOLOR');
			$recursive_backgrounds    = Tools::getValue('CATEGORYBACKGROUNDMANAGER_RECURSIVEBG');

			Configuration::updateValue('CATEGORYBACKGROUNDMANAGER_DEFAULTCOLOR', $default_background_color);
			Configuration::updateValue('CATEGORYBACKGROUNDMANAGER_RECURSIVEBG', $recursive_backgrounds);

			if (isset($this->errors) && count($this->errors))
				$output .= $this->displayError(implode('<br />', $this->errors));
			else
				$output .= $this->displayConfirmation($this->l('Settings updated'));
		}
		return $output.$this->displayForm();
	}

    public function alterTable($method)
    {
        switch ($method) {
            case 'add':
                $sql = 'ALTER TABLE ' . _DB_PREFIX_ . 'category ADD `background_color` VARCHAR (32) ';
                break;
             
            case 'remove':
                $sql = 'ALTER TABLE ' . _DB_PREFIX_ . 'category DROP COLUMN `background_color`';
                break;
        }
         
        if(!Db::getInstance()->Execute($sql))
            return false;
        return true;
    }    

	public function displayForm()
	{

		$this->context->smarty->assign('request_uri', Tools::safeOutput($_SERVER['REQUEST_URI']));
		$this->context->smarty->assign('path', $this->_path);
		$this->context->smarty->assign('CATEGORYBACKGROUNDMANAGER_DEFAULTCOLOR', pSQL(Tools::getValue('CATEGORYBACKGROUNDMANAGER_DEFAULTCOLOR', Configuration::get('CATEGORYBACKGROUNDMANAGER_DEFAULTCOLOR'))));
		$this->context->smarty->assign('CATEGORYBACKGROUNDMANAGER_RECURSIVEBG', pSQL(Tools::getValue('CATEGORYBACKGROUNDMANAGER_RECURSIVEBG', Configuration::get('CATEGORYBACKGROUNDMANAGER_RECURSIVEBG'))));
		$this->context->smarty->assign('submitName', 'submit'.Tools::ucfirst($this->name));
		$this->context->smarty->assign('errors', $this->errors);

		// You can return html, but I prefer this new version: use smarty in admin, :)
		return $this->display(__FILE__, 'views/templates/admin/configure.tpl');
	}    


	public function hookActionAdminCategoriesFormModifier ($params)
	{
		//ddd($params);
		if (Validate::isLoadedObject($category = new Category((int)Tools::getValue('id_category'))))
	    {
			$image = _PS_CAT_IMG_DIR_.$category->id.'_background.jpg';

			$image_url = ImageManager::thumbnail($image, 
												 'category_'.(int)$category->id.'_background.jpg', 
												 350, 
												 ImageType::getImagesTypes('categories'), 
												 true, 
												 true);

			$image_size = file_exists($image) ? filesize($image) / 1000 : false;

			$params['fields'][0]['form']['input'][] = array(
						'type' => 'text',
						'label' => $this->l('background_color'),
						'name' => 'background_color',
						'lang' => false,
						'required' => false,
						'class' => 'copy2friendlyUrl',
						'hint' => $this->l('Invalid characters:').' <>;={}',
					);
			$params['fields'][0]['form']['input'][] = array(
					    'type' => 'file',
					    'label' => $this->l('Background Image'),
					    'name' => 'background_image',
					    'display_image' => true,
					    'image' => $image_url ? $image_url : false,
					    'size' => $image_size,
					    'delete_url' => "index.php?controller=AdminCategoryBackgroundManager&id_category=".$category->id.'&token='.Tools::getAdminTokenLite('AdminCategoryBackgroundManager').'&deletebackground_image=1',
					    'hint' => $this->l('Upload a category background image from your computer.'),
				);	

			$params['fields_value']["background_color"] = $this->getBackgroundColors(Tools::getValue('id_category'));

		}
	}

	public function hookActionCategoryUpdate($params)
	{

	    $id_category = (int)Tools::getValue('id_category');

	    // HANDLING BACKGROUND COLOR
        if(!Db::getInstance()->update('category', array('background_color'=> pSQL(Tools::getValue('background_color'))) ,' id_category = ' .$id_category ))
            $this->context->controller->_errors[] = Tools::displayError('Error: ').mysql_error();

        // HANDLING IMAGES
		$ret = $this->uploadImage($id_category.'_background', 'background_image', $this->fieldImageSettings['dir'].'/');

		if ($id_category && isset($_FILES) && count($_FILES) && $_FILES['background_image']['name'] != null && file_exists(_PS_CAT_IMG_DIR_.$id_category.'_background.jpg')) {

		    $images_types = ImageType::getImagesTypes('categories');
		    foreach ($images_types as $k => $image_type)
		    {
		        ImageManager::resize(
		            _PS_CAT_IMG_DIR_.$id_category.'_background.jpg',
		            _PS_CAT_IMG_DIR_.$id_category.'_background-'.stripslashes($image_type['name']).'.jpg',
		            (int)$image_type['width'], (int)$image_type['height']
		        );
		    }
		}
		 
		return $ret;        
	 
	}	


	public function getBackgroundColors($id_category)
	{
	    $result = Db::getInstance()->ExecuteS('SELECT background_color FROM '._DB_PREFIX_.'category WHERE id_category = ' . (int)$id_category);

	    if(!$result)
	        return array();

	    foreach ($result as $field) {
	        return $field['background_color'];
	    }

	}	

	protected function uploadImage($id, $name, $dir, $ext = false, $width = null, $height = null)
	{
	    if (isset($_FILES[$name]['tmp_name']) && !empty($_FILES[$name]['tmp_name']))
	    {
	        // Delete old image
	        if (Validate::isLoadedObject($category = new Category((int)Tools::getValue('id_category'))))
	            $this->deleteImageBackground(flase, $category);
	        else
	            return false;
	 
	        // Check image validity
	        $max_size = isset($this->max_image_size) ? $this->max_image_size : 0;
	        if ($error = ImageManager::validateUpload($_FILES[$name], Tools::getMaxUploadSize($max_size)))
	            $this->errors[] = $error;
	 
	        $tmp_name = tempnam(_PS_TMP_IMG_DIR_, 'PS');
	        if (!$tmp_name)
	            return false;
	 
	        if (!move_uploaded_file($_FILES[$name]['tmp_name'], $tmp_name))
	            return false;
	 
	        // Evaluate the memory required to resize the image: if it's too much, you can't resize it.
	        if (!ImageManager::checkImageMemoryLimit($tmp_name))
	            $this->errors[] = Tools::displayError('Due to memory limit restrictions, this image cannot be loaded. Please increase your memory_limit value via your server\'s configuration settings. ');
	 
	        // Copy new image
	        if (empty($this->errors) && !ImageManager::resize($tmp_name, _PS_IMG_DIR_.$dir.$id.'.'.'jpg', (int)$width, (int)$height, ($ext ? $ext : 'jpg')))
	            $this->errors[] = Tools::displayError('An error occurred while uploading the image.');
	 
	        if (count($this->errors))
	            return false;


	        if ($this->afterImageUpload())
	        {
	            unlink($tmp_name);
	            return true;
	        }
	        return false;
	    }
	    return true;
	}	


    protected function deleteImageBackground($force_delete = false, $category)
    {
        if (!$category->id)
            return false;
         
        if ($force_delete || !$category->hasMultishopEntries())
        {
            /* Deleting object images and thumbnails (cache) */
            if ($this->image_dir)
            {
                if (file_exists($this->image_dir.$category->id.'_background.jpg')
                    && !unlink($this->image_dir.$category->id.'_background.jpg'))
                    return false;
            }
            if (file_exists(_PS_TMP_IMG_DIR_.'category_'.$category->id.'_background.jpg')
                && !unlink(_PS_TMP_IMG_DIR_.'category_'.$category->id.'_background.jpg'))
                return false;
            if (file_exists(_PS_TMP_IMG_DIR_.'category_mini_'.$category->id.'_background.jpg')
                && !unlink(_PS_TMP_IMG_DIR_.'category_mini_'.$category->id.'_background.jpg'))
                return false;
     
            $types = ImageType::getImagesTypes();
            foreach ($types as $image_type)
                if (file_exists($this->image_dir.$category->id.'_background-'.stripslashes($image_type['name']).'.jpg')
                && !unlink($this->image_dir.$category->id.'_background-'.stripslashes($image_type['name']).'.jpg'))
                    return false;
        }

        return true;
    }

	protected function afterImageUpload()
	{
		return true;
	}	

	public function addTab()
	{
	    $tab = new Tab();
	    $tab->name = array();
	    foreach (Language::getLanguages() as $language)
	        $tab->name[$language['id_lang']] = 'AdminCategoryBackgroundManager';
	    $tab->class_name = 'AdminCategoryBackgroundManager';        

	    $tab->id_parent = -1;
	    $tab->module = $this->name;
	    if(!$tab->add())
	        return false;
	    return true;
	}	


	public function hookDisplayHeader ($params)
	{

		if($this->context->controller->php_self != "category" && $this->context->controller->php_self != "product") {
			return "";
		}

		$category = $this->context->controller->getCategory();
		
		$style = '<style>
					body {
						background-color: '.$this->getBackgroundColorForCurrentCat($category, Configuration::get('CATEGORYBACKGROUNDMANAGER_RECURSIVEBG')).' ;
					}

					.columns-container {
						background-image: url("'.$this->getBackgroundImageForCurrentCat($category, Configuration::get('CATEGORYBACKGROUNDMANAGER_RECURSIVEBG')).'") !important;
						background-position: top center !important;
				    	background-repeat: no-repeat !important;		
					}
				</style>';

		return $style;	
		
	}	


	protected function getBackgroundColorForCurrentCat ($category, $recursive = true)
	{
		if ($background_color = $this->getBackgroundColors($category->id)) {

			return $background_color;
		}
		elseif ($category->id_parent != "2" && $recursive) {

			return $this->getBackgroundColorForCurrentCat(new Category($category->id_parent));
		}
		else {

			return Configuration::get('CATEGORYBACKGROUNDMANAGER_DEFAULTCOLOR');
		}
	}


	protected function getBackgroundImageForCurrentCat ($category, $recursive = true)
	{
		if (file_exists($this->image_dir.$category->id.'_background.jpg')) {

			return $this->context->link->getMediaLink("/img/c/".$category->id.'_background.jpg');

		}
		elseif($category->id_parent != "2" && $recursive) {

			return $this->getBackgroundImageForCurrentCat(new Category($category->id_parent));
		}
		else {

			return "";	
		}

	}	
}
