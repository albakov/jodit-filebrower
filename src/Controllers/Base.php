<?php

namespace Albakov\JoditFilebrowser\Controllers;

use Albakov\JoditFilebrowser\Interfaces\ImageContract;

class Base
{
    /**
     * @var array $config
     */
    protected $config;

    /**
     * @var array $response
     */
    protected $response;

    /**
     * @var Request $request
     */
    protected $request;

    /**
     * @var ImageContract
     */
    protected $imageHandler;

    /**
     * Base constructor.
     * @param array $config
     * @throws \Exception
     */
    public function __construct(array $config = [])
    {
        $this->setConfig($config);

        $this->request = new Request();
        $this->response = [];

        if (is_null($this->request->path)) {
            $this->request->path = '';
        }
    }

    /**
     * @param ImageContract $imageContract
     * @return $this
     */
    public function setImageHandler(ImageContract $imageContract)
    {
        $this->imageHandler = $imageContract;

        return $this;
    }

    /**
     * @param string $relativePath
     * @return false|string
     * @throws \Exception
     */
    public function getCurrentSourcePathWithRelative(string $relativePath = '')
    {
        $root = $this->getCurrentSourceRoot();

        if ($relativePath === '') {
            $relativePath = $this->request->path ?: '';
        }

        if (realpath($root . $relativePath) && strpos(realpath($root . $relativePath) . Helper::DS, $root) !== false) {
            $root = realpath($root . $relativePath);

            if (is_dir($root)) {
                $root .= Helper::DS;
            }
        } else {
            throw new \Exception('Path does not exist', Error::CODE_NOT_EXISTS);
        }

        return $root;
    }

    /**
     * @param array $source
     * @return array
     * @throws \Exception
     */
    public function readFiles(array $source)
    {
        $path = $this->getCurrentSourcePathWithRelative();

        $sourceData = [
            'baseurl' => $source['baseurl'],
            'path' => str_replace(realpath($source['root']) . Helper::DS, '', $path),
            'files' => []
        ];

        try {
            $this->havePermission($this->request->action, $path);
        } catch (\Exception $e) {
            return $sourceData;
        }

        $dir = opendir($path);

        while ($file = readdir($dir)) {
            if ($file != '.' && $file != '..' && is_file($path . $file)) {
                $file = new File($path . $file, $this->request->path);

                if ($file->isValid($source)) {
                    $item = ['file' => $file->getPathByRoot($source)];
                    $item['changed'] = date($this->config['datetimeFormat'], $file->getTime());
                    $item['size'] = Helper::getHumanFileSize($file->getSize());
                    $item['isImage'] = $file->isImage();

                    $sourceData['files'][] = $item;
                }
            }
        }

        return $sourceData;
    }

    /**
     * @param string $sourceName
     * @return array
     * @throws \Exception
     */
    public function getCompatibleSource(string $sourceName)
    {
        if ($sourceName === 'default') {
            return [
                'root' => $this->config['root'],
                'baseurl' => $this->config['baseurl'],
                'maxFileSize' => $this->config['maxFileSize'],
                'extensions' => $this->config['extensions'],
                'quality' => $this->config['quality'],
                'defaultPermission' => $this->config['defaultPermission'],
            ];
        }

        $source = array_key_exists($sourceName, $this->config['sources']) ? $this->config['sources'][$sourceName] : [];

        if (!is_array($source) || count($source) === 0) {
            throw new \Exception('Source not found.', Error::CODE_NOT_EXISTS);
        }

        return $source;
    }

    /**
     * @param array $source
     * @param string $path
     * @return array
     * @throws \Exception
     */
    public function move(array $source, string $path)
    {
        $files = $_FILES['files'];
        $output = [];

        try {
            if (is_array($files) && isset($files['name']) && is_array($files['name']) && count($files['name']) > 0) {
                foreach ($files['name'] as $i => $file) {
                    if ($files['error'][$i]) {
                        throw new \Exception('Error.', $files['error'][$i]);
                    }

                    $tmpName = $files['tmp_name'][$i];
                    $newPath = Helper::getUniqueFileName($path, Helper::getSafeFileName($files['name'][$i]));

                    if (!move_uploaded_file($tmpName, $newPath)) {
                        if (!is_writable($path)) {
                            throw new \Exception('Destination directory is not writable.', Error::CODE_IS_NOT_WRITABLE);
                        }

                        throw new \Exception('No files have been uploaded.', Error::CODE_NO_FILES_UPLOADED);
                    }

                    $file = new File($newPath, $this->request->path);

                    try {
                        $this->havePermission($this->request->action, $path, pathinfo($file->getFullPathToFile(), PATHINFO_EXTENSION));
                    } catch (\Exception $e) {
                        $file->remove();
                        throw $e;
                    }

                    if (!$file->isValid($source)) {
                        $file->remove();
                        throw new \Exception('File type is not in white list.', Error::CODE_FORBIDDEN);
                    }

                    $maxFileSize = $this->getConfigOption($source, 'maxFileSize');

                    if ($file->getSize() > $maxFileSize) {
                        $file->remove();
                        throw new \Exception('File size exceeds the allowable.', Error::CODE_FORBIDDEN);
                    }

                    $output[] = $file;
                }
            }
        } catch (\Exception $e) {
            foreach ($output as $file) {
                $file->remove();
            }

            throw $e;
        }

        return $output;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getCurrentSourceRoot()
    {
        $source = $this->getCompatibleSource($this->request->source);
        $root = $this->getConfigOption($source, 'root');

        return realpath($root) . Helper::DS;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getImageEditorInfo()
    {
        if (is_null($this->imageHandler)) {
            throw new \Exception('Image handler not set.', Error::CODE_IMAGE_HANDLER_NOT_SET);
        }

        $path = $this->getCurrentSourcePathWithRelative();

        $requestedFileName = $this->request->name;

        $box = ['w' => 0, 'h' => 0, 'x' => 0, 'y' => 0];
        $requestedBox = $this->request->box;

        if ($requestedBox && is_array($requestedBox)) {
            foreach ($box as $key => &$value) {
                $value = isset($requestedBox[$key]) ? $requestedBox[$key] : 0;
            }
        }

        $newName = $this->request->newname ? Helper::getSafeFileName($this->request->newname) : '';

        if (!$path || !$requestedFileName || !file_exists($path . $requestedFileName) || !is_file($path . $requestedFileName)) {
            throw new \Exception('File not exists.', Error::CODE_NOT_EXISTS);
        }

        $img = $this->imageHandler->load($path . $requestedFileName);

        if ($newName) {
            $info = pathinfo($path . $requestedFileName);

            if (!preg_match('#\.(' . $info['extension'] . ')$#i', $newName)) {
                $newName = $newName . '.' . $info['extension'];
            }

            if (!$this->config['allowReplaceSourceFile'] && file_exists($path . $newName)) {
                throw new \Exception('File ' . $newName . ' already exists', Error::CODE_BAD_REQUEST);
            }
        } else {
            $newName = $requestedFileName;
        }

        return [
            'path' => $path,
            'box' => $box,
            'newname' => $newName,
            'img' => $img,
            'width' => $img->getWidth(),
            'height' => $img->getHeight()
        ];
    }

    /**
     * @throws \Exception
     */
    public function renamePath()
    {
        $path = $this->getCurrentSourcePathWithRelative();
        $fromPath = $path . $this->request->name;

        $this->havePermission($this->request->action, $fromPath);

        $newName = Helper::getSafeFileName($this->request->newname);
        $destinationPath = $path . $newName;

        $this->havePermission($this->request->action, $destinationPath);

        if (!$fromPath) {
            throw new \Exception('Need source path.', Error::CODE_BAD_REQUEST);
        }

        if (!$destinationPath) {
            throw new \Exception('Need destination path.', Error::CODE_BAD_REQUEST);
        }

        if (!is_file($fromPath) && !is_dir($fromPath)) {
            throw new \Exception('Path not exists.', Error::CODE_NOT_EXISTS);
        }

        if (is_file($fromPath)) {
            $ext = strtolower(pathinfo($fromPath, PATHINFO_EXTENSION));
            $newExt = strtolower(pathinfo($destinationPath, PATHINFO_EXTENSION));

            if ($newExt !== $ext) {
                $destinationPath .= '.' . $ext;
            }
        }

        if (is_file($destinationPath) || is_dir($destinationPath)) {
            throw new \Exception("New " . basename($destinationPath) . " already exists.", Error::CODE_BAD_REQUEST);
        }

        rename($fromPath, $destinationPath);
    }

    /**
     * @param string $dirPath
     */
    public function deleteDir(string $dirPath)
    {
        if (!is_dir($dirPath)) {
            throw new \InvalidArgumentException("{$dirPath} must be a directory.");
        }

        if (substr($dirPath, strlen($dirPath) - 1, 1) != Helper::DS) {
            $dirPath .= Helper::DS;
        }

        $files = glob($dirPath . '*', GLOB_MARK);

        foreach ($files as $file) {
            if (is_dir($file)) {
                $this->deleteDir($file);
            } else {
                unlink($file);
            }
        }

        rmdir($dirPath);
    }

    /**
     * @param array $source
     * @param string $key
     * @return mixed
     */
    public function getConfigOption(array $source, string $key)
    {
        return array_key_exists($key, $source) ? $source[$key] : $this->config[$key];
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function getAccessOption(string $key)
    {
        return array_key_exists($key, $this->config['accessControl']) ? $this->config['accessControl'][$key] : null;
    }

    /**
     * @param $action
     * @param string $path
     * @param string $fileExtension
     * @return bool
     * @throws \Exception
     */
    public function havePermission($action, string $path, string $fileExtension = '*')
    {
        $errors = [];

        if (!is_null($action)) {
            $permission = Helper::getPermissionRuleFromAction($action);
            $permission = $this->getAccessOption($permission);

            if (is_null($permission) || $permission !== true) {
                $errors[] = "No access for this Action! Action: {$action}.";
            }

            $accessPath = $this->getAccessOption('path');

            if (is_null($accessPath) || strpos($path, $accessPath) !== 0) {
                $errors[] = "No access for this Path! Path: {$path}.";
            }

            $allowedExtensions = $this->getAccessOption('extensions');

            if ($fileExtension !== '*') {
                $errorMessage = "No access for this Extension! Extension: $1. Allowed Extensions: $2";

                if (!is_array($allowedExtensions) && $allowedExtensions !== '*') {
                    if (strtolower($allowedExtensions) !== strtolower($fileExtension)) {
                        $errors[] = str_replace(['$1', '$2'], [$fileExtension, $allowedExtensions], $errorMessage);
                    }
                } elseif (is_array($allowedExtensions) && !in_array(strtolower($fileExtension), $allowedExtensions)) {
                    $errors[] = str_replace(['$1', '$2'], [$fileExtension, $allowedExtensions], $errorMessage);
                }
            }
        }

        if (count($errors) === 0) {
            return true;
        }

        throw new \Exception(implode(', ', $errors), Error::CODE_FORBIDDEN);
    }

    /**
     * @param array $config
     * @throws \Exception
     */
    public function setConfig(array $config)
    {
        if (count($config) === 0 || !isset($config['root']) || !isset($config['baseurl'])) {
            throw new \Exception('Set root and baseurl in config!', Error::CODE_CONFIG_REQUIRED);
        }

        $this->config = require_once __DIR__ . '/../config.php';
        $this->config = array_replace_recursive($this->config, $config);
    }

    /**
     * @throws \Exception
     */
    public function movePath()
    {
        $destinationPath = $this->getCurrentSourcePathWithRelative();
        $sourcePath = $this->getCurrentSourcePathWithRelative($this->request->from);

        $this->havePermission($this->request->action, $destinationPath);
        $this->havePermission($this->request->action, $sourcePath);

        if ($sourcePath) {
            if ($destinationPath) {
                if (is_file($sourcePath) or is_dir($sourcePath)) {
                    rename($sourcePath, $destinationPath . basename($sourcePath));
                } else {
                    throw new \Exception('Not file', Error::CODE_NOT_EXISTS);
                }
            } else {
                throw new \Exception('Need destination path', Error::CODE_BAD_REQUEST);
            }
        } else {
            throw new \Exception('Need source path', Error::CODE_BAD_REQUEST);
        }
    }
}
