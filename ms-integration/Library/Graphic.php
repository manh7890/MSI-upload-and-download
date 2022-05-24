<?php
namespace Library;
class Graphic{
    //
    public static function watermark($fileName,$logo){
        $stamp = imagecreatefrompng($logo);
        $im = imagecreatefromjpeg($fileName);
        $margeRight = 5;
        $margeBottom = 5;
        $sx = imagesX($stamp);
        $sy = imagesY($stamp);
        imagecopy($im, $stamp, imagesX($im) - $sx - $margeRight, imagesY($im) - $sy - $margeBottom,
                 0, 0, imagesX($stamp), imagesY($stamp));
        imagejpeg($im,$fileName);
        imagedestroy($stamp);
        imagedestroy($im);
    }
    //
    public static function resizeImage($fileName){
        $width=500;
        $size=getimagesize($fileName);
        $height=round($width*$size[1]/$size[0]);
        $images_orig = imageCreateFromPng($fileName);
        $photoX = imagesX($images_orig);
        $photoY = imagesY($images_orig);
        $imagesFilter = imagecreatetruecolor($width, $height);
        imagecopyresampled($imagesFilter, $images_orig, 0, 0, 0, 0, $width+1, $height+1, $photoX, $photoY);
        imagejpeg($imagesFilter,$fileName);
        imagedestroy($images_orig);
        imagedestroy($imagesFilter);
    }
    public static function saveBase64Image($base64DataImage, $fileName)
    {
        $base64DataImage = str_replace('data:image/jpeg;base64,', '', $base64DataImage);
        $base64DataImage = str_replace(' ', '+', $base64DataImage);
        //
        $base64DataImage= base64_decode($base64DataImage);
        file_put_contents($fileName, $base64DataImage);


    }
    public static function getRandomColor(){
        $ListColor=array('red','blue','green','orange','grey');
        return $ListColor[array_rand($ListColor)];
    }

    //
}