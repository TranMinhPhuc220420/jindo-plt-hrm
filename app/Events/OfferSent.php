<?php

namespace App\Events;

use App\Models\Offer;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OfferSent
{
    use Dispatchable, SerializesModels;

    public function __construct(public Offer $offer) {}
}
