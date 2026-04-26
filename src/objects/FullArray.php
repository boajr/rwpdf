<?php

namespace Boajr\PDF\Objects;

use ArrayAccess;
use Boajr\PDF\PDFException;
use Countable;
use Iterator;


class FullArray extends BaseArray implements ArrayAccess, Countable, Iterator
{
    private int $position = 0;

    public function offsetExists(mixed $offset): bool
    {
        if (!is_numeric($offset))
            throw new PDFException($this->arrayName() . ' does not use keys');

        return array_key_exists(intval($offset), $this->array);
    }

    public function offsetGet(mixed $offset): mixed
    {
        if (!is_numeric($offset))
            throw new PDFException($this->arrayName() . ' does not use keys');

        if (!array_key_exists(intval($offset), $this->array))
            return null;

        return $this->array[intval($offset)]->getValue();
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset !== null && !is_numeric($offset))
            throw new PDFException($this->arrayName() . ' does not use keys');

        $count = count($this->array);
        if ($offset === null || $offset === -1 || $offset === $count)
            $this->addElement($value);
        else {
            if ($offset < 0 || $offset >= $count)
                throw new PDFException('$offset is out of range (to add a new element, push it)');

            $this->array[intval($offset)]->setValue($value);
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        if (!is_numeric($offset))
            throw new PDFException($this->arrayName() . ' does not use keys');

        if ($offset < 0 || $offset >= count($this->array))
            throw new PDFException('$offset is out of range');

        $offset = intval($offset);
        array_splice($this->array, $offset, 1);
        if ($this->position >= $offset)
            --$this->position;
    }

    public function current(): mixed
    {
        if ($this->position < 0)
            $this->position = 0;
        return $this->position < count($this->array) ? $this->array[$this->position]->getValue() : null;
    }

    public function key(): mixed
    {
        if ($this->position < 0)
            $this->position = 0;
        return $this->position;
    }

    public function next(): void
    {
        if ($this->position < 0)
            $this->position = 0;
        else
            ++$this->position;
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function valid(): bool
    {
        if ($this->position < 0)
            $this->position = 0;
        return array_key_exists($this->position, $this->array);
    }

    public function count(): int
    {
        return count($this->array);
    }

    // alcune funzioni che possono servire
    public function insert(int $pos, mixed $elem): void
    {
        $e = new Entry($this->pdf, $this->content_type, false, 1000, 999999);
        try {
            $e->setValue($elem);
        } catch (EntrySetValueException $ex) {
            throw new PDFException("Unable to set value in " . $this->arrayName() . ': ' . $ex->getMessage());
        }
        array_splice($this->array, $pos, 0, [$e]);
    }
}
