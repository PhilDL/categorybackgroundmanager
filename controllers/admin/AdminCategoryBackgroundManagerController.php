<?php
/**
* 2007-2015 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2015 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

class AdminCategoryBackgroundManagerController extends ModuleAdminController
{

	public $fieldImageSettings;
	protected $image_dir = _PS_CAT_IMG_DIR_;

    public function __construct()
    {
        parent::__construct();

 		$this->fieldImageSettings = array(
 			'name' => 'image',
 			'dir' => 'c'
 		);
    }

	public function postProcess()
	{
        if(Tools::getValue('deletebackground_image')) {
            if (Validate::isLoadedObject($category = new Category((int)Tools::getValue('id_category'))))
                if($this->deleteImageBackground(true, $category))
                    Tools::redirectAdmin( "index.php?controller=AdminCategories&updatecategory&id_category=".$category->id.'&token='.Tools::getAdminTokenLite('AdminCategories'));
        }       

        return parent::postProcess();
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
}
