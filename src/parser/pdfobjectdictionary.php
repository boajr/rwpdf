<?php

namespace Boajr\PDF\Parser;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;


class PDFObjectDictionary implements IPDFObject, ArrayAccess, Countable, IteratorAggregate
{
    use LinkedObject;

    /**
     * @var array $array;
     */
    private $array;

    /**
     * @var ?DataStream $stream_source
     */
    private $stream_source;

    /**
     * @var int $stream_offset;
     */
    private $stream_offset;

    public function __construct()
    {
        $this->array = [];
        $this->stream_source = null;
        $this->stream_offset = 0;
    }

    public function GetType(): int
    {
        return $this->stream_source ? self::TYPE_STREAM : self::TYPE_DICTIONARY;
    }

    public function GetFinalType(): int
    {
        return $this->stream_source ? self::TYPE_STREAM : self::TYPE_DICTIONARY;
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

    public function AddStream(DataStream $source, int $offset): void
    {
        $this->stream_source = $source;
        $this->stream_offset = $offset;
    }

    public function GetStream(bool $decode): string
    {
        if ($this->stream_source === null)
            throw new PDFParserException('object type isn\'t TYPE_STREAM');

        // legge il blocco di dati
        $str = $this->stream_source->ReadData($this->stream_offset, $this['Length']->GetFinalValue());

        if ($decode) {
            $filterObj = $this['Filter'];
            if ($filterObj) {
                $decodeParmsObj = $this['DecodeParms'];

                switch ($filterObj->GetFinalType()) {
                    case self::TYPE_ARRAY:
                        $filters = $filterObj->GetFinalValue();
                        $decodeParms = null;
                        $len = count($filters);

                        if ($decodeParmsObj !== null) {
                            if ($decodeParmsObj->GetFinalType() !== self::TYPE_ARRAY) {
                                throw new PDFParserException('Stream DecodeParms field isn\'t TYPE_ARRAY');
                            }

                            $decodeParms = $decodeParmsObj->GetFinalValue();
                            if (count($decodeParms) !== $len) {
                                throw new PDFParserException('Lengths of arrays Filter and DecodeParms aren\'t the same');
                            }
                        }

                        for ($i = 0; $i < $len; ++$i) {
                            $str = $this->applica_filtro($str, $filters[$i], $decodeParms ? $decodeParms[$i] : null);
                        }
                        return $str;

                    case self::TYPE_NAME:
                        return $this->applica_filtro($str, $filterObj, $decodeParmsObj);
                }

                throw new PDFParserException('Invalid stream Filter field type');
            }
        }

        return $str;
    }

    // gestisce la lettura dei sotto oggetti se è uno stream object
    public function IsStreamObject(): bool
    {
        return array_key_exists('Type', $this->array) && $this->array['Type']->GetFinalType() === IPDFObject::TYPE_NAME && $this->array['Type']->GetFinalValue() === 'ObjStm';
    }

    /**
     * @var ?array $subObjTable
     */
    private $subObjTable = null;

    /**
     * @var ?DataStream $subObjStream
     */
    private $subObjStream = null;

    public function ReadObject(int $stmPos, int $objNum): ?IPDFObject
    {
        // se non ho ancora lo stream lo creo
        if (!$this->subObjStream) {
            if (!$this->IsStreamObject() || $this->stream_source === null)
                throw new PDFParserException('object type isn\'t TYPE_STREAMOBJECT');

            $n = array_key_exists('N', $this->array) ? intval($this->array['N']->GetFinalValue()) : 0;
            $first = array_key_exists('First', $this->array) ? intval($this->array['First']->GetFinalValue()) : 0;
            if (!$n || !$first)
                throw new PDFParserException('Invalid stream object collection');

            if (array_key_exists('Extends', $this->array))
                throw new \Exception('TODO: decodifica degli oggetti di tipo 2 con estensione ancora da implementare');

            $stm = $this->GetStream(true);
            $len = $stm ? strlen($stm) : 0;
            if (!$len)
                throw new PDFParserException('Stream object collection can\'t be empty');
            if ($len < $first)
                throw new PDFParserException('Invalid stream object collection');

            $this->subObjStream = new DataStream($stm, $len, $this->stream_source);

            $t = [];
            $i = 0;
            while ($i < $first) {
                // cerco il primo carattere che non sia un separatore
                $i += strspn($stm, " \t\r\n\f", $i, $first - $i);
                if ($i >= $first)
                    break;

                // salvo la posizione iniziale e mi sposto al carattere successivo
                $pos = $i++;

                // cerco l'ultimo carattere che non sia un separatore
                $i += strcspn($stm, " \t\r\n\f", $i, $first - $i);

                $t[] = intval(substr($stm, $pos, ($i++) - $pos));
            }

            $l = count($t);
            if ($l % 2 || $l / 2 != $n)
                throw new PDFParserException('Invalid stream object collection');
            $this->subObjTable = [];
            for ($i = 0; $i < $l; $i += 2) {
                $this->subObjTable[] = [
                    'objNum' => $t[$i],
                    'offset' => $first + $t[$i + 1]
                ];
            }
        }

        if ($stmPos >= count($this->subObjTable))
            throw new PDFParserException('Object index outside collection boundaries');

        if ($this->subObjTable[$stmPos]['objNum'] !== $objNum)
            throw new PDFParserException('Object number requested differs from object number in collection');

        return $this->subObjStream->prossimo_oggetto($this->subObjTable[$stmPos]['offset']);
    }

    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists($offset, $this->array);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->offsetExists($offset) ? $this->array[$offset] : null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null)
            throw new PDFParserException('PDFObjectDictionary needs a key');

        if (!($value instanceof IPDFObject))
            throw new PDFParserException('PDFObjectDictionary can only contain IPDFObject elements');

        $this->array[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        if (array_key_exists($offset, $this->array)) {
            unset($this->array[$offset]);
        }
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->array);
    }

    public function count(): int
    {
        return count($this->array);
    }

    private function getIntegerOrDefault(IPDFObject $obj, int $def): int
    {
        if ($obj && $obj->GetFinalType() === self::TYPE_INT) {
            return $obj->GetFinalValue();
        }
        return $def;
    }

    private function applica_filtro(string $str, PDFObjectName $filtro, ?IPDFObject $decodeParms): string
    {
        switch ($filtro->GetFinalValue()) {
            //case 'ASCIIHexDecode':
            //    return $str;

            //case 'ASCII85Decode':
            //    return $str;

            //case 'LZWDecode':
            //    return $str;

            case 'FlateDecode':
                $predictor = 1;
                $colors = 1;
                $bitsPerComponent = 8;
                $columns = 1;
                if ($decodeParms !== null && $decodeParms->GetFinalType() === self::TYPE_DICTIONARY) {
                    $dp = $decodeParms->GetFinalValue();
                    $predictor = $this->getIntegerOrDefault($dp['Predictor'], $predictor);
                    $colors = $this->getIntegerOrDefault($dp['Colors'], $colors);
                    $bitsPerComponent = $this->getIntegerOrDefault($dp['BitsPerComponent'], $bitsPerComponent);
                    $columns = $this->getIntegerOrDefault($dp['Columns'], $columns);
                }
                return $this->filtroFlatDecode($str, $predictor, $colors, $bitsPerComponent, $columns);

                //case 'RunLengthDecode':
                //    return $str;

                //case 'CCITTFaxDecode':
                //    return $str;

                //case 'JBIG2Decode':
                //    return $str;

                //case 'DCTDecode':
                //    print 'DCTDecode' . PHP_EOL;
                //    return $str;

                //case 'JPXDecode':
                //    return $str;

                //case 'Crypt':
                //    return $str;
        }

        throw new PDFParserException('Unknown filter (' . $filtro->GetFinalValue() . ')');
    }

    private function filtroFlatDecode(string $str, int $predictor, int $colors, int $bitsPerComponent, int $columns): string
    {
        if (!$str)
            return '';

        // decomprimo lo stream
        $str = zlib_decode($str);
        if ($str === false) {
            throw new PDFParserException('Unable to decompress the stream');
        }

        if (!$str || $predictor === 1)
            return $str;

        if ($predictor === 2) {
            throw new PDFParserException('TIFF Predictor 2 ancora da implementare');
        }

        if ($predictor >= 10 && $predictor <= 15) {
            return PNGPrediction::decode($str, 1 + intval((7 + $colors * $bitsPerComponent * $columns) / 8), $bitsPerComponent);
        }

        throw new PDFParserException('Unknown stream Predictor (' . $predictor . ')');
    }
}
