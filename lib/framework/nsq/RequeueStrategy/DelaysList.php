<?php

namespace lib\framework\nsq\RequeueStrategy;

use lib\framework\nsq\Message\MessageInterface;

/**
 * List of delays requeue strategy
 *
 * Retry all failed messages N times with delay picked from a list.
 */
class DelaysList implements RequeueStrategyInterface
{
    /**
     * Number of attempts to make
     *
     * @var integer
     */
    private $maxAttempts;

    /**
     * List of delays
     *
     * @var array
     */
    private $delays = array();

    /**
     * Constructor
     *
     * @param integer $maxAttempts 尝试次数
     * @param array $delays 延迟时间
     */
    public function __construct($maxAttempts = 10, array $delays = array('50'))
    {
        $this->maxAttempts = $maxAttempts;
        $this->delays = $delays;
        if (!$this->delays) {
            throw new \InvalidArgumentException("Delays list cannot be empty");
        }
    }

    /**
     * {@inheritDoc}
     */
    public function shouldRequeue(MessageInterface $msg)
    {
        $attempts = $msg->getAttempts();
        $delay = isset($this->delays[$attempts - 1]) ?
            $this->delays[$attempts - 1] : $this->delays[count($this->delays) - 1]
        ;
        return $attempts < $this->maxAttempts
                ? $delay
                : NULL;
    }
}