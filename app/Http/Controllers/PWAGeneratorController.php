<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Imagick;
use ImagickException;
use ImagickPixel;
use Intervention\Image\Facades\Image;

class PWAGeneratorController extends Controller
{
    private array $iconSizes = [48, 72, 96, 144, 168, 128, 152, 192, 384, 512];
    private array $splashSizes = [
        ['width' => 640, 'height' => 1136],  // iPhone SE, iPod Touch
        ['width' => 720, 'height' => 720], // This was required for a screenshot recommendation from ChatGPT
        ['width' => 750, 'height' => 1334], // iPhone 6, 6s, 7, 8
        ['width' => 828, 'height' => 1792], // iPhone XR, iPhone 11
        ['width' => 1080, 'height' => 2160], // Modern mid-range Android phones
        ['width' => 1125, 'height' => 2436], // iPhone X, XS, 11 Pro
        ['width' => 1136, 'height' => 640],  // Landscape iPhone 5, 5s, 5c
        ['width' => 1242, 'height' => 2688], // iPhone XS Max, 11 Pro Max
        ['width' => 1280, 'height' => 720],  // Lower-end Android phones
        ['width' => 1334, 'height' => 750],  // iPhone 6 Plus, 6s Plus, 7 Plus, 8 Plus
        ['width' => 1440, 'height' => 2960], // Ultra-wide Android (Samsung Galaxy S8/S9)
        ['width' => 1536, 'height' => 2048], // iPad Mini, iPad 9.7-inch
        ['width' => 1668, 'height' => 2224], // iPad Pro 10.5-inch
        ['width' => 1668, 'height' => 2388], // iPad Pro 11-inch
        ['width' => 1792, 'height' => 828],  // Landscape iPhone XR, iPhone 11
        ['width' => 1920, 'height' => 1080], // Common Android resolution
        ['width' => 2048, 'height' => 1536], // Landscape iPad Mini, iPad 9.7-inch
        ['width' => 2048, 'height' => 2732], // iPad Pro 12.9-inch
        ['width' => 2160, 'height' => 3840], // High-end Android phones, 4K screens
        ['width' => 2224, 'height' => 1668], // Landscape iPad Pro 10.5-inch
        ['width' => 2388, 'height' => 1668], // Landscape iPad Pro 11-inch
        ['width' => 2436, 'height' => 1125], // Landscape iPhone X, XS, 11 Pro
        ['width' => 2688, 'height' => 1242], // Landscape iPhone XS Max, 11 Pro Max
        ['width' => 2732, 'height' => 2048], // Landscape iPad Pro 12.9-inch
        ['width' => 3840, 'height' => 2160], // Landscape High-end Android, 4K screens
    ];

    public function index()
    {
        return view('pwa-generator', [
            'iconSizes' => $this->iconSizes,
            'splashSizes' => $this->splashSizes
        ]);
    }

    /**
     * @throws ImagickException
     */
    public function generate(Request $request)
    {
        $svgContent = $request->input('svg');
        $type = $request->input('type');
        $width = $request->input('width');
        $height = $request->input('height', $width);

        // Create a temporary SVG file
        $tempSvg = tempnam(sys_get_temp_dir(), 'svg_');
        file_put_contents($tempSvg, $svgContent);

        // Convert SVG to PNG using Imagick
        $imagick = new Imagick();
        $imagick->setBackgroundColor(new ImagickPixel('transparent'));
        $imagick->readImage($tempSvg);
        $imagick->setImageFormat('png');

        // Create a temporary PNG file
        $tempPng = tempnam(sys_get_temp_dir(), 'png_');
        $imagick->writeImage($tempPng);

        // Now we can use Intervention Image with the PNG
        $image = Image::make($tempPng);

        if ($type === 'icon') {
            // Resize maintaining the aspect ratio to fit within bounds
            $image->resize($width, $width, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });

            // Create a square canvas and center the image
            $canvas = Image::canvas($width, $width, '#ffffff');
            $x = max(0, ($width - $image->width()) / 2);
            $y = max(0, ($width - $image->height()) / 2);

            $canvas->insert($image, 'top-left', (int)$x, (int)$y);
            $image = $canvas;

            $filename = "icon-{$width}x$width.png";
        } else {
            // Create white canvas for splash screen
            $canvas = Image::canvas($width, $height, '#ffffff');

            // Calculate icon size while maintaining the aspect ratio
            $iconSize = min($width, $height) * 0.4;
            $image->resize($iconSize, $iconSize, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });

            // Center the image on the canvas
            $x = ($width - $image->width()) / 2;
            $y = ($height - $image->height()) / 2;

            $canvas->insert($image, 'top-left', (int)$x, (int)$y);
            $image = $canvas;
            $filename = "splash-{$width}x$height.png";
        }

        // Cleanup temporary files
        unlink($tempSvg);
        unlink($tempPng);

        return response()->streamDownload(function() use ($image) {
            echo $image->encode('png');
        }, $filename);
    }
}
