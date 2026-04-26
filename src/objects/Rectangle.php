<?php

namespace Boajr\PDF\Objects;

use Boajr\PDF\Parser\PDFObjectArray;
use Boajr\PDF\PDF;
use Boajr\PDF\PDFException;


/**
 * Classe che rappresenta un rectangle come specificato a pagina 119 del file ISO_32000-2-2020_sponsored.pdf
 */
class Rectangle extends BaseArray
{
    public function __construct(PDF $pdf, PDFObjectArray|float $src_or_left, float $top = 0, float $right = 0, float $bottom = 0)
    {
        $this->pdf = $pdf;

        $this->setElementType(Entry::NUMBER, true);

        if ($src_or_left instanceof PDFObjectArray) {
            if (count($src_or_left) != 4) {
                throw new PDFException('Invalid rectangle format.');
            }

            $src_or_left->SetLinkedObject($this);
            foreach ($src_or_left as $elem) {
                $this->addElement($elem);
            }
        } else {
            $this->addElement($src_or_left);
            $this->addElement($top);
            $this->addElement($right);
            $this->addElement($bottom);
        }
    }

    private function getIndex(string $name)
    {
        if ($name == 'left') {
            return 0;
        }

        if ($name == 'top') {
            return 1;
        }

        if ($name == 'right') {
            return 2;
        }

        if ($name == 'bottom') {
            return 3;
        }

        throw new PDFException('Undefined property Boajr\PDF\PDF::$' . $name);
    }

    public function __get(string $name): mixed
    {
        return $this->array[$this->getIndex($name)]->getValue();
    }

    public function __set(string $name, mixed $value): void
    {
        $this->array[$this->getIndex($name)]->setValue(floatval($value));
    }

    public function toJson(): string
    {
        $ret = [];
        foreach ($this->array as $elem)
            $ret[] = round($elem->GetValue(), Entry::$float_decimals);
        return json_encode($ret);
    }
}
