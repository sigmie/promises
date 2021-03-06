<?php

declare(strict_types=1);

namespace Sigmie\PollOps\Tests\Unit;

use Exception;
use PHPUnit\Framework\TestCase;
use Sigmie\PollOps\Chain;
use Sigmie\PollOps\DefaultOperation;
use Sigmie\PollOps\Exceptions\PromiseRejection;
use Sigmie\PollOps\InsistentOperation;
use Sigmie\PollOps\OperationExecutor;
use Sigmie\PollOps\States\Fulfilled;
use Sigmie\PollOps\States\Pending;
use Sigmie\PollOps\Tests\Fakes\ClosureMockTrait;
use Sigmie\PollOps\Tests\Fakes\FakeOperation;
use Sigmie\PollOps\Tests\Fakes\SleepMockTrait;
use Throwable;

use function Sigmie\PollOps\chain;
use function Sigmie\PollOps\insist;
use function Sigmie\PollOps\operation;

class FunctionsTest extends TestCase
{
    use ClosureMockTrait, SleepMockTrait;

    public function setUp(): void
    {
        parent::setUp();

        $this->closure();
        $this->mockSleep();

        InsistentOperation::setSleep($this->sleepFunction);
        Pending::setSleep($this->sleepFunction);
    }

    /**
     * @test
     */
    public function operation_is_void()
    {
        $this->assertNull(operation(fn () => 'foo-bar')->proceed());
    }

    /**
     * @test
     */
    public function operation_executes_operation_instance_and_caches_on_rejection()
    {
        $this->expectClosureCalledTimes(3);

        $verifyResult = false;

        operation(new FakeOperation($this->closureMock, $verifyResult))
            ->catch(fn ($promiseRejection) => ($this->closureMock)())
            ->then(fn () => null)
            ->finally(fn () => ($this->closureMock)())
            ->proceed();
    }

    /**
     * @test
     */
    public function operation_executes_operation_instance_and_calls_then_callback()
    {
        $this->expectClosureCalledTimes(3);

        $verifyResult = true;

        operation(new FakeOperation($this->closureMock, $verifyResult))
            ->then(fn () => ($this->closureMock)())
            ->catch(fn () => null)
            ->finally(fn () => ($this->closureMock)())
            ->proceed();
    }

    /**
     * @test
     */
    public function operation_executes_operation_instance()
    {
        $this->expectClosureCalledOnce();

        $verifyResult = true;

        operation(new FakeOperation($this->closureMock, $verifyResult))
            ->proceed();
    }

    /**
     * @test
     */
    public function operation_accepts_operation_instance()
    {
        $this->closureWillReturn(new Fulfilled([]));

        $this->expectClosureCalledOnce();

        operation(new DefaultOperation($this->closureMock))->proceed();
    }

    /**
     * @test
     */
    public function operation_max_attempts()
    {
        $operation = operation($this->closureMock)
            ->maxAttempts(3)
            ->attemptsInterval(90)
            ->create();

        $this->assertEquals(3, $operation->maxAttempts());
        $this->assertEquals(90, $operation->attemptsInterval());
    }

    /**
     * @test
     */
    public function operation_catch_call()
    {
        $mock = $this->getMockBuilder(\stdClass::class)->addMethods(['failed', 'succeeded', 'finally'])->getMock();

        $mock->expects($this->once())->method('failed');
        $mock->expects($this->once())->method('finally');
        $mock->expects($this->never())->method('succeeded');

        operation($this->closureMock)
            ->verify(fn () => false)
            ->catch(fn () => $mock->failed())
            ->then(fn () => $mock->succeeded())
            ->finally(fn () => $mock->finally())
            ->proceed();
    }

    /**
     * @test
     */
    public function chain_returns_chain_instance()
    {
        $this->assertInstanceOf(Chain::class, chain([]));
    }

    /**
     * @test
     */
    public function operation_chaining_catches()
    {
        $this->expectClosureCalledOnce();

        chain([
            new FakeOperation(fn () => null),
            new FakeOperation(fn () => null, false), // This operation rejects 
            new FakeOperation(fn () => null),
        ])->catch(function () {
            ($this->closureMock)();
        })->proceed();
    }

    /**
     * @test
     */
    public function closure_called_once_if_not_verify()
    {
        $this->expectClosureCalledOnce();

        operation($this->closureMock)->proceed();
    }

    /**
     * @test
     */
    public function operation_returns_operation_builder()
    {
        $this->assertInstanceOf(OperationExecutor::class, operation($this->closureMock));
    }

    /**
     * @test
     */
    public function test_closure_tries()
    {
        $this->closureWillReturn(false, false, false, false, false);

        $this->expectClosureCalledTimes(3);

        insist($this->closureMock)
            ->tries(3)->proceed();
    }

    /**
     * @test
     */
    public function insistent_breaks_on_exception()
    {
        $callback = function () {
            throw new Exception('Something went wrong');
        };

        $this->expectException(Exception::class);

        insist($callback)
            ->tries(3)->proceed();
    }

    /**
     * @test
     */
    public function insistent_catch_ignores_exception()
    {
        $callback = function () {
            throw new Exception('Something went wrong');
        };

        insist($callback)
            ->catchExceptions()
            ->tries(3)->proceed();

        //No exception was thrown
        $this->addToAssertionCount(1);
    }

    /**
     * @test
     */
    public function insist_returns_insistent_operation_instance(): void
    {
        $this->assertInstanceOf(InsistentOperation::class, insist($this->closureMock));
    }
}
