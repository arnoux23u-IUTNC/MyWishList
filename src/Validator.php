<?php

namespace mywishlist;

use \mywishlist\ImgCropper;

class Validator
{

    public static function validateFile($container, $file, $finfo,  $type)
    {
        if ($file->getError() !== UPLOAD_ERR_OK) 
            return "error";
        if (!in_array(strtolower($file->getClientMediaType()), ['image/png', 'image/jpeg', 'image/jpg']))
            return "typeerr";
        if ($file->getSize() > 10000000 || $file->getSize() < 2000)
            return "sizeerr";
        if($type === "user"){
            $dir = $container['users_upload_dir'].DIRECTORY_SEPARATOR;
            if (!is_writable($dir))
                return "writeerr";
            try{
                $file->moveTo($container['users_upload_dir'].DIRECTORY_SEPARATOR . $finfo[0].".".$finfo[1]);
                (new ImgCropper($container, $finfo))->save();
                return "ok";
            }catch(\Exception){
                return "error";
            }
        }else{
            if (file_exists($container['items_upload_dir'].DIRECTORY_SEPARATOR . $finfo[0].".".$finfo[1]))
                return "fileexist";
            if (!is_writable($container['items_upload_dir'].DIRECTORY_SEPARATOR)) 
                return "writeerr";
            try{
                $file->moveTo($container['items_upload_dir'].DIRECTORY_SEPARATOR . $finfo[0].".".$finfo[1]);
                return "ok";
            }catch(\Exception){
                return "error";
            }
        }
    }

    public static function validateStrings($strings)
    {
        foreach ($strings as $key => $string) {
            if (empty(str_replace(" ", "", $string))) {
                return false;
            }
        }
        return true;
    }

    public static function validatePassword($password, $password_confirm)
    {
        if(empty($password) || empty($password_confirm))
            return false;
        $validPassword = preg_match('@[0-9]@', $password) && preg_match('@[A-Z]@', $password) && preg_match('@[a-z]@', $password) && preg_match('@[^\w]@', $password);
        return $validPassword && strlen($password) > 13 && $password === $password_confirm;
    }

}
