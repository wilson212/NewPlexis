<?php
/**
 * Plexis Content Management System
 *
 * @file        system/framework/Collections/Queue.php
 * @copyright   2013, Plexis Dev Team
 * @license     GNU GPL v3
 */
namespace System\Collections;

/**
 * The Queue object represents a first-in, first-out collection of items.
 *
 * 
 */
class Queue implements \IteratorAggregate, \ArrayAccess, \Countable, \Serializable
{
    protected $Items;
    protected $Size;

    /**
     * Constructor
     */
    public function __construct( $Enumerable = false )
    {
        $this->Items = array();

        if( is_array( $Enumerable ) )
            $this->Items = $Enumerable;
        elseif( $Enumerable instanceof \ArrayObject || $Enumerable instanceof \IteratorAggregate
            || $Enumerable instanceof \Iterator || $Enumerable instanceof \Traversable )
        {
            $this->Items = array();

            foreach( $Enumerable as $Item )
                $this->Items[] = $Item;
        }

        $this->Size = sizeof( $this->Items );
    }

    /**
     * Returns the number of items in Queue
     * This method is required by the interface Countable.
     *
     * @return int
     */
    public function count()
    {
        return $this->Size;
    }

    /**
     * Adds an item to the end of the Queue
     *
     * @param mixed $Item
     *
     * @return void
     */
    public function enqueue( $Item )
    {
        $this->Items[] = $Item;
        ++$this->Size;
    }

    /**
     * Removes and returns the item at the beginning of the Queue
     *
     * @throws \Exception Thrown if the Queue is empty
     *	 (Use Queue::Count() to check for the number of Queued items!)
     * @return mixed
     */
    public function dequeue()
    {
        if( $this->Size == 0 )
            throw new \Exception( "The queue is empty!" );

        $Item = array_shift( $this->Items );
        --$this->Size;

        return $Item;
    }

    /**
     * Returns whether there is an item at the specified offset.
     * This method is required by the interface ArrayAccess.
     *
     * @param int $offset The offset to check for
     * @return bool
     */
    public function offsetExists( $offset )
    {
        return array_key_exists( $offset, $this->Items );
    }

    /**
     * Returns the item at the specified offset.
     * This method is required by the interface ArrayAccess.
     *
     * @param int $offset The item index to fetch
     * @return mixed
     */
    public function offsetGet( $offset )
    {
        return $this->offsetExists( $offset ) ? $this->Items[$offset] : null;
    }

    /**
     * This method is required by the interface ArrayAccess.
     *
     * @throws \Exception Items cannot be set via index in this object
     */
    public function offsetSet( $Key, $Value )
    {
        throw new \Exception( "Cannot alter the queue via index, please use Queue->enqueue() instead." );
    }

    /**
     * This method is required by the interface ArrayAccess.
     *
     * @throws \Exception Cannot unset queue items via index
     */
    public function offsetUnset( $Key )
    {
        throw new \Exception( "Cannot unset queue items via index, please use Queue->dequeue() instead." );
    }

    /**
     * Serializes the data, and returns it.
     * This method is required by the interface Serializable.
     *
     * @return string The serialized string
     */
    public function serialize()
    {
        return serialize( $this->Items );
    }

    /**
     * Unserializes the data, and sets up the storage in this container
     * This method is required by the interface Serializable.
     *
     * @param mixed[] $Data
     *
     * @return void
     */
    public function unserialize( $Data )
    {
        $this->Items = unserialize( $Data );
    }

    /**
     * Returns the ArrayIterator of this object
     * This method is required by the interface IteratorAggregate.
     *
     * @return string The serialized string
     */
    public function getIterator()
    {
        return new \ArrayIterator( $this->Items );
    }
}

?>