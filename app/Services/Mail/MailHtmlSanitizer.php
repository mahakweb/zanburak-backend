<?php

namespace App\Services\Mail;

class MailHtmlSanitizer
{
    public function sanitize(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return null;
        }

        $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html) ?? $html;
        $html = preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $html) ?? $html;
        $html = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? $html;
        $html = preg_replace('/\s(href|src)\s*=\s*("\s*javascript:[^"]*"|\'\s*javascript:[^\']*\')/i', '', $html) ?? $html;

        return $html;
    }

    public function wrapOutgoing(?string $html): ?string
    {
        $html = $this->sanitize($html);
        if (! $html) {
            return null;
        }

        if (preg_match('/<(?:html|body)\b/i', $html)) {
            return $html;
        }

        if (! preg_match('/\bdir\s*=/i', $html) && ! preg_match('/text-align\s*:/i', $html)) {
            return '<div dir="rtl" style="text-align:right">'.$html.'</div>';
        }

        return $html;
    }

    public function toPlainText(?string $html): string
    {
        if (! $html) {
            return '';
        }

        $text = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>', '</div>'], "\n", $html));
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace("/\n{3,}/", "\n\n", $text) ?? $text);
    }
}
