<?php

namespace Tests\Feature\Support;

use App\Support\OgpImage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OgpImageTest extends TestCase
{
    /** @return array<string, array{string, string}> */
    public static function fontSizeProvider(): array
    {
        return [
            '空タイトル' => ['', '50'],
            '10文字' => [str_repeat('あ', 10), '50'],
            '11文字' => [str_repeat('あ', 11), '30'],
            '15文字' => [str_repeat('あ', 15), '30'],
            '16文字' => [str_repeat('あ', 16), '25'],
            '20文字' => [str_repeat('あ', 20), '25'],
        ];
    }

    #[Test]
    #[DataProvider('fontSizeProvider')]
    public function タイトル長に応じたフォントサイズを返す(string $title, string $fontSize): void
    {
        $this->assertSame($fontSize, OgpImage::fontSize($title));
    }

    #[Test]
    public function タイトルを埋め込んだ_cloudinary_url_を返す(): void
    {
        $this->assertSame(
            'https://res.cloudinary.com/antihabits/image/upload/l_text:Sawarabi%20Gothic_50_solid:夜更かし,co_rgb:333,w_500,c_fit/v1757602327/anti_habits_dynamic_ogp_zyyjyk.png',
            OgpImage::urlFor('夜更かし'),
        );
    }
}
