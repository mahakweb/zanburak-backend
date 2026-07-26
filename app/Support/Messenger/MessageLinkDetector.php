<?php

namespace App\Support\Messenger;

/**
 * Detect URLs in messenger message bodies (http/https/www/bare domains/invite paths).
 * Kept in sync conceptually with frontend messageLinks.js.
 */
class MessageLinkDetector
{
    /**
     * Common / multi-letter TLDs for bare-domain matching.
     */
    private const TLD = 'com|org|net|edu|gov|mil|int|io|co|me|app|dev|info|biz|ir|ai|tv|xyz|online|site|store|shop|blog|cloud|tech|pro|name|mobi|asia|tel|news|media|agency|studio|design|digital|email|live|world|today|space|life|club|wiki|page|link|click|top|vip|fun|game|games|play|art|photo|pics|video|music|band|rocks|ninja|guru|expert|academy|school|university|center|company|solutions|systems|services|support|help|team|works|work|jobs|careers|finance|money|bank|insurance|health|care|hospital|doctor|law|legal|attorney|press|report|tools|software|host|hosting|server|network|security|crypto|nft|web|website|home|house|estate|property|travel|tours|hotel|flights|food|restaurant|cafe|bar|beer|wine|fashion|style|beauty|fit|fitness|sport|sports|bet|casino|poker|dating|chat|social|community|group|forum|market|marketing|ads|ad|seo|sale|deals|discount|coupon|gift|free|download|uk|us|ca|au|de|fr|es|it|nl|be|ch|at|se|no|dk|fi|pl|cz|ru|ua|tr|sa|ae|qa|kw|bh|om|eg|ma|za|ng|ke|in|pk|bd|lk|np|th|vn|id|my|sg|ph|jp|kr|cn|hk|tw|br|mx|ar|cl|pe|nz|ie|pt|gr|ro|hu|bg|hr|rs|ba|si|sk|lt|lv|ee|is|lu|mt|cy|ge|am|az|kz|uz|by|md';

    private const FILE_EXT = 'jpg|jpeg|png|gif|webp|svg|bmp|ico|mp3|mp4|mov|avi|mkv|webm|pdf|doc|docx|xls|xlsx|ppt|pptx|txt|csv|zip|rar|7z|tar|gz|js|ts|css|scss|json|xml|html|htm|md|yml|yaml|py|php|rb|go|rs|java|c|cpp|h|hpp|exe|dmg|apk|ipa';

    public static function containsLink(?string $body): bool
    {
        return self::extractLinks($body) !== [];
    }

    /**
     * @return list<string>
     */
    public static function extractLinks(?string $body): array
    {
        $text = (string) $body;
        if ($text === '') {
            return [];
        }

        $pattern = self::pattern();
        if (! preg_match_all($pattern, $text, $matches, PREG_SET_ORDER)) {
            return [];
        }

        $found = [];
        foreach ($matches as $m) {
            $raw = self::trimTrailingPunct($m[0] ?? '');
            if ($raw === '' || ! self::isPlausibleBareDomain($raw)) {
                continue;
            }
            $found[] = self::normalizeHref($raw);
        }

        return $found;
    }

    public static function normalizeHref(string $token): string
    {
        $t = self::trimTrailingPunct(trim($token));
        if ($t === '') {
            return '';
        }
        if (str_starts_with($t, '/')) {
            return $t;
        }
        if (preg_match('#^https?://#i', $t)) {
            return $t;
        }

        return 'https://'.$t;
    }

    protected static function pattern(): string
    {
        $tld = self::TLD;

        // Mimic JS: http(s), www., bare domain with TLD, app-relative invite paths.
        return '#https?://[^\s<>"\']+|www\.[^\s<>"\']+|(?<![@\w./-])(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+(?:'.$tld.')(?![a-z0-9-])(?::\d{1,5})?(?:/[^\s<>"\']*)?|/messenger/(?:join/[^\s<>"\']+|@[^\s<>"\']+)#iu';
    }

    protected static function trimTrailingPunct(string $raw): string
    {
        return preg_replace('/[)\].,;:!?،؛»"\'…]+$/u', '', $raw) ?? $raw;
    }

    protected static function isPlausibleBareDomain(string $token): bool
    {
        if (preg_match('#^https?://#i', $token) || preg_match('#^www\.#i', $token) || str_starts_with($token, '/')) {
            return true;
        }

        $host = preg_split('/[\/?#]/', $token, 2)[0] ?? '';
        $host = explode(':', $host, 2)[0];
        $labels = explode('.', $host);
        if (count($labels) < 2) {
            return false;
        }

        $allNumeric = true;
        foreach ($labels as $label) {
            if (! ctype_digit($label)) {
                $allNumeric = false;
                break;
            }
        }
        if ($allNumeric) {
            return false;
        }

        $last = $labels[count($labels) - 1];
        if (preg_match('/^(?:'.self::FILE_EXT.')$/i', $last)) {
            return false;
        }

        return true;
    }
}
