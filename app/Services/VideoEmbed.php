<?php

namespace App\Services;

class VideoEmbed
{
    /**
     * Build iframe URLs from validated provider IDs, never from submitted HTML.
     * No metadata, redirects, or remote documents are fetched by the server.
     *
     * @return array{provider: string, src: string, aspect: string}|null
     */
    public static function fromUrl(?string $url): ?array
    {
        if (! $url || strlen($url) > 2048 || preg_match('/[\x00-\x20\x7f\\\\]/', $url)) {
            return null;
        }
        $parts = parse_url($url);
        if (! is_array($parts) || strtolower($parts['scheme'] ?? '') !== 'https'
            || isset($parts['user']) || isset($parts['pass'])
            || (isset($parts['port']) && $parts['port'] !== 443)) {
            return null;
        }
        $host = strtolower($parts['host'] ?? '');
        $path = $parts['path'] ?? '';
        $id = null;
        $portrait = false;

        if (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtube-nocookie.com', 'www.youtube-nocookie.com', 'youtu.be', 'www.youtu.be'], true)) {
            if (in_array($host, ['youtu.be', 'www.youtu.be'], true)) {
                $id = preg_match('~^/([A-Za-z0-9_-]{11})/?$~D', $path, $match) ? $match[1] : null;
            } elseif (preg_match('~^/(embed|shorts|live)/([A-Za-z0-9_-]{11})/?$~D', $path, $match)) {
                $id = $match[2];
                $portrait = $match[1] === 'shorts';
            } elseif ($path === '/watch' && ! str_contains($host, 'nocookie')) {
                parse_str($parts['query'] ?? '', $query);
                $id = isset($query['v']) && is_string($query['v']) && preg_match('/^[A-Za-z0-9_-]{11}$/D', $query['v']) ? $query['v'] : null;
            }

            return $id ? self::descriptor('YouTube', 'https://www.youtube-nocookie.com/embed/'.$id.'?autoplay=0&rel=0', $portrait) : null;
        }

        if (in_array($host, ['tiktok.com', 'www.tiktok.com'], true)
            && preg_match('~^/(?:@[A-Za-z0-9._]{1,64}/video|player/v1)/([0-9]{10,25})/?$~D', $path, $match)) {
            return self::descriptor('TikTok', 'https://www.tiktok.com/player/v1/'.$match[1].'?autoplay=0', true);
        }

        if (in_array($host, ['instagram.com', 'www.instagram.com'], true)
            && preg_match('~^/(p|reel|tv)/([A-Za-z0-9_-]{5,64})(?:/embed)?/?$~D', $path, $match)) {
            return self::descriptor('Instagram', 'https://www.instagram.com/'.$match[1].'/'.$match[2].'/embed/', true);
        }

        if (in_array($host, ['vimeo.com', 'www.vimeo.com', 'player.vimeo.com'], true)) {
            $pattern = $host === 'player.vimeo.com' ? '~^/video/([1-9][0-9]{0,11})/?$~D' : '~^/([1-9][0-9]{0,11})/?$~D';
            if (preg_match($pattern, $path, $match)) {
                return self::descriptor('Vimeo', 'https://player.vimeo.com/video/'.$match[1].'?autoplay=0&dnt=1');
            }
        }

        return null;
    }

    private static function descriptor(string $provider, string $src, bool $portrait = false): array
    {
        return ['provider' => $provider, 'src' => $src, 'aspect' => $portrait ? 'portrait' : 'landscape'];
    }
}
