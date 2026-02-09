<?php

namespace HeimrichHannot\ResourceBookingBundle\Booking;

class StepResult
{
    public const NEXT = 'next';
    public const FINISH = 'finish';
    public const RESTART = 'restart';
    public const WAIT = 'wait';
    public const CANCEL = 'cancel';
    public const ERROR = 'error';
    public const DONE = 'done';

    public function __construct(
        public string $action = self::NEXT,
        public ?string $message = null,
        public array $meta = [],
    ) {}

    public function action(): string
    {
        return $this->action;
    }

    public function message(): ?string
    {
        return $this->message;
    }

    public function meta(): array
    {
        return $this->meta;
    }

    public function isNext(): bool
    {
        return self::NEXT === $this->action;
    }

    public function isFinish(): bool
    {
        return self::FINISH === $this->action;
    }

    public function isRestart(): bool
    {
        return self::RESTART === $this->action;
    }

    public function isWait(): bool
    {
        return self::WAIT === $this->action;
    }

    public function isCancel(): bool
    {
        return self::CANCEL === $this->action;
    }

    public function isError(): bool
    {
        return self::ERROR === $this->action;
    }

    public function isDone(): bool
    {
        return self::DONE === $this->action;
    }

    public static function next(): self
    {
        return new self(self::NEXT);
    }

    public static function finish(?string $message = null): self
    {
        return new self(self::FINISH, $message);
    }

    public static function restart(?string $message = null): self
    {
        return new self(self::RESTART, $message);
    }

    public static function wait(?string $message = null): self
    {
        return new self(self::WAIT, $message);
    }

    public static function cancel(?string $message = null): self
    {
        return new self(self::CANCEL, $message);
    }

    public static function error(?string $message = null): self
    {
        return new self(self::ERROR, $message);
    }

    public static function done(array $meta = [], ?string $message = null): self
    {
        return new self(self::DONE, $message, $meta);
    }
}