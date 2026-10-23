<?php

use App\Support\WhatsApp;

test('nigerian numbers are converted to international format', function (string $phone, string $expected) {
    expect(WhatsApp::number($phone))->toBe($expected);
})->with([
    'local with leading zero' => ['09060354675', '2349060354675'],
    'with spaces and plus' => ['+234 906 035 4675', '2349060354675'],
    'already international' => ['2347025918539', '2347025918539'],
    'without leading zero' => ['7025918539', '2347025918539'],
]);

test('links open a chat with the message typed in', function () {
    expect(WhatsApp::link('09060354675'))->toBe('https://wa.me/2349060354675')
        ->and(WhatsApp::link('09060354675', 'Hello there'))->toBe('https://wa.me/2349060354675?text=Hello%20there');
});
