<?php

namespace App\Events;

use App\Models\Escrow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EscrowRefunded
{
    use Dispatchable, SerializesModels;

    public function __construct(public Escrow $escrow) {}
}
