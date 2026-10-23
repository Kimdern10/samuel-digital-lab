<?php

use App\Support\ImageOptimizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('large uploads are resized and saved as webp with a small copy', function () {
    Storage::fake('public');

    $path = app(ImageOptimizer::class)->store(UploadedFile::fake()->image('screenshot.png', 3000, 2000));

    expect($path)->toStartWith('works/')->toEndWith('.webp');

    $large = getimagesizefromstring(Storage::disk('public')->get($path));
    $small = getimagesizefromstring(Storage::disk('public')->get(str_replace('.webp', '-sm.webp', $path)));

    expect($large['mime'])->toBe('image/webp')
        ->and($large[0])->toBe(ImageOptimizer::LARGE_WIDTH)
        ->and($large[1])->toBe(1067)
        ->and($small[0])->toBe(ImageOptimizer::SMALL_WIDTH);
});

test('small uploads are not enlarged', function () {
    Storage::fake('public');

    $path = app(ImageOptimizer::class)->store(UploadedFile::fake()->image('photo.jpg', 640, 480));

    expect(getimagesizefromstring(Storage::disk('public')->get($path))[0])->toBe(640);
});
