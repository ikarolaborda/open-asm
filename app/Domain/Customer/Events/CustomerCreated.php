<?php

declare(strict_types=1);

namespace App\Domain\Customer\Events;

use App\Domain\Customer\Models\Customer;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CustomerCreated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Customer $customer
    ) {}
}
