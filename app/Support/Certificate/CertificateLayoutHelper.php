<?php

namespace App\Support\Certificate;

final class CertificateLayoutHelper
{
    /**
     * Build inline CSS for absolutely-positioned certificate elements.
     * x/y are percentage coordinates within the certificate canvas.
     */
    public static function positionStyle(array $pos, ?string $extra = null): string
    {
        $x = (float) ($pos['x'] ?? 50);
        $y = (float) ($pos['y'] ?? 50);
        $align = $pos['align'] ?? 'center';

        $styles = ['position:absolute', 'z-index:3'];

        if ($align === 'left') {
            $styles[] = "left:{$x}%";
            $styles[] = "top:{$y}%";
            $styles[] = 'transform:translateY(-50%)';
        } elseif ($align === 'right') {
            $styles[] = "right:".(100 - $x).'%';
            $styles[] = "top:{$y}%";
            $styles[] = 'transform:translateY(-50%)';
        } else {
            $styles[] = "left:{$x}%";
            $styles[] = "top:{$y}%";
            $styles[] = 'transform:translate(-50%,-50%)';
        }

        if (isset($pos['font_size'])) {
            $styles[] = 'font-size:'.((int) $pos['font_size']).'px';
        }
        if (! empty($pos['color'])) {
            $styles[] = 'color:'.$pos['color'];
        }
        if (! empty($pos['font_weight'])) {
            $styles[] = 'font-weight:'.$pos['font_weight'];
        }
        if ($align === 'center') {
            $styles[] = 'text-align:center';
        } elseif ($align === 'left') {
            $styles[] = 'text-align:left';
        } else {
            $styles[] = 'text-align:right';
        }

        if ($extra) {
            $styles[] = $extra;
        }

        return implode(';', $styles);
    }

    public static function imageStyle(array $pos): string
    {
        $x = (float) ($pos['x'] ?? 50);
        $y = (float) ($pos['y'] ?? 50);
        $width = (int) ($pos['width'] ?? 100);
        $height = (int) ($pos['height'] ?? 60);

        return implode(';', [
            'position:absolute',
            'z-index:3',
            "left:{$x}%",
            "top:{$y}%",
            'transform:translate(-50%,-50%)',
            "width:{$width}px",
            "height:{$height}px",
            'object-fit:contain',
        ]);
    }

    public static function qrStyle(array $pos): string
    {
        $x = (float) ($pos['x'] ?? 88);
        $y = (float) ($pos['y'] ?? 12);
        $size = (int) ($pos['size'] ?? 120);

        return implode(';', [
            'position:absolute',
            'z-index:3',
            "left:{$x}%",
            "top:{$y}%",
            'transform:translate(-50%,-50%)',
            "width:{$size}px",
            "height:{$size}px",
        ]);
    }

    /**
     * Vue-compatible style object for dynamic layout rendering.
     */
    public static function vuePositionStyle(array $pos): array
    {
        $x = (float) ($pos['x'] ?? 50);
        $y = (float) ($pos['y'] ?? 50);
        $align = $pos['align'] ?? 'center';

        $style = [
            'position' => 'absolute',
            'zIndex' => 3,
            'top' => "{$y}%",
        ];

        if ($align === 'left') {
            $style['left'] = "{$x}%";
            $style['transform'] = 'translateY(-50%)';
            $style['textAlign'] = 'left';
        } elseif ($align === 'right') {
            $style['right'] = (100 - $x).'%';
            $style['transform'] = 'translateY(-50%)';
            $style['textAlign'] = 'right';
        } else {
            $style['left'] = "{$x}%";
            $style['transform'] = 'translate(-50%, -50%)';
            $style['textAlign'] = 'center';
        }

        if (isset($pos['font_size'])) {
            $style['fontSize'] = ((int) $pos['font_size']).'px';
        }
        if (! empty($pos['color'])) {
            $style['color'] = $pos['color'];
        }
        if (! empty($pos['font_weight'])) {
            $style['fontWeight'] = $pos['font_weight'];
        }

        return $style;
    }

    public static function vueImageStyle(array $pos): array
    {
        return [
            'position' => 'absolute',
            'zIndex' => 3,
            'left' => ($pos['x'] ?? 50).'%',
            'top' => ($pos['y'] ?? 50).'%',
            'transform' => 'translate(-50%, -50%)',
            'width' => ((int) ($pos['width'] ?? 100)).'px',
            'height' => ((int) ($pos['height'] ?? 60)).'px',
            'objectFit' => 'contain',
        ];
    }

    public static function vueQrStyle(array $pos): array
    {
        $size = (int) ($pos['size'] ?? 120);

        return [
            'position' => 'absolute',
            'zIndex' => 3,
            'left' => ($pos['x'] ?? 88).'%',
            'top' => ($pos['y'] ?? 12).'%',
            'transform' => 'translate(-50%, -50%)',
            'width' => "{$size}px",
            'height' => "{$size}px",
        ];
    }
}
