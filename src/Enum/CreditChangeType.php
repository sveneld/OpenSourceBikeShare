<?php

declare(strict_types=1);

namespace BikeShare\Enum;

enum CreditChangeType: string
{
    case OVER_FREE_TIME = 'over_free_time';
    case FLAT_RATE = 'flat_rate';
    case LONG_RENTAL = 'long_rental';
    case RERENT_PENALTY = 'rerent_penalty';
    case DOUBLE_PRICE = 'double_price';
    case COUPON_REDEMPTION = 'coupon_redemption';
    case LONG_STAND_BONUS = 'long_stand_bonus';
    case CREDIT_ADD = 'credit_add';
    case BALANCE_ADJUSTMENT = 'balance_adjustment';
}
