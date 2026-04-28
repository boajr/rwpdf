<?php

namespace Boajr\PDF\Objects;

use Boajr\PDF\Parser\IPDFObject;
use Boajr\PDF\Parser\PDFObjectArray;
use Boajr\PDF\Parser\PDFObjectDictionary;
use Boajr\PDF\PDF;
use Boajr\PDF\PDFException;


class BaseDictionary implements IBaseObject
{
    public static bool $NoUnexpectedEntryError = true;

    protected PDF $pdf;

    private int $objNumber = 0;
    private int $objOffset = 0;
    protected array $entryList = [];

    
    protected function dictionaryName(): string
    {
        $pos = strrpos(static::class, '\\');
        return $pos === false ? static::class : substr(static::class, $pos + 1);
    }

    public function isInLine(): ?bool
    {
        return null;
    }

    public function appendObject(array &$objs, bool $inline): int
    {
        $ver = 1000;
        if ($inline || !in_array($this, $objs)) {
            if (!$inline) {
                $objs[] = $this;
                $this->objNumber = count($objs);
            }

            foreach ($this->entryList as $entry) {
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
        $out = '<<';
        foreach ($this->entryList as $key => $entry) {
            if (!$entry->isOutputable($ver)) {
                continue;
            }

            $out .= '/' . $key;
            if ($entry->startWithSeparator()/* || strpos(" \t\r\n\f()<>[]/%", $out[strlen($out) - 1]) !== false*/) {
                $out .= $entry->getOutput($ver);
                continue;
            }
            $out .= ' ' . $entry->getOutput($ver);
        }
        return $out . '>>';
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

    protected function addEntry(string $key, int|array $type, bool $required, bool|array $inline, int $minVer, int $maxVer = 999999): void
    {
        if (array_key_exists($key, $this->entryList)) {
            return;
        }

        $this->entryList[$key] = new Entry($this->pdf, Entry::normalizeEntryType($type, $inline), $required, $minVer, $maxVer);
    }

    public function setData(?PDFObjectDictionary $src): void
    {
        if (!$src) {
            return;
        }

        foreach ($src as $k => $v) {
            if (!isset($this->entryList[$k])) {
                if (static::$NoUnexpectedEntryError) {
                    print "Unexpected entry '$k' in " . $this->dictionaryName() . '.' . PHP_EOL;
                    continue;
                }
                throw new PDFException("Unexpected entry '$k' in " . $this->dictionaryName() . '.');
            }

            try {
                $this->entryList[$k]->setValue($v);
            } catch (EntrySetValueException $ex) {
                throw new PDFException("Unable to set value for entry '$k' in " . $this->dictionaryName() . ': ' . $ex->getMessage());
            }
        }
    }

    public function __get(string $name): mixed
    {
        if (array_key_exists($name, $this->entryList)) {
            return $this->entryList[$name]->getValue();
        }
        return null;
    }

    public function __set(string $name, mixed $value): void
    {
        if (array_key_exists($name, $this->entryList)) {
            $this->entryList[$name]->setValue($value);
        }
    }

    public function __isset(string $name): bool
    {
        if (array_key_exists($name, $this->entryList)) {
            return $this->entryList[$name]->hasValue() && $this->entryList[$name]->getValue() !== null;
        }
        return false;
    }

    public function __unset(string $name): void
    {
        if (array_key_exists($name, $this->entryList)) {
            $this->entryList[$name]->delValue();
        }
    }


    
    public function getArray(PDFObjectDictionary $src, string $name): ?PDFObjectArray
    {
        if (!$src || !$src[$name])
            return null;
        $val = $src[$name]->GetReferencedObject();
        $type = $val->GetFinalType();
        if ($type === IPDFObject::TYPE_NULL)
            return null;

        if ($type === IPDFObject::TYPE_ARRAY)
            return $val->GetFinalValue();

        throw new PDFException("Unable to set value for entry '$name' in " . $this->dictionaryName() . ': wrong type.');
    }

    public function getInteger(PDFObjectDictionary $src, string $name): ?int
    {
        if (!$src || !$src[$name])
            return null;
        $val = $src[$name]->GetReferencedObject();
        $type = $val->GetFinalType();
        if ($type === IPDFObject::TYPE_NULL)
            return null;

        if ($type === IPDFObject::TYPE_INT)
            return $val->GetFinalValue();

        throw new PDFException("Unable to set value for entry '$name' in " . $this->dictionaryName() . ': wrong type.');
    }

    public function getRectangle(PDFObjectDictionary $src, string $name): ?Rectangle
    {
        if (!$src || !$src[$name])
            return null;
        $val = $src[$name]->GetReferencedObject();
        $type = $val->GetFinalType();
        if ($type === IPDFObject::TYPE_NULL)
            return null;

        if ($type === IPDFObject::TYPE_ARRAY)
            return new Rectangle($this->pdf, $val->GetFinalValue());

        throw new PDFException("Unable to set value for entry '$name' in " . $this->dictionaryName() . ': wrong type.');
    }

    public function getResources(PDFObjectDictionary $src, string $name): ?ResourcesDictionary
    {
        if (!$src || !$src[$name])
            return null;
        $val = $src[$name]->GetReferencedObject();
        $type = $val->GetFinalType();
        if ($type === IPDFObject::TYPE_NULL)
            return null;

        if ($type === IPDFObject::TYPE_DICTIONARY)
            return new ResourcesDictionary($this->pdf, $val->GetFinalValue());

        throw new PDFException("Unable to set value for entry '$name' in " . $this->dictionaryName() . ': wrong type.');
    }
}
