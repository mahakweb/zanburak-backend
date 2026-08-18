<?php

namespace App\Services\Mail;

class MailMimeDecoder
{
    public function header(mixed $value): string
    {
        if (! is_string($value) || trim($value) === '') {
            return '';
        }

        $original = $value;
        $value = str_replace(["\r\n", "\r"], "\n", $value);
        $value = preg_replace('/\?=\s+=\?/', '?==?', $value) ?? $value;

        if (function_exists('iconv_mime_decode')) {
            $decoded = @iconv_mime_decode($value, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8');
            if (is_string($decoded) && trim($decoded) !== '') {
                $value = $decoded;
            }
        } elseif (function_exists('mb_decode_mimeheader')) {
            $decoded = mb_decode_mimeheader($value);
            if (is_string($decoded) && trim($decoded) !== '') {
                $value = $decoded;
            }
        }

        $value = $this->quotedPrintable($value);

        return trim($value) !== '' ? $value : trim($original);
    }

    public function body(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $decoded = $this->quotedPrintable($value);

        return $decoded === '' ? null : $decoded;
    }

    public function preview(?string $textBody, ?string $htmlBody): string
    {
        $text = trim((string) $this->body($textBody));
        $looksLikeHtml = $text !== '' && (bool) preg_match('/<(?:!DOCTYPE|html|head|body|div|table|p|br|style)\b/i', $text);

        if ($text === '' || $looksLikeHtml) {
            $html = $looksLikeHtml ? $text : (string) $this->body($htmlBody);
            $html = preg_replace('/<style\b[^>]*>.*?<\/style>/is', ' ', $html) ?? $html;
            $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', ' ', $html) ?? $html;
            $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        $text = trim($text);

        if (mb_strlen($text) <= 120) {
            return $text;
        }

        return mb_substr($text, 0, 120).'…';
    }

    protected function quotedPrintable(string $value): string
    {
        $value = trim($value);

        if ($value === '' || ! preg_match('/=[0-9A-Fa-f]{2}/', $value)) {
            return $value;
        }

        $decoded = quoted_printable_decode($value);

        if (is_string($decoded) && $decoded !== '' && ! mb_check_encoding($decoded, 'UTF-8')) {
            foreach (['UTF-8', 'ISO-8859-1', 'Windows-1256'] as $from) {
                try {
                    $converted = mb_convert_encoding($decoded, 'UTF-8', $from);
                    if (is_string($converted) && $converted !== '' && mb_check_encoding($converted, 'UTF-8')) {
                        $decoded = $converted;
                        break;
                    }
                } catch (\Throwable) {
                    continue;
                }
            }
        }

        return is_string($decoded) ? trim($decoded) : $value;
    }
}
