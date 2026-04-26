<?php

namespace Boajr\PDF\Objects;

use Boajr\PDF\Parser\PDFObjectDictionary;


/**
 * È un dictionary che non ha dei campi predefiniti, funziona come una sdtClass
 */
class GenericDictionary extends BaseDictionary
{
    private array $content_type;

    protected function setElementType(int|array $type, bool $inline): void
    {
        $this->content_type = Entry::normalizeEntryType($type, $inline);
    }

    public function setData(?PDFObjectDictionary $src): void
    {
        if (!$src) {
            return;
        }

        foreach ($src as $k => $v) {
            $this->__set($k, $v);
        }
    }

    public function __set(string $name, mixed $value): void
    {
        if (array_key_exists($name, $this->entryList)) {
            $this->entryList[$name]->setValue($value);
        } else {
            $e = new Entry($this->pdf, $this->content_type, false, 1000, 999999);
            try {
                $e->setValue($value, true);
            } catch (EntrySetValueException $ex) {
                throw new \Exception("Unable to set value for entry '$name' in " . $this->dictionaryName() . ': ' . $ex->getMessage());
            }
            $this->entryList[$name] = $e;
        }
    }

    public function getDictionaryKeys(): array
    {
        $a = [];
        foreach ($this->entryList as $k => $v)
            $a[] = $k;
        return $a;
    }
}
