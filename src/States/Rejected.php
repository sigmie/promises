<?php

declare(strict_types=1);

namespace Sigmie\PollOps\States;

use Closure;
use Exception;
use Sigmie\PollOps\Exceptions\PromiseRejection;

class Rejected
{
    /**
     * Rejection exception
     *
     * @var PromiseRejection
     */
    private PromiseRejection $reason;

    /**
     * Constructor
     *
     * @param PromiseRejection $reason
     */
    public function __construct(PromiseRejection $reason)
    {
        $this->reason = $reason;
    }

    /**
     * Call catch function with rejection reason
     *
     * @return Settled
     */
    public function reject(Closure $catch): Settled
    {
        ($catch)($this->reason);

        return new Settled;
    }
}
