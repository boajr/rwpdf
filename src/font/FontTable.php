<?php

namespace Boajr\PDF\font;

use ReflectionObject;
use ReflectionProperty;

abstract class FontTable
{
    private ?Font $src = null;
    private ?int $offset = null;
    private ?int $end_of_table = null;
    protected int $pos = 0;

    public function __construct(Font $font, int $offset, int $length)
    {
        $this->src = $font;
        $this->offset = $offset;
        $this->end_of_table = $length;
    }

    protected function seek(int $offset, int $whence = SEEK_SET): int
    {
        switch ($whence) {
            case SEEK_CUR:
                $this->pos += $offset;
                break;

            case SEEK_END:
                $this->pos = $this->end_of_table - $offset;
                break;

            default:
                $this->pos = $offset;
                break;
        }

        if ($this->pos < 0)
            $this->pos = 0;
        else if ($this->pos > $this->end_of_table)
            $this->pos = $this->end_of_table;

        return $this->src->seek($this->offset + $this->pos) - $this->offset;
    }

    public function read(int $length): string|false
    {
        if ($length <= 0)
            return false;

        if ($length > $this->end_of_table - $this->pos)
            $length = $this->end_of_table - $this->pos;

        $read = $this->src->read($length);
        if ($read !== false)
            $this->pos += strlen($read);
        return $read;
    }





    protected function parse()
    {
        $this->seek(0);
        foreach (new ReflectionObject($this)->getProperties() as $property) {
            if (!$this->getPropertyFromStream($property))
                break;
        }
    }

    protected function parseProperty(string $property)
    {
        $p = new ReflectionProperty($this, $property);
        if (!$p)
            throw new FontException('Property not found');
        $this->getPropertyFromStream($p);
    }

    private function getPropertyFromStream(ReflectionProperty $property): bool
    {
        $attributes = $property->getAttributes(FontPropertyType::class);
        if (count($attributes) > 0) {
            $attr = $attributes[0]->newInstance();

            print ' ' . $attr->getSize() . ' ' . $this->offset . ' ' . $this->end_of_table . ' ' . $this->pos . PHP_EOL;

            $read = $this->read($attr->getSize());
            if ($read === false)
                return false;
            $property->setValue($this, $attr->unpack($read));
        }
        return true;
    }
}
