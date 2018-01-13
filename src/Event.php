<?php

/**
 * Execute callback function for certain triggers.
 */

namespace TheFox\Smtp;

class Event
{
    const TRIGGER_NEW_MAIL = 1000;
    const TRIGGER_NEW_RCPT = 2000;
    const TRIGGER_AUTH_ATTEMPT = 9000;

    /**
     * @var int
     */
    private $trigger;

    /**
     * @var object
     */
    private $object;

    /**
     * @var \Closure
     */
    private $function;

    /**
     * @var mixed
     */
    private $returnValue;

    /**
     * Event constructor.
     * @param null|int $trigger
     * @param null|object $object
     * @param null|\Closure|string $function
     */
    public function __construct($trigger = null, $object = null, $function = null)
    {
        $this->trigger = $trigger;
        $this->object = $object;
        $this->function = $function;
    }

    /**
     * @return int|null
     */
    public function getTrigger()
    {
        return $this->trigger;
    }

    /**
     * @return mixed
     */
    public function getReturnValue()
    {
        return $this->returnValue;
    }

    /**
     * @param array $args
     * @return mixed
     */
    public function execute(array $args = [])
    {
        $object = $this->object;
        $function = $this->function;

        array_unshift($args, $this);

        if ($object) {
            $this->returnValue = call_user_func_array([$object, $function], $args);
        } else {
            $this->returnValue = call_user_func_array($function, $args);
        }

        return $this->returnValue;
    }
}
