<?php

namespace Albakov\JoditFilebrowser\Controllers;

class File
{
    /**
     * @var string $path
     */
    private $path;

    /**
     * @var string $relative
     */
    private $relative;

    /**
     * File constructor.
     * @param string $path
     * @param string $relative
     * @throws \Exception
     */
    public function __construct(string $path, string $relative)
    {
        $path = realpath($path);

        if ($path === '') {
            throw new \Exception('File not exists.', Error::CODE_NOT_EXISTS);
        }

        $this->path = $path;
        $this->relative = $relative;
    }

    /**
     * @param array $source
     * @return bool
     */
    public function isValid(array $source)
    {
        $info = pathinfo($this->path);

        if (!isset($info['extension']) || (!in_array(strtolower($info['extension']), $source['extensions']))) {
            return false;
        }

        if (in_array(strtolower($info['extension']), $source['extensions']) && !$this->isImage()) {
            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    public function remove()
    {
        $file = basename($this->path);
        $thumb = dirname($this->path) . Helper::DS . '.thumb' . Helper::DS . $file;

        if (file_exists($thumb)) {
            unlink($thumb);

            if (count(glob(dirname($thumb) . Helper::DS . '*')) === 0) {
                rmdir(dirname($thumb));
            }
        }

        return unlink($this->path);
    }

    /**
     * @return false|string|string[]
     */
    public function getFullPathToFile()
    {
        return str_replace('\\', Helper::DS, $this->path);
    }

    /**
     * @return string
     */
    public function getFolder()
    {
        return dirname($this->getFullPathToFile()) . Helper::DS;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return basename($this->path);
    }

    /**
     * @return false|int
     */
    public function getSize()
    {
        return filesize($this->getFullPathToFile());
    }

    /**
     * @return false|int
     */
    public function getTime()
    {
        return filemtime($this->getFullPathToFile());
    }

    /**
     * @return string|string[]
     */
    public function getExtension()
    {
        return pathinfo($this->getFullPathToFile(), PATHINFO_EXTENSION);
    }

    /**
     * @param array $source
     * @return string|string[]|null
     */
    public function getPathByRoot(array $source)
    {
        $path = preg_replace('#[\\\\/]#', '/', $this->getFullPathToFile());
        $root = preg_replace('#[\\\\/]#', '/', $this->getRootPathWithRelative($source) . Helper::DS);

        return str_replace($root, '', $path);
    }

    /**
     * @return bool
     */
    public function isImage()
    {
        try {
            if (!function_exists('exif_imagetype')) {
                function exif_imagetype($filename)
                {
                    if ((list(, , $type) = getimagesize($filename)) !== false) {
                        return $type;
                    }

                    return false;
                }
            }

            return in_array(exif_imagetype($this->getFullPathToFile()), [IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_BMP]);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param array $source
     * @return false|string
     */
    public function getRootPathWithRelative(array $source)
    {
        return realpath($source['root'] . Helper::DS . $this->relative);
    }
}
