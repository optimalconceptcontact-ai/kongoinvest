<?php

namespace App\Events;

use App\Models\KycProfile;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class KycRejected
{
    use Dispatchable, SerializesModels;

    public function __construct(public KycProfile $kycProfile) {}
}
