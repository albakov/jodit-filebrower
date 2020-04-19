<?php

namespace App\Http\Controllers;

use Albakov\JoditFilebrowser\Interfaces\ImageContract;
use claviska\SimpleImage;

class Image implements ImageContract
{
    /**
     * @var SimpleImage
     */
    public $img;

    /**
     * Image constructor.
     */
    public function __construct()
    {
        // Here you can use any library for images
        // For example, here we use SimpleImage library from claviska (search on GitHub)
        $this->img = new SimpleImage();
    }

    /**
     * @param string $pathToImage
     * @return $this|mixed
     * @throws \Exception
     */
    public function load(string $pathToImage)
    {
        $this->img->fromFile($pathToImage);

        return $this;
    }

    /**
     * @param int $w
     * @param int $h
     * @return $this|mixed
     */
    public function resize(int $w, int $h)
    {
        $this->img->resize($w, $h);

        return $this;
    }

    /**
     * @param int $x1
     * @param int $y1
     * @param int $x2
     * @param int $y2
     * @return $this|mixed
     */
    public function crop(int $x1, int $y1, int $x2, int $y2)
    {
        $this->img->crop($x1, $y1, $x2, $y2);

        return $this;
    }

    /**
     * @return int
     */
    public function getWidth()
    {
        return $this->img->getWidth();
    }

    /**
     * @return int
     */
    public function getHeight()
    {
        return $this->img->getHeight();
    }

    /**
     * @return string
     */
    public function getMimeType()
    {
        return $this->img->getMimeType();
    }

    /**
     * @param string $pathToImage
     * @param int $quality
     * @return $this|mixed
     * @throws \Exception
     */
    public function save(string $pathToImage, int $quality)
    {
        $this->img->toFile($pathToImage, $this->getMimeType(), $quality);

        return $this;
    }
}
