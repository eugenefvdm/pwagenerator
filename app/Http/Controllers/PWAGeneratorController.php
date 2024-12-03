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

        // Create a temporary SVG file
        $tempSvg = tempnam(sys_get_temp_dir(), 'svg_');
        file_put_contents($tempSvg, $svgContent);

        // Convert SVG to PNG using Imagick
        $imagick = new \Imagick();
        $imagick->setBackgroundColor(new \ImagickPixel('transparent'));
        $imagick->readImage($tempSvg);
        $imagick->setImageFormat('png');

        // Create a temporary PNG file
        $tempPng = tempnam(sys_get_temp_dir(), 'png_');
        $imagick->writeImage($tempPng);

        // Now we can use Intervention Image with the PNG
        $image = Image::make($tempPng);

        if ($type === 'icon') {
            // Resize maintaining aspect ratio to fit within bounds
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

            $filename = "icon-{$width}x{$width}.png";
        } else {

//        if ($type === 'icon') {
//            // Resize maintaining the aspect ratio and ensure it fits within the bounds
//            $image->resize($width, $width, function ($constraint) {
//                $constraint->aspectRatio();
//                $constraint->upsize();
//            });
//
//            // Create a square canvas of the target size
//            $canvas = Image::canvas($width, $width, 'transparent');
//
//            // Center the resized image on the canvas
//            $x = ($width - $image->width()) / 2;
//            $y = ($width - $image->height()) / 2;
//            $canvas->insert($image, 'top-left', (int)$x, (int)$y);
//            $image = $canvas;
//
//            $filename = "icon-{$width}x{$width}.png";
//        } else {
            // Create white canvas for splash screen
            $canvas = Image::canvas($width, $height, '#ffffff');

            // Calculate icon size while maintaining aspect ratio
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
