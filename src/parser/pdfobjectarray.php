<?php

namespace Boajr\PDF\Parser;

use ArrayAccess;
use Countable;
use Iterator;


class PDFObjectArray implements IPDFObject, ArrayAccess, Countable, Iterator
{
    use LinkedObject;

    /**
     * @var array $array;
     */
    private $array;

    /**
     * @var int $position;
     */
    private $position;

    public function __construct()
    {
        $this->array = [];
        $this->position = 0;
    }

    public function GetType(): int
    {
        return self::TYPE_ARRAY;
    }

    public function GetFinalType(): int
    {
        return self::TYPE_ARRAY;
    }

    public function GetValue(): mixed
    {
        return $this;
    }

    public function GetFinalValue(): mixed
    {
        return $this;
    }

    public function GetReferencedObject(): IPDFObject
    {
        return $this;
    }

    public function offsetExists(mixed $offset): bool
    {
        if (!is_numeric($offset))
            throw new PDFParserException('PDFObjectArray does not use keys');

        return array_key_exists(intval($offset), $this->array);
    }

    public function offsetGet(mixed $offset): mixed
    {
        if (!is_numeric($offset))
            throw new PDFParserException('PDFObjectArray does not use keys');

        return $this->array[intval($offset)] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset !== null && !is_numeric($offset))
            throw new PDFParserException('PDFObjectArray does not use keys');

        if (!($value instanceof IPDFObject))
            throw new PDFParserException('PDFObjectArray can only contain IPDFObject elements');

        $count = count($this->array);
        if ($offset === null || $offset === -1 || $offset === $count)
            $this->array[] = $value;
        else {
            if ($offset < 0 || $offset >= $count) {
                throw new PDFParserException('Offset is out of range (to add a new element, push it)');
            }
            $this->array[intval($offset)] = $value;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        if (!is_numeric($offset))
            throw new PDFParserException('PDFObjectArray does not use keys');

        $offset = intval($offset);
        array_splice($this->array, $offset, 1);
        if ($this->position >= $offset)
            --$this->position;
    }

    public function current(): mixed
    {
        if ($this->position < 0)
            $this->position = 0;
        return $this->array[$this->position];
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
}
