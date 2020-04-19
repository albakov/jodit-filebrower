<?php

namespace Albakov\JoditFilebrowser\Controllers;

class Helper
{
    const DS = DIRECTORY_SEPARATOR;

    /**
     * @param string $path
     * @param string $fileName
     * @return string
     */
    public static function getUniqueFileName(string $path, string $fileName)
    {
        $filesInPath = scandir($path);

        if (count($filesInPath) > 0 && in_array($fileName, $filesInPath)) {
            return self::getUniqueFileName($path, rand(1, 999999999) . '_' . $fileName);
        }

        return $path . $fileName;
    }

    /**
     * @param string $str
     * @return string
     */
    public static function translit(string $str)
    {
        $replace = [
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'yo', 'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'y',
            'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f',
            'х' => 'h', 'ц' => 'ts', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'shch', 'ъ' => '', 'ы' => 'i', 'ь' => '', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
            ' ' => '-',
            'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D', 'Е' => 'E', 'Ё' => 'Yo', 'Ж' => 'Zh', 'З' => 'Z', 'И' => 'I', 'Й' => 'Y',
            'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N', 'О' => 'O', 'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T', 'У' => 'U', 'Ф' => 'F',
            'Х' => 'H', 'Ц' => 'Ts', 'Ч' => 'CH', 'Ш' => 'Sh', 'Щ' => 'Shch', 'Ъ' => '', 'Ы' => 'I', 'Ь' => '', 'Э' => 'E', 'Ю' => 'Yu', 'Я' => 'Ya',
        ];

        return strtr($str, $replace);
    }

    /**
     * @param string $file
     * @return string
     */
    public static function getSafeFileName(string $file)
    {
        $file = rtrim(self::translit($file), '.');
        $regex = ['#(\.){2,}#', '#[^A-Za-z0-9\.\_\- ]#', '#^\.#'];

        return trim(preg_replace($regex, '', $file));
    }

    /**
     * @param string $string
     * @return string
     */
    public static function convertToCamelCase(string $string)
    {
        $string = preg_replace_callback('#([_])(\w)#', function ($m) {
            return strtoupper($m[2]);
        }, strtolower($string));

        return ucfirst($string);
    }

    /**
     * @param string $actionName
     * @return string
     */
    public static function getPermissionRuleFromAction(string $actionName) {
        $string = preg_replace('#([a-z])([A-Z])#', '\1_\2', $actionName);

        return strtoupper($string);
    }

    /**
     * @param int $bytes
     * @param int $decimals
     * @return string
     */
    public static function getHumanFileSize(int $bytes, int $decimals = 2)
    {
        $size = ['B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        $factor = floor((strlen($bytes) - 1) / 3);

        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . $size[(int)$factor];
    }
}
