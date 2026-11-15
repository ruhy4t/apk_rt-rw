<?php

namespace Tests\Unit;

use App\Services\VideoEmbed;
use PHPUnit\Framework\TestCase;

class VideoEmbedTest extends TestCase
{
    public function test_youtube_links_only_produce_canonical_privacy_player_urls(): void
    {
        foreach ([
            'https://www.youtube.com/watch?v=Abcdef_1234&autoplay=1&origin=https://untrusted.example',
            'https://youtu.be/Abcdef_1234?si=tracking',
            'https://m.youtube.com/watch?v=Abcdef_1234',
            'https://www.youtube.com/embed/Abcdef_1234',
            'https://www.youtube-nocookie.com/embed/Abcdef_1234?autoplay=1',
            'https://youtube.com/live/Abcdef_1234',
        ] as $url) {
            $this->assertSame([
                'provider' => 'YouTube',
                'src' => 'https://www.youtube-nocookie.com/embed/Abcdef_1234?autoplay=0&rel=0',
                'aspect' => 'landscape',
            ], VideoEmbed::fromUrl($url));
        }
        $this->assertSame('portrait', VideoEmbed::fromUrl('https://www.youtube.com/shorts/Abcdef_1234')['aspect']);
    }

    public function test_supported_social_video_links_generate_only_allowlisted_player_urls(): void
    {
        $this->assertSame([
            'provider' => 'TikTok',
            'src' => 'https://www.tiktok.com/player/v1/1234567890123456789?autoplay=0',
            'aspect' => 'portrait',
        ], VideoEmbed::fromUrl('https://www.tiktok.com/@synthetic/video/1234567890123456789?autoplay=1'));
        foreach (['p', 'reel', 'tv'] as $type) {
            $this->assertSame([
                'provider' => 'Instagram',
                'src' => 'https://www.instagram.com/'.$type.'/SYNTHETIC_1/embed/',
                'aspect' => 'portrait',
            ], VideoEmbed::fromUrl('https://www.instagram.com/'.$type.'/SYNTHETIC_1/?igsh=tracking'));
        }
        $this->assertSame([
            'provider' => 'Vimeo',
            'src' => 'https://player.vimeo.com/video/123456789?autoplay=0&dnt=1',
            'aspect' => 'landscape',
        ], VideoEmbed::fromUrl('https://vimeo.com/123456789?autoplay=1'));
        $this->assertSame('Vimeo', VideoEmbed::fromUrl('https://player.vimeo.com/video/123456789')['provider']);
    }

    public function test_unsupported_or_unsafe_links_are_never_used_as_iframe_sources(): void
    {
        foreach ([
            null, '', 'javascript:alert(1)', 'http://youtube.com/watch?v=Abcdef_1234',
            'https://youtube.com.evil.example/watch?v=Abcdef_1234',
            'https://youtube.com@evil.example/watch?v=Abcdef_1234',
            'https://name:password@youtube.com/watch?v=Abcdef_1234',
            'https://youtube.com:8443/watch?v=Abcdef_1234',
            'https://www.youtube.com/watch?v[]=Abcdef_1234',
            'https://www.youtube.com/watch?v=%3Cscript%3E',
            'https://www.youtube.com/embed/Abcdef_1234/extra',
            'https://www.youtube.com/embed/%41bcdef_1234',
            'https://www.youtube.com/embed/Abcdef_1234" onload="alert(1)',
            "https://www.youtube.com/\nembed/Abcdef_1234",
            'https://www.youtube.com\\@evil.example/embed/Abcdef_1234',
            'https://vm.tiktok.com/shortlink/',
            'https://www.instagram.com/stories/synthetic/123/',
            'https://www.instagram.com/reel/SYNTHETIC_1/extra/',
            'https://www.tiktok.com/@synthetic/video/not-a-number',
            'https://www.tiktok.com.evil.example/@synthetic/video/1234567890123456789',
            'https://127.0.0.1/video', 'https://video.example/embed/123',
            'https://vimeo.com/123456789/privatehash',
            str_repeat('a', 2049),
        ] as $url) {
            $this->assertNull(VideoEmbed::fromUrl($url));
        }
    }
}
