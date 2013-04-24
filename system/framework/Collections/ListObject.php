<?php
/**
 * Plexis Content Management System
 *
 * @file        system/framework/Collections/ListObject.php
 * @copyright   2013, Plexis Dev Team
 * @license     GNU GPL v3
 */
namespace System\Collections;

/**
 * The ListObject implements an integer-indexed collection class, just like an array.
 *
 * You can access and add items to the collection using the "add", "insertAt", "removeAt",
 * and "itemAt" methods, or you can use this object like an array:
 * <ul>
 *    <li> $list[] = $value </li>
 *    <li> $list[$index] = $value </li>
 *    <li> unset($list[$index]) </li>
 *    <li> if(isset($list[$index])) </li>
 *    <li> $numItems = count($list) </li>
 *    <li> foreach($list as $item) </li>
 * </ul>
 */
class ListObject implements \IteratorAggregate, \ArrayAccess, \Countable, \Serializable
{
	/**
	 * Data Container.
	 * @var mixed[]
	 */
	private $data = array();
	
	/**
	 * The index count of the data container
	 * @var int
	 */
	protected $index = 0;
	
	/**
	 * Represents whether this list is read-only.
	 * @var bool
	 */
	protected $isReadOnly = false;
	
	/**
	 * Constructor
	 */
	public function __construct($readOnly = false)
	{
		$this->isReadOnly = $readOnly;
	}
	
	/**
	 * Appends an item at the end of the list.
	 *
	 * @param mixed $item the new item to add
	 *
	 * @return int the zero based index at which the item is added
	 */
	public function add($item)
	{
		$this->insertAt($this->index, $item);
		return $this->index -1; // Count is incremented from insertAt, so return -1
	}
	
	/**
	 * Returns where the dictionary contains a value
	 *
	 * @param mixed $item The value to search for
	 * @return bool
	 */
	public function contains($item)
	{
		return $this->indexOf($item) >= 0;
	}

    /**
     * Removes all items from the dictionary
     *
     * @throws \Exception Thrown if the ListObject is Read Only
     * @return void
     */
	public function clear()
	{
		if($this->isReadOnly)
			throw new \Exception();
		
		$this->data = array();
		$this->index = 0;
	}
	
	/**
	 * Returns the index of the specified item
	 * 
	 * @param mixed $item The item to search for
	 * @return int The zero based index of the item, or -1 if the item doesn't exist
	 */
	public function indexOf($item)
	{
		return (($index = array_search($item, $this->data, true)) !== false) ? $index : -1;
	}

    /**
     * Inserts a new item at the specified index location
     *
     * @param int $index The index to place the item at.
     * @param $item
     *
     * @throws \OutOfBoundsException If the specified index was out of bounds
     * @throws \Exception Thrown if the ListObject is Read Only
     * @return void
     */
	public function insertAt($index, $item)
	{
		if($this->isReadOnly)
			throw new \Exception();
		
		if($index == $this->index)
		{
			$this->data[$index] = $item;
			++$this->index;
		}
		elseif($index >= 0 && $index < $this->index)
		{
			array_splice($this->data, $index, 0, array($item));
			++$this->index;
		}
		else
			throw new \OutOfBoundsException();
	}
	
	/**
	 * Returns the item at the specified index
	 * 
	 * @param int $index The zero based index of the item being requested
     *
	 * @throws \OutOfBoundsException If the specified index was out of bounds
     *
	 * @return mixed Returns the item of the specified index
	 */
	public function itemAt($index)
	{
		if($index >= 0 && $index < $this->index)
			return $this->data[$index];
		else
			throw new \OutOfBoundsException();
	}
	
	/**
	 * Removes an item value from the dictionary
	 * 
	 * @param mixed $item The item value to search for
     *
     * @throws \Exception Thrown if the ListObject is Read Only
     *
	 * @return int|bool The zero based index of the item was removed from, or false
	 */
	public function remove($item)
	{
		// Check that the item exists
		if(($index = $this->indexOf($item)) >= 0)
		{
			$this->removeAt($index);
			return $index;
		}
		
		return false;
	}

    /**
     * Removes an item at a specified index
     *
     * @param int $index The zero based index of the item to remove
     *
     * @throws \OutOfBoundsException If the specified index was out of bounds
     * @throws \Exception Thrown if the ListObject is Read Only
     *
     * @return mixed Returns the value of the item that was removed
     */
	public function removeAt($index)
	{
		if(!$this->isReadOnly)
			throw new \Exception();
		
		if($index >= 0 && $index < $this->index)
		{
			--$this->index;
			if($index === $this->index)
			{
				return array_pop($this->data);
			}
			else
			{
				$item = $this->data[$index];
				array_splice($this->data, $index, 1);
				return $item;
			}
		}
		else
			throw new \OutOfBoundsException();
	}
	
	/**
	 * Returns the list as an array
	 * @return mixed[]
	 */
	public function toArray()
	{
		return $this->data;
	}
	
	// === Interface / Abstract Methods === //
	
	/**
	 * Returns the number of items in the list
	 * This method is required by the interface Countable.
	 *
	 * @return int
	 */
	public function count()
	{
		return count($this->data);
	}
	
	/**
	 * Returns whether there is an item at the specified offset.
	 * This method is required by the interface ArrayAccess.
	 *
	 * @param int $index The offset to check for
	 * @return bool
	 */
	public function offsetExists($index)
	{
		return ($index >= 0 && $index < $this->index);
	}
	
	/**
	 * Returns the item at the specified offset.
	 * This method is required by the interface ArrayAccess.
	 *
	 * @param int $index The item index to fetch
	 * @return mixed
	 */
	public function offsetGet($index)
	{
		return $this->itemAt($index);
	}
	
	/**
	 * Sets the item at the specified index.
	 * This method is required by the interface ArrayAccess.
	 *
	 * @param int $index The item index to set
	 * @param mixed $item The item's value
	 * @return void
	 */
	public function offsetSet($index, $item)
	{
		// If no index is supplied, add a new item to the list
		if($index === null || $index === $this->index)
		{
			$this->insertAt($this->index, $item);
		}
		else
		{
			$this->removeAt($index);
			$this->insertAt($index, $item);
		}
	}
	
	/**
	 * Removes the item at the specified index.
	 * This method is required by the interface ArrayAccess.
	 *
	 * @param int $index The item index to set
     *
     * @throws \Exception Thrown if the ListObject is Read Only
     *
	 * @return void
	 */
	public function offsetUnset($index)
	{
		$this->removeAt($index);
	}
	
	/**
	 * Serializes the data, and returns it.
	 * This method is required by the interface Serializable.
	 *
	 * @return string The serialized string
	 */
	public function serialize()
	{
		return serialize($this->data);
	}

    /**
     * Unserializes the data, and sets up the storage in this container
     * This method is required by the interface Serializable.
     *
     * @param string $data
     *
     * @return void
     */
    public function unserialize($data)
	{
        $this->data = unserialize($data);
    }
	
	/**
	 * Returns the ArrayIterator of this object
	 * This method is required by the interface IteratorAggregate.
	 *
	 * @return string The serialized string
	 */
	public function getIterator()
	{
        return new \ArrayIterator($this->data);
    }
}