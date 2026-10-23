<?php

namespace App\Support;

/**
 * タイトルを埋め込んだ動的 OGP 画像 (Cloudinary) の URL を組み立てる。
 */
class OgpImage
{
    public static function urlFor(string $title): string
    {
        $fontSize = self::fontSize($title);

        return "https://res.cloudinary.com/antihabits/image/upload/l_text:Sawarabi%20Gothic_{$fontSize}_solid:{$title},co_rgb:333,w_500,c_fit/v1757602327/anti_habits_dynamic_ogp_zyyjyk.png";
    }

    public static function fontSize(string $title): string
    {
        return match (true) {
            mb_strlen($title) > 15 => '25',
            mb_strlen($title) > 10 => '30',
            default => '50',
        };
    }
}
