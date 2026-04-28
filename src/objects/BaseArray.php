<?php

namespace Boajr\PDF\Objects;

use Boajr\PDF\PDF;
use Boajr\PDF\PDFException;

abstract class BaseArray implements IBaseObject
{
    protected PDF $pdf;

    private int $objNumber = 0;
    private int $objOffset = 0;

    protected array $content_type;

    protected array $array = [];

    protected function arrayName(): string
    {
        $pos = strrpos(static::class, '\\');
        return $pos === false ? static::class : substr(static::class, $pos + 1);
    }

    public function appendObject(array &$objs, bool $inline): int
    {
        $ver = 1000;
        if ($inline || !in_array($this, $objs)) {
            if (!$inline) {
                $objs[] = $this;
                $this->objNumber = count($objs);
            }

            foreach ($this->array as $entry) {
                $v = $entry->appendObject($objs);
                if ($ver < $v) {
                    $ver = $v;
                }
            }
        }
        return $ver;
    }

    public function getInlineObject(int $ver): string
    {
        $out = '[';
        foreach ($this->array as $elem) {
            if ($elem->startWithSeparator() || strpos(" \t\r\n\f()<>[]/%", $out[strlen($out) - 1]) !== false) {
                $out .= $elem->getOutput($ver);
                continue;
            }
            $out .= ' ' . $elem->getOutput($ver);
        }
        return $out . ']';
    }

    public function getPDFObject(int $ver, int $offset, int $objNumber): string
    {
        $this->objOffset = $offset;
        return $this->objNumber . " 0 obj\r\n" . $this->getInlineObject($ver) . "\r\nendobj\r\n";
    }

    public function getObjectReference(): string
    {
        return $this->objNumber . ' 0 R';
    }

    public function getObjectOffset(int $objNumber): int
    {
        return $this->objOffset;
    }

    protected function setElementType(int|array $type, bool|array $inline): void
    {
        $this->content_type = Entry::normalizeEntryType($type, $inline);
    }

    protected function addElement(mixed $elem): void
    {
        $e = new Entry($this->pdf, $this->content_type, false, 1000, 999999);
        try {
            $e->setValue($elem);
        } catch (EntrySetValueException $ex) {
            throw new PDFException("Unable to set value in " . $this->arrayName() . ': ' . $ex->getMessage());
        }
        $this->array[] = $e;
    }

    public function __clone(): void
    {
        $src = $this->array;
        $this->array = [];
        foreach ($src as $elem) {
            $this->addElement($elem->GetValue());
        }
    }

    //public function __serialize(): array
    //{
    //    $ret = [];
    //    foreach ($this->array as $elem) {
    //        $ret[] = $elem->GetValue();
    //    }
    //    return $ret;
    //}

    //public function __unserialize(array $data): void
    //{
    //    $this->array = [];
    //    foreach ($data as $val) {
    //        $this->addElement($val);
    //    }
    //}
}
