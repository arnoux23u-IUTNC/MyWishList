<?php

namespace mywishlist;

use GdImage;
use Slim\Container;

/**
 * Image Cropper Class
 * Utils to circle crop an image
 * @author Guillaume ARNOUX
 * @package mywishlist
 */
class ImgCropper
{

    private GdImage $image;
    private array $finfo;
    private Container $container;

    /**
     * Constructor
     * @param Container $container
     * @param array $finfo file name and extension
     */
    public function __construct(Container $container, array $finfo)
    {
        $this->image = match ($finfo[1]) {
            "jpg", "jpeg" => imagecreatefromjpeg($container['users_img_dir'] . DIRECTORY_SEPARATOR . $finfo[0] . "." . $finfo[1]),
            "png" => imagecreatefrompng($container['users_img_dir'] . DIRECTORY_SEPARATOR . $finfo[0] . "." . $finfo[1])
        };
        $this->container = $container;
        $this->finfo = $finfo;
    }

    /**
     * Save image to disk
     */
    public function save()
    {
        $size = min(imagesx($this->image), imagesy($this->image));
        $newImg = imagecreatetruecolor($size, $size);
        imagecopy($newImg, $this->image, 0, 0, 0, 0, $size, $size);
        $mask = imagecreatetruecolor($size, $size);
        $maskTransparent = imagecolorallocate($mask, 255, 0, 255);
        imagecolortransparent($mask, $maskTransparent);
        imagefilledellipse($mask, $size / 2, $size / 2, $size, $size, $maskTransparent);
        imagecopymerge($newImg, $mask, 0, 0, 0, 0, $size, $size, 100);
        $dstTransparent = imagecolorallocate($newImg, 255, 0, 255);
        imagefill($newImg, 0, 0, $dstTransparent);
        imagefill($newImg, $size - 1, 0, $dstTransparent);
        imagefill($newImg, 0, $size - 1, $dstTransparent);
        imagefill($newImg, $size - 1, $size - 1, $dstTransparent);
        imagecolortransparent($newImg, $dstTransparent);
        imagepng($newImg, $this->container['users_img_dir'] . DIRECTORY_SEPARATOR . $this->finfo[0] . "." . $this->finfo[1]);
    }

}
