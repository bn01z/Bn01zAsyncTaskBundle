<?php

namespace Bn01z\AsyncTask\Task;

final class TaskStatus implements \JsonSerializable
{
    const STATUS_UNKNOWN = 0;
    const STATUS_QUEUED = 1;
    const STATUS_PROCESSING = 2;
    const STATUS_FINISHED = 3;
    const STATUS_FAILED = 4;

    const STATUSES = [
        self::STATUS_UNKNOWN,
        self::STATUS_QUEUED,
        self::STATUS_PROCESSING,
        self::STATUS_FINISHED,
        self::STATUS_FAILED,
    ];

    /**
     * @var string
     */
    private $id;
    /**
     * @var string|null
     */
    private $message = null;
    /**
     * @var mixed|null
     */
    private $result = null;
    /**
     * @var float
     */
    private $progress = 0;
    /**
     * @var int
     */
    private $status = self::STATUS_QUEUED;

    public static function getIdentifierFromTopicName(string $topicName): ?string
    {
        return explode('/', $topicName, 2)[1] ?? null;
    }

    public static function isChangedStatusTopicName(string $topicName): bool
    {
        return 'changed' === explode('/', $topicName, 2)[0];
    }

    public static function isFinishedStatusTopicName(string $topicName): bool
    {
        return 'finished' === explode('/', $topicName, 2)[0];
    }

    public function __construct(string $id)
    {
        $this->id = $id;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getChangedStatusTopicName()
    {
        return sprintf('changed/%s', $this->getId());
    }

    public function getFinishedStatusTopicName()
    {
        return sprintf('finished/%s', $this->getId());
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getResult()
    {
        return $this->result;
    }

    public function setResult($result): self
    {
        $this->result = $result;

        return $this;
    }

    public function getProgress(): float
    {
        return $this->progress;
    }

    public function setProgress(float $progress): self
    {
        $this->progress = $progress;

        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        if (!in_array($status, self::STATUSES)) {
            throw new AsyncTaskException('Wrong async task status!');
        }
        $this->status = $status;

        return $this;
    }

    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'message' => $this->message,
            'progress' => $this->progress,
            'status' => $this->status,
            'topics' => [
                'changed' => $this->getChangedStatusTopicName(),
                'finished' => $this->getFinishedStatusTopicName(),
            ],
        ];
    }
}
