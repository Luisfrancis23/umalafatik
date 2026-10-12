<?php
/**
 * Generates the placeholder food-art SVG imagery for Uma Lafatik.
 * Run once: php tools/generate_images.php
 * Replace with real photography at any time via Admin → Menu Items (upload).
 */
declare(strict_types=1);

$dishesDir = __DIR__ . '/../assets/images/dishes';
$galleryDir = __DIR__ . '/../assets/images/gallery';
@mkdir($dishesDir, 0755, true);
@mkdir($galleryDir, 0755, true);

/* ------------------------------------------------------------------ helpers */

function svg_doc(string $inner, string $bg1, string $bg2, int $w = 800, int $h = 600): string
{
    $gid = bin2hex(random_bytes(4));
    return sprintf(
        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %d %d" role="img" aria-label="Food illustration">
<defs>
  <linearGradient id="bg%s" x1="0" y1="0" x2="1" y2="1">
    <stop offset="0" stop-color="%s"/><stop offset="1" stop-color="%s"/>
  </linearGradient>
  <radialGradient id="glow%s" cx="0.5" cy="0.42" r="0.75">
    <stop offset="0" stop-color="#ffffff" stop-opacity="0.14"/>
    <stop offset="0.6" stop-color="#ffffff" stop-opacity="0.04"/>
    <stop offset="1" stop-color="#000000" stop-opacity="0.10"/>
  </radialGradient>
</defs>
<rect width="%d" height="%d" fill="url(#bg%s)"/>
%s
<rect width="%d" height="%d" fill="url(#glow%s)"/>
</svg>',
        $w, $h,
        $gid, $bg1, $bg2,
        $gid,
        $w, $h, $gid,
        $inner,
        $w, $h, $gid
    );
}

/** Decorative background rings + dots. */
function bg_decor(string $tint = '#d4a24e'): string
{
    $out = '';
    $out .= '<circle cx="120" cy="90" r="150" fill="none" stroke="' . $tint . '" stroke-opacity="0.10" stroke-width="1.5"/>';
    $out .= '<circle cx="700" cy="520" r="190" fill="none" stroke="' . $tint . '" stroke-opacity="0.08" stroke-width="1.5"/>';
    $out .= '<circle cx="680" cy="110" r="70" fill="' . $tint . '" fill-opacity="0.06"/>';
    $out .= '<circle cx="90" cy="510" r="55" fill="' . $tint . '" fill-opacity="0.05"/>';
    for ($i = 0; $i < 26; $i++) {
        $x = 40 + ($i * 137) % 720;
        $y = 30 + ($i * 89) % 540;
        $out .= sprintf('<circle cx="%d" cy="%d" r="1.6" fill="#ffffff" fill-opacity="0.10"/>', $x, $y);
    }
    return $out;
}

/** Warm spotlight under the plate. */
function plate_shadow(): string
{
    return '<ellipse cx="400" cy="470" rx="230" ry="34" fill="#000000" fill-opacity="0.22"/>';
}

/** A dinner plate. */
function plate(float $cx, float $cy, float $rx, float $ry, string $rim = '#f7f2ea', string $inner = '#ece2cf'): string
{
    return sprintf(
        '<ellipse cx="%s" cy="%s" rx="%s" ry="%s" fill="%s"/>
<ellipse cx="%s" cy="%s" rx="%s" ry="%s" fill="%s"/>
<ellipse cx="%s" cy="%s" rx="%s" ry="%s" fill="none" stroke="#d4a24e" stroke-opacity="0.55" stroke-width="2"/>',
        $cx, $cy, $rx, $ry, $rim,
        $cx, $cy, $rx * 0.78, $ry * 0.76, $inner,
        $cx, $cy, $rx * 0.6, $ry * 0.58
    );
}

/** Steam wisps above a dish. */
function steam(float $cx, float $topY, string $color = '#ffffff'): string
{
    $paths = [
        "M {$cx} {$topY} q -14 -26 2 -44 q 12 -14 2 -28",
        "M " . ($cx + 30) . " " . ($topY + 8) . " q 14 -24 -2 -42 q -10 -16 -2 -30",
        "M " . ($cx - 32) . " " . ($topY + 12) . " q -12 -22 2 -40 q 10 -16 2 -28",
    ];
    $out = '';
    foreach ($paths as $p) {
        $out .= '<path d="' . $p . '" fill="none" stroke="' . $color . '" stroke-opacity="0.35" stroke-width="5" stroke-linecap="round"/>';
    }
    return $out;
}

/** Dome of food (e.g. rice). */
function dome(float $cx, float $cy, float $w, float $h, string $base, string $top): string
{
    $gid = bin2hex(random_bytes(3));
    return sprintf(
        '<defs><linearGradient id="dm%s" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="%s"/><stop offset="1" stop-color="%s"/>
        </linearGradient></defs>
        <path d="M %s %s A %s %s 0 0 1 %s %s Z" fill="url(#dm%s)"/>',
        $gid, $top, $base,
        $cx - $w, $cy, $w, $h, $cx + $w, $cy, $gid
    );
}

/** Small garnish circles. */
function garnish(array $dots): string
{
    $out = '';
    foreach ($dots as $d) {
        $out .= sprintf('<circle cx="%s" cy="%s" r="%s" fill="%s" fill-opacity="%s"/>', $d[0], $d[1], $d[2], $d[3], $d[4] ?? '1');
    }
    return $out;
}

/* ------------------------------------------------------------------ motifs */

function motif_plate_dome(array $cfg): string
{
    $s  = bg_decor($cfg['accent'] ?? '#d4a24e');
    $s .= plate_shadow();
    $s .= plate(400, 360, 210, 150, $cfg['plate'] ?? '#f7f2ea', $cfg['plate_inner'] ?? '#ece2cf');
    $s .= dome(400, 350, 150, 130, $cfg['food_base'], $cfg['food_top']);
    // texture strokes on the dome
    foreach ($cfg['texture'] ?? [] as $t) {
        $s .= sprintf('<path d="%s" fill="none" stroke="%s" stroke-opacity="%s" stroke-width="%s" stroke-linecap="round"/>', $t[0], $t[1], $t[2] ?? '0.7', $t[3] ?? '4');
    }
    $s .= garnish($cfg['garnish'] ?? []);
    $s .= steam(400, 200, $cfg['steam'] ?? '#ffffff');
    return $s;
}

function motif_bowl(array $cfg): string
{
    $gid = bin2hex(random_bytes(3));
    $s  = bg_decor($cfg['accent'] ?? '#d4a24e');
    $s .= plate_shadow();
    // bowl
    $s .= sprintf(
        '<defs><linearGradient id="bw%s" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="%s"/><stop offset="1" stop-color="%s"/>
        </linearGradient></defs>
        <path d="M 230 330 A 170 140 0 0 0 570 330 Z" fill="url(#bw%s)"/>
        <ellipse cx="400" cy="330" rx="170" ry="42" fill="%s"/>',
        $gid, $cfg['bowl_base'], $cfg['bowl_top'], $gid, $cfg['bowl_rim'] ?? '#f7f2ea'
    );
    // contents
    foreach ($cfg['contents'] ?? [] as $c) {
        $s .= sprintf('<ellipse cx="%s" cy="%s" rx="%s" ry="%s" fill="%s"/>', $c[0], $c[1], $c[2], $c[3], $c[4]);
    }
    $s .= garnish($cfg['garnish'] ?? []);
    $s .= steam(400, 240, $cfg['steam'] ?? '#ffffff');
    return $s;
}

function motif_skewers(array $cfg): string
{
    $s  = bg_decor($cfg['accent'] ?? '#d4a24e');
    $s .= plate_shadow();
    $s .= plate(400, 380, 190, 110, $cfg['plate'] ?? '#f7f2ea', $cfg['plate_inner'] ?? '#ece2cf');
    $skewers = $cfg['skewers'] ?? [];
    $i = 0;
    foreach ($skewers as $sk) {
        $i++;
        $x = 260 + ($i * 90);
        $y = 250 + ($i * 20);
        // stick
        $s .= sprintf('<line x1="%d" y1="%d" x2="%d" y2="%d" stroke="#8a7a66" stroke-width="6" stroke-linecap="round"/>', $x - 90, $y - 120, $x + 110, $y + 90);
        // pieces
        foreach ($sk as $piece) {
            $s .= sprintf(
                '<rect x="%d" y="%d" width="%d" height="%d" rx="14" fill="%s" transform="rotate(%d %d %d)"/>',
                $piece[0], $piece[1], $piece[2], $piece[3], $piece[4], $piece[5], $piece[0] + $piece[2] / 2, $piece[1] + $piece[3] / 2
            );
        }
    }
    // flame accents
    foreach ($cfg['flames'] ?? [] as $f) {
        $s .= sprintf('<path d="M %s %s q 10 -22 0 -38 q -10 16 0 38 Z" fill="%s" fill-opacity="0.8"/>', $f[0], $f[1], $f[2] ?? '#e8873a');
    }
    $s .= garnish($cfg['garnish'] ?? []);
    return $s;
}

function motif_glass(array $cfg): string
{
    $gid = bin2hex(random_bytes(3));
    $s  = bg_decor($cfg['accent'] ?? '#d4a24e');
    $s .= '<ellipse cx="400" cy="500" rx="130" ry="24" fill="#000000" fill-opacity="0.22"/>';
    // glass
    $s .= sprintf(
        '<defs>
          <linearGradient id="lf%s" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="%s"/><stop offset="1" stop-color="%s"/>
          </linearGradient>
          <linearGradient id="gl%s" x1="0" y1="0" x2="1" y2="0">
            <stop offset="0" stop-color="#ffffff" stop-opacity="0.35"/>
            <stop offset="0.25" stop-color="#ffffff" stop-opacity="0.06"/>
            <stop offset="0.8" stop-color="#ffffff" stop-opacity="0.05"/>
            <stop offset="1" stop-color="#ffffff" stop-opacity="0.22"/>
          </linearGradient>
        </defs>
        <path d="M 330 190 L 344 470 Q 400 492 456 470 L 470 190 Z" fill="url(#lf%s)"/>
        <path d="M 330 190 L 344 470 Q 400 492 456 470 L 470 190 Z" fill="url(#gl%s)"/>
        <path d="M 330 190 L 470 190 L 464 230 L 336 230 Z" fill="#ffffff" fill-opacity="0.18"/>',
        $gid, $cfg['liquid_top'], $cfg['liquid_base'], $gid, $gid, $gid
    );
    // ice cubes
    foreach ($cfg['ice'] ?? [] as $ice) {
        $s .= sprintf('<rect x="%s" y="%s" width="34" height="34" rx="8" fill="#ffffff" fill-opacity="0.35" transform="rotate(%s %s %s)"/>', $ice[0], $ice[1], $ice[2], $ice[0] + 17, $ice[1] + 17);
    }
    // garnish: citrus wheel
    if (!empty($cfg['citrus'])) {
        $s .= sprintf(
            '<circle cx="%s" cy="%s" r="34" fill="%s"/><circle cx="%s" cy="%s" r="24" fill="%s" fill-opacity="0.9"/>',
            $cfg['citrus'][0], $cfg['citrus'][1], $cfg['citrus'][2], $cfg['citrus'][0], $cfg['citrus'][1], $cfg['citrus'][3]
        );
        for ($a = 0; $a < 360; $a += 30) {
            $rad = deg2rad($a);
            $x1 = $cfg['citrus'][0] + cos($rad) * 24;
            $y1 = $cfg['citrus'][1] + sin($rad) * 24;
            $x2 = $cfg['citrus'][0] + cos($rad) * 34;
            $y2 = $cfg['citrus'][1] + sin($rad) * 34;
            $s .= sprintf('<line x1="%.1f" y1="%.1f" x2="%.1f" y2="%.1f" stroke="%s" stroke-width="2"/>', $x1, $y1, $x2, $y2, $cfg['citrus'][2]);
        }
    }
    // mint leaves
    foreach ($cfg['mint'] ?? [] as $m) {
        $s .= sprintf('<path d="M %s %s q 18 -22 34 -6 q -16 20 -34 6 Z" fill="%s"/>', $m[0], $m[1], $m[2] ?? '#7a8b5a');
    }
    // straw
    if (!empty($cfg['straw'])) {
        $s .= sprintf('<line x1="%s" y1="%s" x2="%s" y2="%s" stroke="%s" stroke-width="10" stroke-linecap="round"/>', $cfg['straw'][0], $cfg['straw'][1], $cfg['straw'][2], $cfg['straw'][3], $cfg['straw'][4] ?? '#c96f4a');
    }
    $s .= steam(400, 150, $cfg['steam'] ?? '#ffffff');
    return $s;
}

function motif_dessert(array $cfg): string
{
    $s  = bg_decor($cfg['accent'] ?? '#d4a24e');
    $s .= plate_shadow();
    $s .= plate(400, 380, 195, 130, $cfg['plate'] ?? '#f7f2ea', $cfg['plate_inner'] ?? '#ece2cf');
    $s .= $cfg('body');
    $s .= garnish($cfg['garnish'] ?? []);
    $s .= steam(400, 220, $cfg['steam'] ?? '#ffffff');
    return $s;
}

/* ------------------------------------------------------------------ dishes */

$dishes = [
    // Starters
    'mezze' => function () {
        return motif_bowl([
            'accent' => '#c96f4a', 'bowl_base' => '#3a332d', 'bowl_top' => '#241e1a', 'bowl_rim' => '#f7f2ea',
            'contents' => [
                [340, 322, 90, 26, '#d9a05b'], [470, 326, 80, 22, '#8e5a44'],
                [400, 318, 70, 20, '#7a8b5a'], [300, 334, 55, 16, '#efe0c8'], [490, 340, 48, 14, '#efe0c8'],
            ],
            'garnish' => [[370, 300, 6, '#b4443c'], [430, 296, 6, '#b4443c'], [340, 288, 4, '#d4a24e'], [480, 290, 4, '#d4a24e'], [400, 286, 5, '#7a8b5a']],
        ]);
    },
    'falafel' => function () {
        return motif_plate_dome([
            'accent' => '#7a8b5a', 'plate' => '#2f3a2e', 'plate_inner' => '#24301f',
            'food_base' => '#8a5a28', 'food_top' => '#c98d4b',
            'texture' => [
                ['M 330 330 q 30 -18 60 0 q 30 18 60 0', '#8a5a28', '0.8', '5'],
                ['M 320 356 q 40 -20 80 0 q 40 20 80 0', '#8a5a28', '0.7', '5'],
                ['M 345 300 q 20 -14 40 0', '#a96f36', '0.8', '4'],
            ],
            'garnish' => [[330, 280, 5, '#7a8b5a'], [470, 276, 5, '#7a8b5a'], [400, 268, 6, '#efe0c8'], [360, 262, 4, '#d4a24e'], [445, 260, 4, '#d4a24e']],
            'steam' => '#e8d5b7',
        ]);
    },
    'vine-leaves' => function () {
        $s = bg_decor('#7a8b5a') . plate_shadow() . plate(400, 380, 200, 130, '#f7f2ea', '#ece2cf');
        for ($i = 0; $i < 6; $i++) {
            $x = 300 + $i * 40;
            $y = 350 + ($i % 2) * 14;
            $s .= sprintf(
                '<g transform="rotate(%d %d %d)">
                    <rect x="%d" y="%d" width="78" height="26" rx="13" fill="#6b7f52"/>
                    <rect x="%d" y="%d" width="78" height="10" rx="5" fill="#efe0c8" fill-opacity="0.85"/>
                </g>',
                -12 + $i * 5, $x, $y, $x - 39, $y - 13, $x - 39, $y - 5
            );
        }
        $s .= garnish([[310, 310, 5, '#7a8b5a'], [490, 306, 5, '#7a8b5a'], [400, 300, 6, '#d4a24e'], [355, 296, 4, '#b4443c'], [448, 294, 4, '#b4443c']]);
        $s .= steam(400, 268, '#ffffff');
        return $s;
    },
    // Main courses
    'kabsa' => function () {
        return motif_plate_dome([
            'accent' => '#d4a24e', 'plate' => '#f7f2ea', 'plate_inner' => '#ece2cf',
            'food_base' => '#b4863a', 'food_top' => '#e0b25c',
            'texture' => [
                ['M 320 320 q 40 -22 80 0 q 40 22 80 0', '#c99b3f', '0.7', '4'],
                ['M 310 350 q 45 -24 90 0 q 45 24 90 0', '#c99b3f', '0.6', '4'],
                ['M 340 285 q 25 -16 50 0', '#f0d08a', '0.8', '4'],
            ],
            'garnish' => [[330, 262, 8, '#7a8b5a'], [470, 258, 8, '#7a8b5a'], [400, 250, 9, '#b4443c'], [360, 246, 5, '#3a2e1e'], [445, 244, 5, '#3a2e1e'], [300, 290, 6, '#e8e0d0']],
        ]);
    },
    'mandi' => function () {
        return motif_plate_dome([
            'accent' => '#c98d4b', 'plate' => '#f7f2ea', 'plate_inner' => '#ece2cf',
            'food_base' => '#c99b3f', 'food_top' => '#edd08a',
            'texture' => [
                ['M 330 318 q 35 -20 70 0 q 35 20 70 0', '#d9b265', '0.7', '4'],
                ['M 320 348 q 40 -22 80 0 q 40 22 80 0', '#d9b265', '0.6', '4'],
            ],
            'garnish' => [[400, 262, 26, '#c98d5a'], [400, 262, 16, '#a96a3e'], [335, 256, 7, '#7a8b5a'], [465, 254, 7, '#7a8b5a'], [370, 244, 5, '#d4a24e'], [432, 242, 5, '#d4a24e']],
        ]);
    },
    'sea-bass' => function () {
        $s = bg_decor('#6fa8a0') . plate_shadow() . plate(400, 370, 210, 140, '#f7f2ea', '#ece2cf');
        // fish body
        $s .= '<path d="M 300 360 Q 400 285 500 360 Q 400 405 300 360 Z" fill="#cfd8d3"/>';
        $s .= '<path d="M 315 355 Q 400 300 485 355 Q 400 388 315 355 Z" fill="#e8efec"/>';
        // tail + fin
        $s .= '<path d="M 500 360 L 545 335 L 540 360 L 545 385 Z" fill="#b9c6c0"/>';
        $s .= '<path d="M 395 310 Q 410 285 425 310 Z" fill="#b9c6c0"/>';
        // eye & lemon
        $s .= '<circle cx="335" cy="352" r="5" fill="#241e1a"/>';
        $s .= '<circle cx="455" cy="300" r="22" fill="#e8c44f"/><circle cx="455" cy="300" r="14" fill="#f2dc8a"/>';
        $s .= garnish([[360, 330, 5, '#7a8b5a'], [420, 320, 5, '#7a8b5a'], [390, 300, 4, '#d4a24e'], [340, 380, 4, '#b4443c']]);
        $s .= steam(400, 240);
        return $s;
    },
    'risotto' => function () {
        return motif_plate_dome([
            'accent' => '#d4a24e', 'plate' => '#f7f2ea', 'plate_inner' => '#ece2cf',
            'food_base' => '#c9b489', 'food_top' => '#e8dcc0',
            'texture' => [
                ['M 325 322 q 38 -20 75 0 q 37 20 75 0', '#d5c5a2', '0.8', '4'],
                ['M 315 352 q 42 -24 85 0 q 43 24 85 0', '#d5c5a2', '0.7', '4'],
            ],
            'garnish' => [[350, 285, 12, '#a98a68'], [365, 282, 10, '#8a6a48'], [450, 278, 12, '#a98a68'], [465, 282, 9, '#8a6a48'], [400, 252, 5, '#d4a24e'], [375, 248, 4, '#d4a24e'], [425, 246, 4, '#d4a24e'], [330, 262, 4, '#7a8b5a']],
        ]);
    },
    // Grill
    'mixed-grill' => function () {
        return motif_skewers([
            'accent' => '#e8873a',
            'skewers' => [
                [[-40, -30, 60, 30, '#a94a32', 25], [10, -5, 60, 30, '#7a3a2e', 25], [60, 20, 55, 28, '#a94a32', 25]],
                [[-40, -30, 60, 30, '#c98d4b', 25], [10, -5, 60, 30, '#a96f36', 25], [60, 20, 55, 28, '#c98d4b', 25]],
                [[-40, -30, 60, 30, '#8a5a3a', 25], [10, -5, 60, 30, '#6e452a', 25], [60, 20, 55, 28, '#8a5a3a', 25]],
            ],
            'flames' => [[280, 420, '#e8873a'], [400, 432, '#d4a24e'], [520, 420, '#e8873a']],
            'garnish' => [[330, 300, 5, '#7a8b5a'], [470, 296, 5, '#7a8b5a'], [400, 290, 6, '#b4443c']],
        ]);
    },
    'lamb-chops' => function () {
        $s = bg_decor('#c96f4a') . plate_shadow() . plate(400, 380, 200, 130, '#f7f2ea', '#ece2cf');
        for ($i = 0; $i < 3; $i++) {
            $x = 320 + $i * 80;
            $s .= sprintf(
                '<g transform="rotate(-8 %d %d)">
                    <rect x="%d" y="%d" width="52" height="78" rx="18" fill="#7a3a2e"/>
                    <rect x="%d" y="%d" width="52" height="26" rx="12" fill="#8a4434"/>
                    <rect x="%d" y="%d" width="16" height="42" rx="8" fill="#efe0c8"/>
                </g>',
                $x, 340, $x - 26, 300, $x - 26, 300, $x - 8, 258
            );
        }
        $s .= '<ellipse cx="400" cy="420" rx="90" ry="18" fill="#5a2418" fill-opacity="0.85"/>';
        $s .= garnish([[330, 280, 6, '#7a8b5a'], [470, 278, 6, '#7a8b5a'], [400, 270, 5, '#d4a24e'], [360, 266, 4, '#d4a24e'], [445, 264, 4, '#d4a24e']]);
        $s .= steam(400, 230);
        return $s;
    },
    'tawook' => function () {
        return motif_skewers([
            'accent' => '#d9a05b',
            'skewers' => [
                [[-40, -30, 60, 30, '#d9a05b', 25], [10, -5, 60, 30, '#c98d4b', 25], [60, 20, 55, 28, '#d9a05b', 25]],
                [[-40, -30, 60, 30, '#c98d4b', 25], [10, -5, 60, 30, '#b47a3a', 25], [60, 20, 55, 28, '#c98d4b', 25]],
            ],
            'garnish' => [[330, 300, 12, '#efe0c8'], [470, 298, 12, '#efe0c8'], [400, 292, 14, '#efe0c8'], [365, 288, 4, '#7a8b5a'], [440, 286, 4, '#7a8b5a']],
        ]);
    },
    // Pasta & rice
    'linguine' => function () {
        $s = bg_decor('#e3c77e') . plate_shadow() . plate(400, 370, 210, 140, '#f7f2ea', '#ece2cf');
        // pasta nest
        for ($i = 0; $i < 9; $i++) {
            $r = 20 + $i * 12;
            $s .= sprintf('<ellipse cx="400" cy="360" rx="%d" ry="%d" fill="none" stroke="#e3c77e" stroke-opacity="0.9" stroke-width="5"/>', 130 - $i * 6, 85 - $i * 5);
        }
        // prawns
        $s .= '<path d="M 340 320 q 20 -18 36 -4 q -14 16 -36 4 Z" fill="#d97a5a"/>';
        $s .= '<path d="M 430 318 q 20 -18 36 -4 q -14 16 -36 4 Z" fill="#d97a5a"/>';
        $s .= garnish([[360, 290, 5, '#d4a24e'], [440, 288, 5, '#d4a24e'], [400, 282, 5, '#7a8b5a'], [315, 300, 4, '#b4443c'], [485, 300, 4, '#b4443c']]);
        $s .= steam(400, 240);
        return $s;
    },
    'fattoush' => function () {
        return motif_bowl([
            'accent' => '#7a8b5a', 'bowl_base' => '#3a332d', 'bowl_top' => '#241e1a', 'bowl_rim' => '#f7f2ea',
            'contents' => [
                [350, 320, 80, 24, '#7a8b5a'], [450, 322, 76, 22, '#8b9a62'],
                [400, 318, 60, 20, '#6b7f52'], [320, 334, 40, 14, '#d9a05b'], [480, 336, 42, 14, '#d9a05b'],
            ],
            'garnish' => [[370, 296, 6, '#b4443c'], [430, 294, 6, '#b4443c'], [400, 290, 5, '#d4a24e'], [340, 288, 4, '#e8c44f'], [465, 288, 4, '#e8c44f']],
        ]);
    },
    // Desserts
    'kunafa' => function () {
        $s = bg_decor('#d9a04b') . plate_shadow() . plate(400, 380, 195, 130, '#f7f2ea', '#ece2cf');
        // shredded nest
        for ($i = 0; $i < 12; $i++) {
            $s .= sprintf('<ellipse cx="400" cy="350" rx="%d" ry="%d" fill="none" stroke="#d9a04b" stroke-opacity="0.95" stroke-width="6"/>', 120 - $i * 5, 70 - $i * 4);
        }
        $s .= '<ellipse cx="400" cy="345" rx="70" ry="34" fill="#f2e8d8"/>';
        $s .= '<path d="M 330 345 q 70 -30 140 0 q -70 24 -140 0 Z" fill="#e8d5b7"/>';
        $s .= garnish([[350, 320, 4, '#7a8b5a'], [380, 312, 4, '#7a8b5a'], [420, 312, 4, '#7a8b5a'], [450, 320, 4, '#7a8b5a'], [400, 305, 4, '#b4443c']]);
        // syrup drizzle
        $s .= '<path d="M 320 330 q 80 -40 160 0" fill="none" stroke="#c98d2a" stroke-opacity="0.7" stroke-width="3" stroke-dasharray="10 8"/>';
        $s .= steam(400, 250);
        return $s;
    },
    'lava-cake' => function () {
        $s = bg_decor('#8a4a2a') . plate_shadow() . plate(400, 390, 195, 120, '#f7f2ea', '#ece2cf');
        // cake
        $s .= '<path d="M 345 380 L 352 320 Q 400 300 448 320 L 455 380 Q 400 396 345 380 Z" fill="#4a2f24"/>';
        $s .= '<ellipse cx="400" cy="322" rx="48" ry="18" fill="#2e1c14"/>';
        $s .= '<ellipse cx="400" cy="322" rx="30" ry="11" fill="#a35a2e"/>';
        $s .= '<path d="M 385 318 q 15 10 30 0 q -5 12 -15 12 q -10 0 -15 -12 Z" fill="#c9763a" fill-opacity="0.9"/>';
        // ice cream
        $s .= '<circle cx="480" cy="330" r="26" fill="#f7f2ea"/><circle cx="480" cy="330" r="26" fill="none" stroke="#e0d0b4" stroke-width="2"/>';
        $s .= garnish([[350, 300, 5, '#7a8b5a'], [360, 292, 4, '#7a8b5a'], [455, 280, 5, '#b4443c']], );
        $s .= '<path d="M 470 300 q 10 -14 22 -10" fill="none" stroke="#7a8b5a" stroke-width="5" stroke-linecap="round"/>';
        $s .= steam(400, 260);
        return $s;
    },
    'baklava' => function () {
        $s = bg_decor('#d4a24e') . plate_shadow() . plate(400, 385, 195, 120, '#f7f2ea', '#ece2cf');
        $pieces = [[340, 360, -14], [400, 350, 4], [460, 360, 16]];
        foreach ($pieces as $p) {
            $s .= sprintf(
                '<g transform="rotate(%d %d %d)">
                    <polygon points="%d,%d %d,%d %d,%d" fill="#d4a24e"/>
                    <polygon points="%d,%d %d,%d %d,%d" fill="#b4863a" fill-opacity="0.75"/>
                </g>',
                $p[2], $p[0], $p[1],
                $p[0] - 32, $p[1] + 18, $p[0] + 32, $p[1] + 18, $p[0], $p[1] - 26,
                $p[0] - 20, $p[1] + 18, $p[0] + 20, $p[1] + 18, $p[0], $p[1] - 10
            );
        }
        $s .= '<path d="M 320 370 q 80 -34 160 0" fill="none" stroke="#c98d2a" stroke-opacity="0.75" stroke-width="4" stroke-dasharray="12 9"/>';
        $s .= garnish([[330, 330, 4, '#7a8b5a'], [400, 322, 4, '#7a8b5a'], [470, 332, 4, '#7a8b5a'], [365, 336, 3, '#7a8b5a'], [435, 338, 3, '#7a8b5a']]);
        return $s;
    },
    // Beverages
    'mocktail' => function () {
        return motif_glass([
            'accent' => '#d4a24e', 'liquid_top' => '#e8c47a', 'liquid_base' => '#b4763a',
            'ice' => [[360, 260, 20], [410, 300, -15], [385, 350, 35]],
            'citrus' => [470, 210, '#e8873a', '#f2b25a'],
            'mint' => [[350, 190], [430, 175]],
            'straw' => [430, 120, 405, 300, '#c96f4a'],
        ]);
    },
    'tea' => function () {
        return motif_glass([
            'accent' => '#c98d4b', 'liquid_top' => '#d9a05b', 'liquid_base' => '#8a5a28',
            'ice' => [],
            'mint' => [[355, 185], [445, 178], [400, 168]],
            'straw' => [],
            'steam' => '#e8d5b7',
        ]);
    },
    'coffee' => function () {
        $s = bg_decor('#d4a24e');
        $s .= '<ellipse cx="400" cy="500" rx="140" ry="24" fill="#000000" fill-opacity="0.22"/>';
        // cup
        $s .= '<path d="M 300 260 L 318 470 Q 400 496 482 470 L 500 260 Z" fill="#3a332d"/>';
        $s .= '<path d="M 300 260 L 500 260 L 494 292 L 306 292 Z" fill="#d4a24e" fill-opacity="0.9"/>';
        $s .= '<ellipse cx="400" cy="262" rx="100" ry="22" fill="#241e1a"/>';
        $s .= '<ellipse cx="400" cy="262" rx="82" ry="15" fill="#3a2418"/>';
        // latte art heart-ish swirl
        $s .= '<path d="M 385 258 q 15 -12 30 0 q -8 12 -15 12 q -7 0 -15 -12 Z" fill="#c98d4b" fill-opacity="0.85"/>';
        // saucer
        $s .= '<ellipse cx="400" cy="486" rx="150" ry="22" fill="#241e1a"/><ellipse cx="400" cy="484" rx="150" ry="22" fill="none" stroke="#d4a24e" stroke-opacity="0.5" stroke-width="2"/>';
        // cardamom pods
        $s .= garnish([[290, 470, 10, '#7a8b5a'], [310, 478, 9, '#6b7f52'], [510, 470, 10, '#7a8b5a']]);
        $s .= steam(400, 200);
        return $s;
    },
];

foreach ($dishes as $slug => $fn) {
    file_put_contents("$dishesDir/{$slug}.svg", $fn());
    echo "dish: {$slug}.svg\n";
}

/* ------------------------------------------------------------------ gallery */

$gallery = [
    'interior' => function () {
        $s = '';
        $s .= '<rect width="800" height="600" fill="none"/>';
        // arches with warm light
        foreach ([170, 400, 630] as $x) {
            $s .= sprintf(
                '<path d="M %d 480 L %d 220 A 90 90 0 0 1 %d 220 L %d 480 Z" fill="#f4ecdf" fill-opacity="0.16"/>
                 <path d="M %d 480 L %d 220 A 90 90 0 0 1 %d 220 L %d 480 Z" fill="none" stroke="#d4a24e" stroke-opacity="0.5" stroke-width="3"/>',
                $x - 90, $x - 90, $x + 90, $x + 90,
                $x - 90, $x - 90, $x + 90, $x + 90
            );
            $s .= sprintf('<circle cx="%d" cy="290" r="60" fill="#d4a24e" fill-opacity="0.12"/>', $x);
        }
        // hanging lamps
        foreach ([250, 550] as $x) {
            $s .= sprintf(
                '<line x1="%d" y1="0" x2="%d" y2="120" stroke="#d4a24e" stroke-opacity="0.6" stroke-width="2"/>
                 <path d="M %d 120 L %d 175 L %d 175 Z" fill="#c96f4a"/>
                 <circle cx="%d" cy="185" r="34" fill="#d4a24e" fill-opacity="0.25"/>',
                $x, $x, $x - 26, $x + 26, $x, $x
            );
        }
        // table silhouettes
        $s .= '<ellipse cx="400" cy="520" rx="150" ry="26" fill="#241e1a" fill-opacity="0.55"/>';
        $s .= '<ellipse cx="400" cy="514" rx="150" ry="26" fill="#2e2822" fill-opacity="0.55"/>';
        $s .= '<ellipse cx="400" cy="508" rx="70" ry="12" fill="#f7f2ea" fill-opacity="0.5"/>';
        return svg_doc($s, '#3d352d', '#241e1a');
    },
    'table-setting' => function () {
        $s = '';
        $s .= '<rect x="0" y="200" width="800" height="400" fill="#4a3f36" fill-opacity="0.5"/>';
        $s .= '<rect x="60" y="200" width="680" height="400" fill="#c96f4a" fill-opacity="0.25"/>';
        // plates
        foreach ([200, 400, 600] as $x) {
            $s .= sprintf(
                '<ellipse cx="%d" cy="400" rx="90" ry="34" fill="#f7f2ea"/>
                 <ellipse cx="%d" cy="396" rx="62" ry="22" fill="#ece2cf"/>
                 <ellipse cx="%d" cy="394" rx="40" ry="14" fill="none" stroke="#d4a24e" stroke-opacity="0.6" stroke-width="2"/>',
                $x, $x, $x
            );
        }
        // candle
        $s .= '<rect x="394" y="280" width="12" height="90" fill="#f7f2ea"/>';
        $s .= '<path d="M 400 262 q 10 -18 0 -30 q -10 12 0 30 Z" fill="#e8a44b"/>';
        $s .= '<circle cx="400" cy="240" r="46" fill="#d4a24e" fill-opacity="0.18"/>';
        // cutlery
        $s .= '<rect x="120" y="390" width="8" height="80" rx="4" fill="#d4a24e" fill-opacity="0.7"/>';
        $s .= '<rect x="672" y="390" width="8" height="80" rx="4" fill="#d4a24e" fill-opacity="0.7"/>';
        return svg_doc($s, '#4a3f36', '#2e2822');
    },
    'plating' => function () {
        $s = '';
        $s .= sprintf(
            '<ellipse cx="400" cy="310" rx="260" ry="190" fill="#f7f2ea"/>
             <ellipse cx="400" cy="306" rx="200" ry="145" fill="#ece2cf"/>
             <ellipse cx="400" cy="302" rx="150" ry="105" fill="#d9a05b"/>
             <ellipse cx="400" cy="298" rx="110" ry="75" fill="#c98d4b"/>'
        );
        for ($i = 0; $i < 5; $i++) {
            $s .= sprintf(
                '<path d="M %d %d q 60 -%d 120 0" fill="none" stroke="#7a8b5a" stroke-opacity="0.85" stroke-width="5" stroke-linecap="round"/>',
                280, 260 + $i * 22, 30 + $i * 6
            );
        }
        $s .= garnish([[340, 240, 8, '#b4443c'], [460, 238, 8, '#b4443c'], [400, 225, 9, '#d4a24e'], [300, 270, 6, '#7a8b5a'], [500, 272, 6, '#7a8b5a']]);
        $s .= '<path d="M 250 330 q 150 60 300 0" fill="none" stroke="#c98d2a" stroke-opacity="0.6" stroke-width="4" stroke-dasharray="14 10"/>';
        return svg_doc($s, '#3a332d', '#241e1a');
    },
    'lounge' => function () {
        $s = '';
        foreach ([150, 320, 490, 660] as $i => $x) {
            $y = 90 + ($i % 2) * 40;
            $s .= sprintf(
                '<line x1="%d" y1="0" x2="%d" y2="%d" stroke="#d4a24e" stroke-opacity="0.5" stroke-width="2"/>
                 <circle cx="%d" cy="%d" r="26" fill="#d4a24e" fill-opacity="0.3"/>
                 <circle cx="%d" cy="%d" r="40" fill="#d4a24e" fill-opacity="0.12"/>',
                $x, $x, $y, $x, $y, $x, $y
            );
        }
        // bokeh
        for ($i = 0; $i < 14; $i++) {
            $x = 60 + ($i * 173) % 680;
            $y = 320 + ($i * 97) % 220;
            $r = 6 + ($i * 7) % 18;
            $s .= sprintf('<circle cx="%d" cy="%d" r="%d" fill="#e8b49a" fill-opacity="0.12"/>', $x, $y, $r);
        }
        // low tables
        foreach ([240, 560] as $x) {
            $s .= sprintf(
                '<ellipse cx="%d" cy="500" rx="110" ry="22" fill="#2e2822" fill-opacity="0.7"/>
                 <ellipse cx="%d" cy="494" rx="70" ry="14" fill="#f7f2ea" fill-opacity="0.35"/>
                 <rect x="%d" y="500" width="8" height="60" fill="#241e1a" fill-opacity="0.7"/>',
                $x, $x, $x - 4
            );
        }
        return svg_doc($s, '#3d352d', '#1d1815');
    },
    'dessert-counter' => function () {
        $s = '';
        $s .= '<rect x="100" y="380" width="600" height="16" fill="#d4a24e" fill-opacity="0.7"/>';
        $s .= '<rect x="120" y="396" width="16" height="150" fill="#2e2822"/><rect x="664" y="396" width="16" height="150" fill="#2e2822"/>';
        // cake stand with dome
        $s .= '<ellipse cx="400" cy="380" rx="120" ry="16" fill="#f7f2ea"/>';
        $s .= '<rect x="394" y="380" width="12" height="40" fill="#d4a24e"/>';
        $s .= '<ellipse cx="400" cy="424" rx="70" ry="12" fill="#f7f2ea"/>';
        $s .= '<path d="M 280 380 A 120 120 0 0 1 520 380 Z" fill="#f7f2ea" fill-opacity="0.18" stroke="#d4a24e" stroke-opacity="0.6" stroke-width="3"/>';
        // macarons
        foreach ([180, 260, 540, 620] as $i => $x) {
            $colors = ['#c96f4a', '#d4a24e', '#7a8b5a', '#b4443c'];
            $c = $colors[$i % 4];
            $s .= sprintf(
                '<g transform="rotate(%d %d 470)">
                    <rect x="%d" y="458" width="56" height="24" rx="12" fill="%s"/>
                    <rect x="%d" y="470" width="56" height="6" fill="#f7f2ea" fill-opacity="0.8"/>
                </g>',
                $i * 8 - 12, $x, $x - 28, $c, $x - 28
            );
        }
        return svg_doc($s, '#4a3826', '#2c2114');
    },
    'spices' => function () {
        $s = '';
        $bowls = [
            [180, 380, '#b4443c', 'sumac'], [400, 360, '#d4a24e', 'saffron'], [620, 380, '#7a8b5a', 'zaatar'],
        ];
        foreach ($bowls as $b) {
            $x = $b[0]; $y = $b[1]; $c = $b[2];
            $s .= sprintf(
                '<path d="M %d %d A 90 70 0 0 0 %d %d Z" fill="#3a332d"/>
                 <ellipse cx="%d" cy="%d" rx="90" ry="30" fill="#f7f2ea"/>
                 <ellipse cx="%d" cy="%d" rx="72" ry="20" fill="%s"/>',
                $x - 90, $y, $x + 90, $y, $x, $y, $x, $y - 2, $c
            );
            for ($i = 0; $i < 10; $i++) {
                $dx = ($i * 23) % 120 - 60;
                $dy = ($i * 13) % 24 - 12;
                $s .= sprintf('<circle cx="%d" cy="%d" r="2.4" fill="#000000" fill-opacity="0.18"/>', $x + $dx, $y - 4 + $dy);
            }
        }
        $s .= steam(400, 260);
        $s .= garnish([[250, 250, 5, '#d4a24e'], [560, 245, 5, '#d4a24e'], [400, 235, 6, '#b4443c']]);
        return svg_doc($s, '#3a2f28', '#241a14');
    },
];

foreach ($gallery as $slug => $fn) {
    file_put_contents("$galleryDir/{$slug}.svg", $fn());
    echo "gallery: {$slug}.svg\n";
}

/* ------------------------------------------------------------------ hero + og */

// Hero illustration — arch framed signature plate
$heroInner = sprintf(
    '<defs>
       <linearGradient id="hbg" x1="0" y1="0" x2="0" y2="1">
         <stop offset="0" stop-color="#3d352d"/><stop offset="1" stop-color="#241e1a"/>
       </linearGradient>
     </defs>
     <rect width="600" height="760" fill="url(#hbg)"/>
     <circle cx="300" cy="380" r="230" fill="#d4a24e" fill-opacity="0.08"/>
     <circle cx="300" cy="380" r="180" fill="none" stroke="#d4a24e" stroke-opacity="0.35" stroke-width="2"/>
     <circle cx="300" cy="380" r="150" fill="none" stroke="#c96f4a" stroke-opacity="0.25" stroke-width="1.5"/>
     <ellipse cx="300" cy="500" rx="170" ry="26" fill="#000000" fill-opacity="0.3"/>
     <ellipse cx="300" cy="420" rx="150" ry="105" fill="#f7f2ea"/>
     <ellipse cx="300" cy="416" rx="112" ry="76" fill="#ece2cf"/>
     <path d="M 210 410 A 90 62 0 0 1 390 410 Z" fill="#e0b25c"/>
     <path d="M 232 406 A 68 44 0 0 1 368 406 Z" fill="#edd08a"/>
     <circle cx="245" cy="372" r="9" fill="#7a8b5a"/>
     <circle cx="355" cy="368" r="9" fill="#7a8b5a"/>
     <circle cx="300" cy="352" r="11" fill="#b4443c"/>
     <circle cx="272" cy="348" r="6" fill="#3a2e1e"/>
     <circle cx="330" cy="346" r="6" fill="#3a2e1e"/>
     <path d="M 280 330 q -12 -22 2 -38 q 10 -12 2 -24" fill="none" stroke="#ffffff" stroke-opacity="0.4" stroke-width="5" stroke-linecap="round"/>
     <path d="M 318 336 q 12 -22 -2 -38 q -10 -12 -2 -24" fill="none" stroke="#ffffff" stroke-opacity="0.3" stroke-width="5" stroke-linecap="round"/>
     <path d="M 230 470 q 70 40 140 0" fill="none" stroke="#d4a24e" stroke-opacity="0.7" stroke-width="4" stroke-dasharray="12 10"/>'
);
file_put_contents(__DIR__ . '/../assets/images/hero-plate.svg', sprintf(
    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 600 760" role="img" aria-label="Signature dish">%s</svg>', $heroInner
));
echo "hero-plate.svg\n";

// OG cover 1200x630
$og = sprintf(
    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 630">
<defs>
  <linearGradient id="og" x1="0" y1="0" x2="1" y2="1">
    <stop offset="0" stop-color="#2e2822"/><stop offset="1" stop-color="#1d1815"/>
  </linearGradient>
</defs>
<rect width="1200" height="630" fill="url(#og)"/>
<circle cx="180" cy="120" r="240" fill="none" stroke="#d4a24e" stroke-opacity="0.12" stroke-width="2"/>
<circle cx="1050" cy="530" r="280" fill="none" stroke="#c96f4a" stroke-opacity="0.10" stroke-width="2"/>
<circle cx="1020" cy="110" r="90" fill="#d4a24e" fill-opacity="0.07"/>
<ellipse cx="420" cy="360" rx="190" ry="130" fill="#f7f2ea" fill-opacity="0.96"/>
<ellipse cx="420" cy="355" rx="142" ry="95" fill="#ece2cf"/>
<path d="M 310 350 A 110 76 0 0 1 530 350 Z" fill="#e0b25c"/>
<path d="M 336 345 A 84 54 0 0 1 504 345 Z" fill="#edd08a"/>
<circle cx="350" cy="312" r="11" fill="#7a8b5a"/>
<circle cx="490" cy="308" r="11" fill="#7a8b5a"/>
<circle cx="420" cy="292" r="13" fill="#b4443c"/>
<text x="660" y="285" font-family="Georgia, serif" font-size="92" fill="#f7f2ea">Uma <tspan font-style="italic" fill="#d4a24e">Lafatik</tspan></text>
<text x="664" y="345" font-family="Georgia, serif" font-style="italic" font-size="34" fill="#c96f4a">A Taste Worth Remembering</text>
<line x1="664" y1="380" x2="1060" y2="380" stroke="#d4a24e" stroke-opacity="0.6" stroke-width="2"/>
<text x="664" y="430" font-family="Jost, sans-serif" font-size="26" fill="#e9ddc9" fill-opacity="0.85">Exceptional food · Warm hospitality</text>
<text x="664" y="470" font-family="Jost, sans-serif" font-size="26" fill="#e9ddc9" fill-opacity="0.85">A memorable dining experience</text>
</svg>'
);
file_put_contents(__DIR__ . '/../assets/images/og-cover.svg', $og);
echo "og-cover.svg\n";

// Brand mark (used in a few places)
$brand = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48"><rect x="2" y="2" width="44" height="44" rx="12" fill="#241e1a"/><path d="M24 10c-4.4 0-7.5 3.3-7.5 7.5v5.2c0 2.1 1.5 3.8 3.5 4.1L15.5 38h3l-1.4-11.2c2.1-.3 3.5-2 3.5-4.1v-5.2c0-2.8-3-7.5-3-7.5z" fill="#d4a24e" transform="translate(3.5 0) scale(0.86)"/><path d="M30 12v24" stroke="#d4a24e" stroke-width="2.5" stroke-linecap="round"/></svg>';
file_put_contents(__DIR__ . '/../assets/images/brand.svg', $brand);
echo "brand.svg\n";

echo "Done. Generated " . count($dishes) . " dishes, " . count($gallery) . " gallery images, hero, og-cover, brand.\n";
