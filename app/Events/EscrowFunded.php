<?php

namespace App\Events;

use App\Models\Escrow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EscrowFunded
{
    use Dispatchable, SerializesModels;

    public function __construct(public Escrow $escrow) {}
}
