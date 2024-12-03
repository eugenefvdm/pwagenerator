<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Intervention\Image\Facades\Image;

class PWAGeneratorController extends Controller
{
    private $iconSizes = [48, 72, 96, 144, 168, 192, 512];
    private $splashSizes = [
        ['width' => 1242, 'height' => 2688],
        ['width' => 1125, 'height' => 2436],
        ['width' => 828, 'height' => 1792],
    ];

    public function index()
    {
        return view('pwa-generator', [
            'iconSizes' => $this->iconSizes,
            'splashSizes' => $this->splashSizes
        ]);
    }

    public function generate(Request $request)
    {
        $svgContent = $request->input('svg');
        $type = $request->input('type');
        $width = $request->input('width');
        $height = $request->input('height', $width);

        // Create temporary SVG file
        $tempSvg = tempnam(sys_get_temp_dir(), 'svg_');
        file_put_contents($tempSvg, $svgContent);

        // Convert SVG to PNG using Imagick
        $imagick = new \Imagick();
        $imagick->setBackgroundColor(new \ImagickPixel('transparent'));
        $imagick->readImage($tempSvg);
        $imagick->setImageFormat('png');

        // Create temporary PNG file
        $tempPng = tempnam(sys_get_temp_dir(), 'png_');
        $imagick->writeImage($tempPng);

        // Now we can use Intervention Image with the PNG
        $image = Image::make($tempPng);

        if ($type === 'icon') {
            $image->resize($width, $width);
            $filename = "icon-{$width}x{$width}.png";
        } else {
            // Create white canvas for splash screen
            $canvas = Image::canvas($width, $height, '#ffffff');

            // Calculate icon size and position
            $iconSize = min($width, $height) * 0.4;
            $x = ($width - $iconSize) / 2;
            $y = ($height - $iconSize) / 2;

            // Resize icon and place it on canvas
            $image->resize($iconSize, $iconSize);
            $canvas->insert($image, 'top-left', (int)$x, (int)$y);
            $image = $canvas;
            $filename = "splash-{$width}x{$height}.png";
        }

        // Cleanup temporary files
        unlink($tempSvg);
        unlink($tempPng);

        return response()->streamDownload(function() use ($image) {
            echo $image->encode('png');
        }, $filename);
    }
}
