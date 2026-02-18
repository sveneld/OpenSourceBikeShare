<?php

declare(strict_types=1);

namespace BikeShare\Rent\Enum;

enum RentSystemType: string
{
    case WEB = 'web';
    case SMS = 'sms';
    case QR = 'qr';
}
