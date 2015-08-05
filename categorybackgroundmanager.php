<?php

/* Security */
if (!defined('_PS_VERSION_'))
	exit;

/* Checking compatibility with older PrestaShop and fixing it */
if (!defined('_MYSQL_ENGINE_'))
	define('_MYSQL_ENGINE_', 'MyISAM');

require_once(_PS_MODULE_DIR_.'categorybackgroundmanager/helpers/CategoryBackgroundHelper.php');

class CategoryBackgroundManager extends Module
{
	private   $errors = null;
	protected $fieldImageSettings;
	protected $helper;


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

 		$this->helper = new CategoryBackgroundHelper();
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
            !Configuration::updateValue('CATEGORYBACKGROUNDMANAGER_RECURSIVEBG', 1) OR 
            !Configuration::updateValue('CATEGORYBACKGROUNDMANAGER_IMAGEPOSITION', ".columns-container"))
            return false;
        return true;
	}

	public function uninstall()
	{
        if (!parent::uninstall() OR !$this->alterTable('remove'))
            return false;
        return true;
	}
	

	/**
	 * alterTable used at the installation of the module to add a field in the database table "categor"
	 * @param  String 	$method Switch changing state depending on install or uninstall
	 * @return Boolean        	Success or Failure
	 */
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

	/**
	 * Unfortunately a Tab is required to register the controller AdminCategoryBackgroundManagerController
	 * for whatever reason. So a Tab is created with a parent of "-1" so it won't be visible 
	 * in the admin panel.
	 */
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

	/**
	 * Main method of the module showing the form and processing the submitted data from the configuration panel of the module
	 * @return String  Html containing the form and the confirmation/error messages
	 */
	public function getContent()
	{
		$output = "";
		if (Tools::isSubmit('submit'.Tools::ucfirst($this->name))) {
;
			$default_background_color = Tools::getValue('color');
			$recursive_backgrounds    = Tools::getValue('CATEGORYBACKGROUNDMANAGER_RECURSIVEBG');
			$image_position           = Tools::getValue('CATEGORYBACKGROUNDMANAGER_IMAGEPOSITION');

			Configuration::updateValue('CATEGORYBACKGROUNDMANAGER_DEFAULTCOLOR', $default_background_color);
			Configuration::updateValue('CATEGORYBACKGROUNDMANAGER_RECURSIVEBG', $recursive_backgrounds);
			Configuration::updateValue('CATEGORYBACKGROUNDMANAGER_IMAGEPOSITION', $image_position);

			if (isset($this->errors) && count($this->errors))
				$output = $this->displayError(implode('<br />', $this->errors));
			else
				$output = $this->displayConfirmation($this->l('Settings updated'));
		}
		return $output.$this->displayForm();
	}

	/**
	 * Form displayed in the configuration of the module
	 * @return String Html containing the form see views/templates/admin/configure.tpl
	 */
	public function displayForm()
	{
		$this->context->smarty->assign('request_uri', Tools::safeOutput($_SERVER['REQUEST_URI']));
		$this->context->smarty->assign('path', $this->_path);
		$this->context->smarty->assign('CATEGORYBACKGROUNDMANAGER_DEFAULTCOLOR', pSQL(Tools::getValue('CATEGORYBACKGROUNDMANAGER_DEFAULTCOLOR', Configuration::get('CATEGORYBACKGROUNDMANAGER_DEFAULTCOLOR'))));
		$this->context->smarty->assign('CATEGORYBACKGROUNDMANAGER_RECURSIVEBG', pSQL(Tools::getValue('CATEGORYBACKGROUNDMANAGER_RECURSIVEBG', Configuration::get('CATEGORYBACKGROUNDMANAGER_RECURSIVEBG'))));
		$this->context->smarty->assign('CATEGORYBACKGROUNDMANAGER_IMAGEPOSITION', pSQL(Tools::getValue('CATEGORYBACKGROUNDMANAGER_IMAGEPOSITION', Configuration::get('CATEGORYBACKGROUNDMANAGER_IMAGEPOSITION'))));
		$this->context->smarty->assign('submitName', 'submit'.Tools::ucfirst($this->name));
		$this->context->smarty->assign('errors', $this->errors);
		$this->context->smarty->assign('colorpicker_path', __PS_BASE_URI__.'js/jquery/plugins/jquery.colorpicker.js');

		return $this->display(__FILE__, 'views/templates/admin/configure.tpl');
	}    


	/**
	 * hook called when building the form to update category informations in the admin panel
	 * @param  Array 	$fields_data  fields of the form to be built in AdminCategoriesController renderForm()
	 * @return Void
	 */
	public function hookActionAdminCategoriesFormModifier ($fields_data)
	{
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

			$fields_data['fields'][0]['form']['input'][] = array(
						'type' => 'color',
						'label' => $this->l('background_color'),
						'name' => 'background_color',
						'lang' => false,
						'required' => false,
						'hint' => $this->l('Invalid characters:').' <>;={}',
					);
			$fields_data['fields'][0]['form']['input'][] = array(
					    'type' => 'file',
					    'label' => $this->l('Background Image'),
					    'name' => 'background_image',
					    'display_image' => true,
					    'image' => $image_url ? $image_url : false,
					    'size' => $image_size,
					    'delete_url' => "index.php?controller=AdminCategoryBackgroundManager&id_category=".$category->id.'&token='.Tools::getAdminTokenLite('AdminCategoryBackgroundManager').'&deletebackground_image=1',
					    'hint' => $this->l('Upload a category background image from your computer.'),
				);	

			$fields_data['fields_value']["background_color"] = $this->helper->getBackgroundColors(Tools::getValue('id_category'));
		}
	}


	/**
	 * hook triggered when User save modification on Category page in the admin panel 
	 * it saves the background color and upload the image to the server
	 * @param  Array 	$params Cookies 
	 * @return Boolean          Boolean verification of operations  
	 */
	public function hookActionCategoryUpdate($params)
	{

	    $id_category = (int)Tools::getValue('id_category');

	    // HANDLING BACKGROUND COLOR
        if(!Db::getInstance()->update('category', array('background_color'=> pSQL(Tools::getValue('background_color'))) ,' id_category = ' .$id_category ))
            $this->context->controller->_errors[] = Tools::displayError('Error: ').mysql_error();

        // HANDLING IMAGES
		$ret = $this->helper->uploadImage($id_category.'_background', 'background_image', $this->fieldImageSettings['dir'].'/');

		return $ret;        
	}	


	/**
	 * hook to integrate styling to body and columns-container in pages when we are on product and category pages
	 * @param  Array 	$params Cookies 
	 * @return String         	String containing style tags
	 */
	public function hookDisplayHeader ($params)
	{
		if($this->context->controller->php_self != "category" && $this->context->controller->php_self != "product") {
			return "";
		}

		$category = $this->context->controller->getCategory();
		$style = '<style>
					body {
						background-color: '.$this->helper->getBackgroundColorForCurrentCat($category, Configuration::get('CATEGORYBACKGROUNDMANAGER_RECURSIVEBG')).' ;
					}

					'.Configuration::get('CATEGORYBACKGROUNDMANAGER_IMAGEPOSITION').' {
						background-image: url("'.$this->helper->getBackgroundImageForCurrentCat($category, Configuration::get('CATEGORYBACKGROUNDMANAGER_RECURSIVEBG')).'") !important;
						background-position: top center !important;
				    	background-repeat: no-repeat !important;		
					}
				</style>';

		return $style;		
	}	

}
