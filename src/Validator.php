<?php

namespace mywishlist;

use Exception;
use Slim\Container;
use Slim\Http\UploadedFile;

/**
 * Class Validator
 * Validates data
 * @author Guillaume ARNOUX
 * @package mywishlist
 */
class Validator
{

    /**
     * Validate a file
     * @param Container $container
     * @param UploadedFile $file
     * @param array $finfo
     * @param string $type usage of file, user or item
     * @return string message
     */
    public static function validateFile(Container $container, UploadedFile $file, array $finfo, string $type): string
    {
        if ($file->getError() !== UPLOAD_ERR_OK)
            return "error";
        if (!in_array(strtolower($file->getClientMediaType()), ['image/png', 'image/jpeg', 'image/jpg']))
            return "typeerr";
        if ($file->getSize() > 10000000 || $file->getSize() < 2000)
            return "sizeerr";
        if ($type === "user") {
            $dir = $container['users_upload_dir'] . DIRECTORY_SEPARATOR;
            if (!is_writable($dir))
                return "writeerr";
            try {
                $file->moveTo($container['users_upload_dir'] . DIRECTORY_SEPARATOR . $finfo[0] . "." . $finfo[1]);
                (new ImgCropper($container, $finfo))->save();
                return "ok";
            } catch (Exception) {
                return "error";
            }
        } else {
            if (file_exists($container['items_upload_dir'] . DIRECTORY_SEPARATOR . $finfo[0] . "." . $finfo[1]))
                return "fileexist";
            if (!is_writable($container['items_upload_dir'] . DIRECTORY_SEPARATOR))
                return "writeerr";
            try {
                $file->moveTo($container['items_upload_dir'] . DIRECTORY_SEPARATOR . $finfo[0] . "." . $finfo[1]);
                return "ok";
            } catch (Exception) {
                return "error";
            }
        }
    }

    /**
     * Validate some strings
     * @param array $strings
     * @return bool true if valid
     */
    public static function validateStrings(array $strings): bool
    {
        foreach ($strings as $string) {
            if (empty(str_replace(" ", "", $string))) {
                return false;
            }
        }
        return true;
    }

    /**
     * Validate a password
     * @param string $password
     * @param string $password_confirm confirmation, can be equals to password
     * @return bool true if valid
     */
    public static function validatePassword(string $password, string $password_confirm): bool
    {
        if (empty($password) || empty($password_confirm))
            return false;
        $validPassword = preg_match('@[0-9]@', $password) && preg_match('@[A-Z]@', $password) && preg_match('@[a-z]@', $password) && preg_match('@[^\w]@', $password);
        return $validPassword && strlen($password) > 13 && $password === $password_confirm;
    }

}
