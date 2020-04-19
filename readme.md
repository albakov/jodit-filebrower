# Jodit v3.0 FileBrowser PHP Connector

### Установка

```composer require albakov/jodit-filebrowser```

### Как пользоваться

На примере Laravel:

```
. . .

use Albakov\JoditFilebrowser\Handler;

class Editor
{
    /**
     * Requests handler
     * @throws \Exception
     */
    public function browser()
    {
        $config = [
            'root' => public_path('files'),
            'baseurl' => url('files'),
            'sources' => [
                'files' => [
                    'root' => public_path('files'),
                    'baseurl' => url('files'),
                    'extensions' => ['jpg', 'jpeg', 'png', 'gif']
                ]
            ]
        ];

        return (new Handler($config))->handle();
    }
}

. . .

```

Доступные параметры:

```
$config = [
    'root' => '/www/...',
    'baseurl' => 'https://...',
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
        'image_cropped' => 'Изображение обрезано!'
    ]
];
```

Обязательные поля:

```
'root' => '/www/...',
'baseurl' => 'https://...',
'sources' => [
    'files' => [
        'root' => '/www/...',
        'baseurl' => 'https://...'
    ]
]
```

Если будет использоваться функционал изменения размера изображения (crop, resize), необходимо указать обработчик. 
Для этого можно использовать любую библиотеку для обработки изображений, 
например [SimpleImage](https://github.com/claviska/SimpleImageSimpleImage).

Сначала создается класс-обработчик, который реализовывает интерфейс Albakov\JoditFilebrowser\Interfaces\ImageContract.
<br>Пример этого файла тут: https://github.com/albakov/jodit-filebrower/blob/master/src/Example/Image.php

Далее указываем обработчик:

```
. . .

return (new Handler($config))
    ->setImageHandler(new Image)
    ->handle();

. . .

```

За основу взята библиотека: https://github.com/xdan/jodit-connectors
