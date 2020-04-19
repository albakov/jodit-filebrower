<?php

namespace Albakov\JoditFilebrowser\Interfaces;

interface ImageContract
{
    /**
     * @param string $pathToImage
     * @return mixed
     */
    public function load(string $pathToImage);

    /**
     * @param int $w
     * @param int $h
     * @return mixed
     */
    public function resize(int $w, int $h);

    /**
     * @param int $x1
     * @param int $y1
     * @param int $x2
     * @param int $y2
     * @return mixed
     */
    public function crop(int $x1, int $y1, int $x2, int $y2);

    /**
     * @return int
     */
    public function getWidth();

    /**
     * @return int
     */
    public function getHeight();

    /**
     * @return string
     */
    public function getMimeType();

    /**
     * @param string $pathToImage
     * @param int $quality
     * @return mixed
     */
    public function save(string $pathToImage, int $quality);
}
