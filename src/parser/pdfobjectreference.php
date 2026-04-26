<?php

namespace Boajr\PDF\Parser;


class PDFObjectReference implements IPDFObject
{
    use LinkedObject;

    /**
     * @var DataStream $source;
     */
    public $source;

    /**
     * @var int|float $obj_num;
     */
    public $obj_num;

    /**
     * @var int|float $gen_num;
     */
    public $gen_num;

    /**
     * @var IPDFObject $referenced
     */
    private $referenced = null;

    public function __construct(DataStream $source, int $objNum, int $genNum)
    {
        $this->source = $source;
        $this->obj_num = $objNum;
        $this->gen_num = $genNum;
    }

    private function risolvi_referenza(): IPDFObject
    {
        // se ho già fatto la ricerca restituisco l'oggetto
        if ($this->referenced !== null) {
            return $this->referenced;
        }

        // devo implementare una ricerca ricorsiva, verificando che non passi due volte per lo stesso oggetto
        $nums = [];
        $obj_num = $this->obj_num;
        $gen_num = $this->gen_num;
        while (1) {
            /** @var IPDFObject $obj */
            $obj = $this->source->ReadObjectByXRef($obj_num, $gen_num);
            if ($obj->GetType() !== self::TYPE_REFERENCE) {
                $this->referenced = $obj;
                return $obj;
            }

            $nums[] = $obj_num;

            $val = $obj->GetValue();
            $obj_num = $val['obj'];
            $gen_num = $val['gen'];

            if (in_array($obj_num, $nums)) {
                throw new PDFParserException('Invalid PDF: cyclic redundancy in xref');
            }
        }
    }

    public function GetType(): int
    {
        return self::TYPE_REFERENCE;
    }

    public function GetFinalType(): int
    {
        return $this->risolvi_referenza()->GetType();
    }

    public function GetValue(): mixed
    {
        return [
            'obj' => $this->obj_num,
            'gen' => $this->gen_num
        ];
    }

    public function GetFinalValue(): mixed
    {
        return $this->risolvi_referenza()->GetValue();
    }

    public function GetReferencedObject(): IPDFObject
    {
        return $this->risolvi_referenza();
    }
}
