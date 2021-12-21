<?php

namespace mywishlist;

use GdImage;

class ImgCropper
{

    private int $width, $height;
    private GdImage $image;
    private array $finfo;
    private $container;

    public function __construct($container, $finfo)
    {
        $this->image = match($finfo[1]){
            "jpg", "jpeg" => imagecreatefromjpeg($container['users_upload_dir'].DIRECTORY_SEPARATOR.$finfo[0].".".$finfo[1]),
            "png"=> imagecreatefrompng($container['users_upload_dir'].DIRECTORY_SEPARATOR.$finfo[0].".".$finfo[1])
        };
        $this->width = imagesx($this->image);
        $this->height = imagesy($this->image);
        $this->container = $container;
        $this->finfo = $finfo;
    }

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
        imagepng($newImg, $this->container['users_upload_dir'].DIRECTORY_SEPARATOR.$this->finfo[0].".".$this->finfo[1]);
    }

}
