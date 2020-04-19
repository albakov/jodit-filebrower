<?php

return [
    'root' => '',
    'baseurl' => '',
    'maxFileSize' => 2 * 1024 * 1024,
    'extensions' => ['jpg', 'png', 'gif', 'jpeg'],

    'datetimeFormat' => 'm/d/Y g:i A',
    'defaultPermission' => 0775,
    'quality' => 90,

    'sources' => [
        'default' => []
    ],

    'excludeDirectoryNames' => [],
    'allowReplaceSourceFile' => true,

    'accessControl' => [
        'extensions' => '*',
        'path' => '/',

        'FILES' => true,
        'FILE_MOVE' => true,
        'FILE_UPLOAD' => true,
        'FILE_REMOVE' => true,
        'FILE_RENAME' => true,

        'FOLDERS' => true,
        'FOLDER_MOVE' => true,
        'FOLDER_REMOVE' => true,
        'FOLDER_RENAME' => true,
        'FOLDER_CREATE' => true,

        'IMAGE_RESIZE' => true,
        'IMAGE_CROP' => true
    ],

    'locale' => [
        'folder_created' => 'Папка создана!',
        'folder_renamed' => 'Папка переименована',
        'folder_removed' => 'Папка удалена',

        'file_uploaded' => 'Файл :file загружен',
        'file_renamed' => 'Файл переименован',
        'file_removed' => 'Файл удален',

        'image_resized' => 'Изображение изменено!',
        'image_cropped' => 'Изображение обрезано!',
    ]
];
