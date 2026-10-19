<?php

namespace App\Support\Games;

use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

/**
 * QR code for a room link, drawn on our server as inline SVG so the link never reaches a third-party service.
 * Only dark modules are drawn; games.css colours them and supplies the light tile behind.
 */
final class RoomQr
{
    public static function svg(string $url): string
    {
        $options = new QROptions([
            'outputType' => QROutputInterface::MARKUP_SVG,
            'outputBase64' => false,
            'svgAddXmlHeader' => false,
            'drawLightModules' => false,
            'connectPaths' => true,
            'addQuietzone' => true,
            'quietzoneSize' => 1,
            'cssClass' => 'game-qr-code',
        ]);

        return trim((new QRCode($options))->render($url));
    }
}
