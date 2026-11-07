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

    public function seek(int $offset, int $whence = SEEK_SET): int
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

        return $this->src->seek($this->offset + $this->pos);
    }

    public function pos(): int
    {
        return $this->pos;
    }

    public function skip(int $length): int
    {
        return $this->seek($length, SEEK_CUR);
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
            $read = $this->read($attr->getSize());
            if ($read === false)
                return false;
            $property->setValue($this, $attr->unpack($read));
        }
        return true;
    }

    //protected function readUFWORD(): int
    //{
    //    $buf = $this->read(2);
    //    if (strlen($buf) != 2)
    //        throw new FontException('Unable to read 2 bytes');
    //    return (ord($buf[0]) << 8) + ord($buf[1]);
    //}

    //protected function readFWORD(): int
    //{
    //    $buf = $this->read(2);
    //    if (strlen($buf) != 2)
    //        throw new FontException('Unable to read 2 bytes');
    //    $v = (ord($buf[0]) << 8) + ord($buf[1]);
    //    return $v >= 0x8000 ? $v - 0x10000 : $v;
    //}

    public function readUInt16(): int
    {
        return unpack("n", $this->read(2))[1];
    }

    public function readUInt16Many(int $count): array
    {
        return array_values(unpack("n*", $this->read($count * 2)));
    }

    public function readInt16Many(int $count): array
    {
        $vals = array_values(unpack("n*", $this->read($count * 2)));
        foreach ($vals as &$v) {
            if ($v >= 0x8000)
                $v -= 0x10000;
        }
        return $vals;
    }
}
