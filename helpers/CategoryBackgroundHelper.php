<?php 

/**
* Helper with Image treatment functions and retrieving/showing background colors
* and images.
*/
class CategoryBackgroundHelper
{

    protected $fieldImageSettings;
    protected $image_dir;

    public function __construct()
    {
        $this->fieldImageSettings = array(
            'name' => 'image',
            'dir' => 'c'
        );

        $this->image_dir = _PS_CAT_IMG_DIR_;
    }

    /**
     * upload the background Image to the server
     * @param  String   $id         Image identifier [id_category]_background
     * @param  String   $name       Name of the image (background_category)
     * @param  String   $dir        Directory
     * @param  boolean  $ext    
     * @param  String   $width  
     * @param  String   $height 
     * @return [type]               Success or Failure
     */
    public function uploadImage($id, $name, $dir, $ext = false, $width = null, $height = null)
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

            unlink($tmp_name);
            
            return true;

        }
        return true;
    }   

    /**
     * Delete the background image of a category after update or force delete
     * @param  boolean      $force_delete   force delete occurs when user click on "delete" under image
     * @param  Category     $category       CategoryObject
     * @return Boolean                      Success or Failure
     */
    public function deleteImageBackground($force_delete = false, $category)
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


    public function getBackgroundColorForCurrentCat ($category, $recursive = true)
    {
        if ($background_color = $this->getBackgroundColors($category->id)) {

            return $background_color;
        }
        elseif ($category->id_parent != "2" && $recursive) {

            return $this->getBackgroundColorForCurrentCat(new Category($category->id_parent));
        }
        return Configuration::get('CATEGORYBACKGROUNDMANAGER_DEFAULTCOLOR');
    }


    public function getBackgroundImageForCurrentCat ($category, $recursive = true)
    {
        if (file_exists($this->image_dir.$category->id.'_background.jpg')) {

            return _PS_BASE_URL_."/img/c/".$category->id.'_background.jpg';

        }
        elseif($category->id_parent != "2" && $recursive) {

            return $this->getBackgroundImageForCurrentCat(new Category($category->id_parent));
        }
        return "";  
    }        
    

    /**
     * Retrieve the background color of a category 
     * @param  String   $id_category    Category id of the description
     * @return String                   The color returned in hexadecimal format (#ffffff)
     */
    public function getBackgroundColors($id_category)
    {
        $result = Db::getInstance()->ExecuteS('SELECT background_color FROM '._DB_PREFIX_.'category WHERE id_category = ' . (int)$id_category);

        if(!$result)
            return array();

        foreach ($result as $field) {
            return $field['background_color'];
        }
    }             
}