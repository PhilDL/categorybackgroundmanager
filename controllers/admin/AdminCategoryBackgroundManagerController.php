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

    public function __construct()
    {
        parent::__construct();

        $this->helper = new CategoryBackgroundHelper();
    }

    /**
     * postProcess happens when this controller has been called from the "delete" button under the Background Image
     * for the Category uploaded. We're gonna check if the delete request has been sent and delete the Imafe if so.
     * @return Void nothing
     */
	public function postProcess()
	{
        if(Tools::getValue('deletebackground_image')) {
            if (Validate::isLoadedObject($category = new Category((int)Tools::getValue('id_category'))))
                if($this->helper->deleteImageBackground(true, $category))
                    Tools::redirectAdmin( "index.php?controller=AdminCategories&updatecategory&id_category=".$category->id.'&token='.Tools::getAdminTokenLite('AdminCategories'));
        }       

        return parent::postProcess();
	}

}
