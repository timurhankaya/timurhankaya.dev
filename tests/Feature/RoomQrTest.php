<?php

use App\Support\Games\RoomQr;

it('draws the room link as an inline svg qr code on our own server', function () {
    $svg = RoomQr::svg('https://timurhankaya.dev/games/imposter/ABC123');

    expect($svg)->toStartWith('<svg')->toContain('<path')->toContain('viewBox')
        ->not->toContain('<?xml')->not->toContain('data:image');
});

it('never sends a room link to a third-party qr service', function () {
    $views = collect(glob(resource_path('views/livewire/games/*/*.blade.php')))->map(fn (string $file): string => file_get_contents($file))->implode('');

    expect($views)->not->toContain('qrserver.com')->toContain('RoomQr::svg');
});
