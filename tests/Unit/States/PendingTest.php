<?php

declare(strict_types=1);

namespace Sigmie\PollOps\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sigmie\PollOps\AbstractOperation;
use Sigmie\PollOps\States\Fulfilled;
use Sigmie\PollOps\States\Pending;


if (!function_exists('Sigmie\PollOps\Tests\Unit\sleep')) {
    function sleep()
    {
    }
}

class PendingTest extends TestCase
{
    /**
     * Setup
     */
    public function setUp(): void
    {
        Pending::setSleep('Sigmie\PollOps\Tests\Unit\sleep');
    }

    /**
     * @test
     */
    public function settle_calls_promise_verify(): void
    {
        $promiseMock = $this->createMock(AbstractOperation::class);
        $promiseMock->method('verify')->willReturn(true);
        $promiseMock->method('maxAttempts')->willReturn(3);
        $promiseMock->method('attemptsInterval')->willReturn(10);

        $pending = new Pending([], $promiseMock);

        $promiseMock->expects($this->exactly(1))->method('verify');

        $pending->settle();
    }

    /**
     * @test
     */
    public function settle_calls_promise_verify_max_times_before_returning_false(): void
    {
        $promiseMock = $this->createMock(AbstractOperation::class);
        $promiseMock->method('verify')->willReturn(false);
        $promiseMock->method('maxAttempts')->willReturn(3);
        $promiseMock->method('attemptsInterval')->willReturn(10);

        $pending = new Pending([], $promiseMock);

        $promiseMock->expects($this->exactly(3))->method('verify');

        $pending->settle();
    }

    /**
     * @test
     */
    public function fulfilled_response_on_verification_success()
    {
        $promiseMock = $this->createMock(AbstractOperation::class);
        $promiseMock->method('verify')->willReturn(true);
        $promiseMock->method('maxAttempts')->willReturn(3);
        $promiseMock->method('attemptsInterval')->willReturn(10);

        $pending = new Pending([], $promiseMock);

        $response = $pending->settle();

        $this->assertInstanceOf(Fulfilled::class, $response);
    }
}
