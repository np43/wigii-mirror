<?php
/**
 * Adapts a Wigii DebugLogger to a Guzzle logger object
 */
class DebugLoggerGuzzleLogAdapter extends Guzzle\Log\AbstractLogAdapter
{
    public function __construct($debugLogger)
    {
        $this->log = $debugLogger;
    }

    public function log($message, $priority = LOG_INFO, $extras = array())
    {
        $this->log->write($message);
    }
}
