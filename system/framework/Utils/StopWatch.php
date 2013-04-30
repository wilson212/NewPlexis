<?php
/**
 * Plexis Content Management System
 *
 * @file        system/framework/Utils/Stopwatch.php
 * @copyright   2013, Plexis Dev Team
 * @license     GNU GPL v3
 */
namespace System\Utils;

/**
 * Provides a set of methods that you can use to accurately measure elapsed time.
 *
 * @author      Steven Wilson 
 * @package     System
 * @subpackage  
 */
class Stopwatch
{
    /**
     * The total elapsed time counter
     * @var float
     */
    protected $buffer = 0;

    /**
     * The start time from the last pause or stop
     * @var float
     */
    protected $start = 0;

    /**
     * Indicates whether the timer is ticking.
     * @var bool
     */
    protected $isRunning = false;

    /**
     * Starts, or resumes, measuring elapsed time for an interval.
     *
     * @return void
     */
    public function start()
    {
        if(!$this->isRunning)
        {
            $this->start = microtime(true);
            $this->isRunning = true;
        }
    }

    /**
     * Stops measuring elapsed time for an interval.
     *
     * @return void
     */
    public function stop()
    {
        if($this->isRunning)
        {
            $this->buffer += microtime(true) - $this->start;
            $this->isRunning = false;
        }
    }

    /**
     * Stops time interval measurement, resets the elapsed time to zero,
     * and starts measuring elapsed time.
     *
     * @return void
     */
    public function restart()
    {
        $this->buffer = 0;
        $this->start = microtime(true);
        $this->isRunning = true;
    }

    /**
     * Stops time interval measurement and resets the elapsed time to zero.
     *
     * @return void
     */
    public function reset()
    {
        $this->isRunning = false;
        $this->buffer = 0;
        $this->start = 0;
    }

    /**
     * Gets the total elapsed time measured in microseconds
     *
     * @return float|int
     */
    public function elapsedTime()
    {
        if($this->isRunning)
            return ($this->buffer + (microtime(true) - $this->start));
        else
            return $this->buffer;
    }

    /**
     * Gets a value indicating whether the Stopwatch timer is running.
     *
     * @return bool
     */
    public function isRunning()
    {
        return $this->isRunning;
    }

    /**
     * Initializes a new Stopwatch instance, and starts measuring time.
     *
     * @return Stopwatch
     */
    public static function StartNew()
    {
        $Sw = new Stopwatch();
        $Sw->start();
        return $Sw;
    }
}