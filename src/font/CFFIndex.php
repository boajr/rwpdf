<?php

namespace Boajr\PDF\font;


class CFFIndex
{
    private ?FontTable $src = null;
    private ?int $src_offset = null;
    private ?int $src_length = null;
    protected int $pos = 0;


    public int $count;
    private array $offset = [];

    public function __construct(FontTable $fontTable, int $offset)
    {
        $this->src = $fontTable;
        $this->src_offset = $offset;

        $this->src->seek($offset);

        $buf = $this->src->read(2);
        $this->count = (ord($buf[0]) << 8) + ord($buf[1]);

        if ($this->count) {
            $buf = $this->src->read(1);
            $offSize = ord($buf[0]);
            $buf = $this->src->read(($this->count + 1) * $offSize);

            $pos = 0;
            $base = 2 + ($this->count + 1) * $offSize;
            for ($i = 0; $i <= $this->count; ++$i) {
                $offset = 0;
                for ($j = 0; $j < $offSize; ++$j)
                    $offset = ($offset << 8) + ord($buf[$pos++]);

                $this->offset[] = $base + $offset;
            }

            $this->src_length = $this->offset[$this->count];
        } else
            $this->src_length = 2;
    }

    protected function seek(int $offset, int $whence = SEEK_SET): int
    {
        switch ($whence) {
            case SEEK_CUR:
                $this->pos += $offset;
                break;

            case SEEK_END:
                $this->pos = $this->src_length - $offset;
                break;

            default:
                $this->pos = $offset;
                break;
        }

        if ($this->pos < 0)
            $this->pos = 0;
        else if ($this->pos > $this->src_length)
            $this->pos = $this->src_length;

        return $this->src->seek($this->src_offset + $this->pos);
    }

    //public function pos(): int
    //{
    //    return $this->pos;
    //}

    //public function skip(int $length): int
    //{
    //    return $this->seek($length, SEEK_CUR);
    //}

    protected function read(int $length): string|false
    {
        if ($length <= 0)
            return false;

        if ($length > $this->src_length - $this->pos)
            $length = $this->src_length - $this->pos;

        $read = $this->src->read($length);
        if ($read !== false)
            $this->pos += strlen($read);
        return $read;
    }

    public function size(): int
    {
        return $this->src_length;
    }

    public function getRawData(int $idx): ?string
    {
        if ($idx < 0 || $idx > $this->count)
            return null;
        $this->seek($this->offset[$idx]);
        return $this->read($this->offset[$idx + 1] - $this->offset[$idx]);
    }
}
