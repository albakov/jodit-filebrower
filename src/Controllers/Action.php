<?php

namespace Albakov\JoditFilebrowser\Controllers;

class Action extends Base
{
    /**
     * @throws \Exception
     */
    public function folders()
    {
        $sources = [];

        foreach ($this->config['sources'] as $key => $source) {
            if ($this->request->source !== 'default' && $key !== $this->request->source && $this->request->path !== './') {
                continue;
            }

            $path = $this->getCurrentSourcePathWithRelative();

            try {
                $this->havePermission($this->request->action, $path);
            } catch (\Exception $e) {
                continue;
            }

            $sourceData = [
                'baseurl' => $source['baseurl'],
                'path' => str_replace(realpath($source['root']) . Helper::DS, '', $path),
                'folders' => []
            ];

            $sourceData['folders'][] = $path == realpath($source['root']) . Helper::DS ? '.' : '..';

            $dir = opendir($path);

            while ($file = readdir($dir)) {
                if ($file != '.' && $file != '..' && is_dir($path . $file) && !in_array($file, $this->config['excludeDirectoryNames'])) {
                    $sourceData['folders'][] = $file;
                }
            }

            $sources[$key] = $sourceData;
        }

        $this->response['data']['sources'] = $sources;
    }

    /**
     * @throws \Exception
     */
    public function folderRename()
    {
        $this->renamePath();
        $this->response['data']['messages'][] = $this->config['locale']['folder_renamed'];
    }

    /**
     * @throws \Exception
     */
    public function folderRemove()
    {
        $file_path = false;
        $path = $this->getCurrentSourcePathWithRelative();

        $this->havePermission($this->request->action, $path);

        if (realpath($path) && strpos(realpath($path), $this->getCurrentSourceRoot()) !== false) {
            $file_path = realpath($path);
        }

        if ($file_path && file_exists($file_path)) {
            if (is_dir($file_path)) {
                $this->deleteDir($file_path);
            } else {
                throw new \Exception('It is not a directory!', Error::CODE_IS_NOT_WRITABLE);
            }
        } else {
            throw new \Exception('Directory not exists.', Error::CODE_NOT_EXISTS);
        }

        $this->response['data']['messages'][] = $this->config['locale']['folder_removed'];
    }

    /**
     * @throws \Exception
     */
    public function folderCreate()
    {
        $source = $this->getCompatibleSource($this->request->source);
        $destinationPath = $this->getCurrentSourcePathWithRelative();

        $this->havePermission($this->request->action, $destinationPath);

        $folderName = Helper::getSafeFileName($this->request->name);

        if ($destinationPath) {
            if ($folderName) {
                if (!realpath($destinationPath . $folderName)) {
                    mkdir($destinationPath . $folderName, $this->getConfigOption($source, 'defaultPermission'));

                    if (is_dir($destinationPath . $folderName)) {
                        $this->response['data']['messages'][] = $this->config['locale']['folder_created'];
                        return;
                    }

                    throw new \Exception('Directory was not created.', Error::CODE_NOT_EXISTS);
                }

                throw new \Exception('Directory already exists.', Error::CODE_NOT_ACCEPTABLE);
            }

            throw new \Exception('The name for new directory has not been set.', Error::CODE_NOT_ACCEPTABLE);
        }

        throw new \Exception('The destination directory has not been set.', Error::CODE_NOT_ACCEPTABLE);
    }

    /**
     * @throws \Exception
     */
    public function folderMove()
    {
        $this->movePath();
    }

    /**
     * Get files
     */
    public function files()
    {
        $sources = [];

        $currentSource = $this->config['sources'][$this->request->source];

        foreach ($this->config['sources'] as $key => $source) {
            if ($this->request->source !== 'default' && $currentSource !== $source && $this->request->path !== './') {
                continue;
            }

            $sources[$key] = $this->readFiles($source);
        }

        $this->response['data']['sources'] = $sources;
    }

    /**
     * @throws \Exception
     */
    public function fileUpload()
    {
        $source = $this->getCompatibleSource($this->request->source);
        $root = $this->getCurrentSourceRoot();
        $path = $this->getCurrentSourcePathWithRelative();

        $this->havePermission($this->request->action, $path);

        $messages = [];
        $isImages = [];

        $files = $this->move($source, $path);

        $files = array_map(function (File $file) use ($source, $root, &$isImages) {
            $messages[] = str_replace(':file', $file->getName(), $this->config['locale']['file_uploaded']);
            $isImages[] = $file->isImage();
            return str_replace($root, '', $file->getFullPathToFile());
        }, $files);

        if (count($files) === 0) {
            throw new \Exception('No files have been uploaded.', Error::CODE_NO_FILES_UPLOADED);
        }

        $this->response['data'] = [
            'baseurl' => $source['baseurl'] . Helper::DS,
            'messages' => $messages,
            'files' => $files,
            'isImages' => $isImages
        ];
    }

    /**
     * @throws \Exception
     */
    public function fileRemove()
    {
        $file_path = false;

        $path = $this->getCurrentSourcePathWithRelative();

        $this->havePermission($this->request->action, $path);

        $target = $this->request->name;

        if (realpath($path . $target) && strpos(realpath($path . $target), $this->getCurrentSourceRoot()) !== false) {
            $file_path = realpath($path . $target);
        }

        if (!$file_path || !file_exists($file_path)) {
            throw new \Exception("File or directory not exists {$path}{$target}.", Error::CODE_NOT_EXISTS);
        }

        if (is_file($file_path)) {
            $file = new File($file_path, $this->request->path);

            if (!$file->remove()) {
                $error = (object)error_get_last();
                throw new \Exception("Delete failed! {$error->message}.", Error::CODE_IS_NOT_WRITABLE);
            }
        } else {
            throw new \Exception('It is not a file!', Error::CODE_IS_NOT_WRITABLE);
        }

        $this->response['data']['messages'][] = $this->config['locale']['file_removed'];
    }

    /**
     * @throws \Exception
     */
    public function fileRename()
    {
        $this->renamePath();
        $this->response['data']['messages'][] = $this->config['locale']['file_renamed'];
    }

    /**
     * @throws \Exception
     */
    public function fileMove()
    {
        $this->movePath();
    }

    /**
     * @throws \Exception
     */
    public function imageResize()
    {
        $source = $this->getCompatibleSource($this->request->source);
        $path = $this->getCurrentSourcePathWithRelative();

        $this->havePermission($this->request->action, $path);

        $info = $this->getImageEditorInfo();

        $w = (int)$info['box']['w'];
        $h = (int)$info['box']['h'];

        if ($w <= 0) {
            throw new \Exception('Width not specified.', Error::CODE_BAD_REQUEST);
        }

        if ($h <= 0) {
            throw new \Exception('Height not specified.', Error::CODE_BAD_REQUEST);
        }

        $quality = $this->getConfigOption($source, 'quality');

        $info['img']->resize($w, $h);
        $info['img']->save($info['path'] . $info['newname'], $quality);

        $this->response['data']['messages'][] = $this->config['locale']['image_resized'];
    }

    /**
     * @throws \Exception
     */
    public function imageCrop()
    {
        $source = $this->getCompatibleSource($this->request->source);

        $this->havePermission($this->request->action, $this->getCurrentSourcePathWithRelative());

        $info = $this->getImageEditorInfo();

        $x1 = (int)$info['box']['x'];
        $y1 = (int)$info['box']['y'];

        $w = (int)$info['box']['w'];
        $h = (int)$info['box']['h'];

        if ($x1 < 0 || $x1 > (int)$info['width']) {
            throw new \Exception('Start X not specified', Error::CODE_BAD_REQUEST);
        }

        if ($y1 < 0 || $y1 > (int)$info['height']) {
            throw new \Exception('Start Y not specified', Error::CODE_BAD_REQUEST);
        }

        if ($w <= 0) {
            throw new \Exception('Width not specified', Error::CODE_BAD_REQUEST);
        }

        if ($h <= 0) {
            throw new \Exception('Height not specified', Error::CODE_BAD_REQUEST);
        }

        $x2 = $x1 + $w;
        $y2 = $y1 + $h;

        $quality = $this->getConfigOption($source, 'quality');

        $info['img']->crop($x1, $y1, $x2, $y2);
        $info['img']->save($info['path'] . $info['newname'], $quality);

        $this->response['data']['messages'][] = $this->config['locale']['image_cropped'];
    }

    /**
     * Set current permissions
     * @throws \Exception
     */
    public function permissions()
    {
        $result = [];
        $path = $this->getCurrentSourcePathWithRelative();

        foreach ($this->config['accessControl'] as $permission => $tmp) {
            if (preg_match('#^[A-Z_]+$#', $permission)) {
                $allow = false;
                try {
                    $this->havePermission($permission, $path);
                    $allow = true;
                } catch (\Exception $e) {
                }

                $result['allow' . Helper::convertToCamelCase($permission)] = $allow;
            }
        }

        $this->response['data']['permissions'] = $result;
    }
}
