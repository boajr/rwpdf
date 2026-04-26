<?php

namespace Boajr\PDF\Objects;

use Boajr\PDF\Parser\IPDFObject;
use Boajr\PDF\PDF;
use Boajr\PDF\PDFException;
use DateTimeInterface;


class Entry
{
    public const EMPTY = 0;
    public const ARRAY = 1;
    public const BYTE_STRING = 12;
    public const BOOLEAN = 2;
    public const DATE = 3;
    public const DICTIONARY = 4;
    public const INTEGER = 5;
    public const NAME = 6;
    public const NULL = 7;
    public const NUMBER = 8;
    public const RECTANGLE = 9;
    public const STREAM = 10;
    public const TEXT_STRING = 11;

    /**
     * @var int $float_decimals - Max number of decimal digits to output
     */
    public static $float_decimals = 3;

    public static function normalizeEntryType(int|array $type, bool|array $inline): array
    {
        $nt = [];
        foreach ((is_array($type) ? $type : [$type]) as $k => $row) {
            if (is_numeric($row)) {
                $k = $row;
                $row = null;
            }

            if ($k === self::ARRAY) {
                if (!$row) {
                    throw new PDFException('Specify array subclass.');
                }
                $nt[$k] = [$row => is_array($inline) ? ($inline[$k] ?? true) : $inline];
            } else if ($k === self::DICTIONARY) {
                $nt[$k] = [];
                foreach ((is_array($row) ? $row : [$row]) as $sub) {
                    if (!$sub) {
                        throw new PDFException('Specify dictionary subclass.');
                    }

                    if (!is_array($inline)) {
                        // se $inline è un boolean, ogni struttura dipende dal suo valore
                        $il = $inline;
                    } else if (!array_key_exists($k, $inline)) {
                        // se non esiste una chiave per il tipo di struttura la definisco inline
                        $il = true;
                    } else if (!is_array($inline[$k])) {
                        // se il tipo di chiave è un boolean, ogni sottostruttura dipende dal suo valore
                        $il = $inline[$k];
                    } else {
                        // il valore è quello specificato o true se non c'è la sottostruttura
                        $il = $inline[$k][$sub] ?? true;
                    }
                    $nt[$k][$sub] = $il;
                }
            } else if ($k === self::STREAM) {
                $nt[$k] = [($row ? $row : 'Stream') => false];
            } else {
                // se per un campo che non è una struttura non viene specificato il valore per l'inline, lo setto true
                $nt[$k] = is_array($inline) ? ($inline[$k] ?? true) : $inline;
            }
        }
        return $nt;
    }

    private PDF $pdf;

    private $type;
    private $required;
    private $minVer;
    private $maxVer;

    private $value_type = 0;
    private $value_inline = true;
    private $value = null;

    public function __construct(PDF $pdf, array $type, bool $required, int $minVer, int $maxVer)
    {
        $this->pdf = $pdf;
        $this->type = $type;
        $this->required = $required;
        $this->minVer = $minVer;
        $this->maxVer = $maxVer;
    }

    private function isInline()
    {
        // chiedo all'oggetto se è inline
        $ret = $this->value_type == self::DICTIONARY ? $this->value->isInLine() : null;
        return $ret === null ? $this->value_inline : $ret;
    }

    public function setValue(mixed $val): void
    {
        $this->value_type = self::NULL;
        $this->value_inline = true;
        $this->value = null;

        // se il valore passato è null, l'entry sarà un oggetto null
        if (is_null($val)) {
            return;
        }

        if ($val instanceof IPDFObject) {
        $val = $val->GetReferencedObject();
            if (!$val->HasLinkedObject()) {
                $type = $val->GetFinalType();
                if ($type === IPDFObject::TYPE_NULL) {
                    return;
                }

                foreach ($this->type as $t => $sub) {
                    switch ($t) {
                        case self::ARRAY:
                            if ($type === IPDFObject::TYPE_ARRAY) {
                                $key = array_key_first($sub);
                                $this->value_type = self::ARRAY;
                                $this->value_inline = $sub[$key];
                                $this->value = new $key($this->pdf, $val->GetFinalValue());
                                return;
                            }
                            break;

                        case self::BOOLEAN:
                            if ($type === IPDFObject::TYPE_BOOL) {
                                $this->value_type = self::BOOLEAN;
                                $this->value_inline = $sub;
                                $this->value = $val->GetFinalValue();
                                return;
                            }
                            break;

                        case self::BYTE_STRING:
                            if ($type === IPDFObject::TYPE_STRING) {
                                $this->value_type = self::BYTE_STRING;
                                $this->value_inline = $sub;
                                $this->value = $val->GetFinalValue();
                                return;
                            }
                            break;

                        case self::DATE:
                            if ($type === IPDFObject::TYPE_STRING) {
                                $str = $val->GetFinalValue();
                                if ($str && preg_match("/D:([0-9]{4,14})([+-][0-9]{2}'[0-9]{2}){0,1}/", $str, $match)) {
                                    $len = strlen($match[1]);
                                    while ($len < 14) {
                                        $match[1] .= ($len == 5 || $len == 7) ? '1' : '0';
                                        ++$len;
                                    }

                                    $this->value_type = self::DATE;
                                    $this->value_inline = $sub;
                                    $this->value = \DateTimeImmutable::createFromFormat(
                                        'YmdHis.up',
                                        $match[1] . '.000000' . (isset($match[2]) ? substr($match[2], 0, 3) . ':' . substr($match[2], 4) : 'Z')
                                    );
                                    return;
                                }
                                //throw new EntrySetValueException('wrong pdf date format.');
                            }
                            break;

                        case self::DICTIONARY:
                            if ($type === IPDFObject::TYPE_DICTIONARY) {
                                // se ho più di un possibile dizionario cerco quello da caricare basandomi
                                // sul campo type
                                $fin = $val->GetFinalValue();
                                $type = $fin['Type'];
                                if ($type) {
                                    $type = $type->GetFinalType() == IPDFObject::TYPE_NAME ? $type->GetFinalValue() . 'Dictionary' : null;
                                }

                                // se la struttura non ha un tipo, provo a caricare la prima dell'elenco
                                if (!$type) {
                                    $key = array_key_first($sub);
                                    $this->value_type = self::DICTIONARY;
                                    $this->value_inline = $sub[$key];
                                    $this->value = new $key($this->pdf, $fin);
                                    return;
                                }

                                foreach ($sub as $key => $il) {
                                    $pos = strrpos($key, '\\');
                                    if ($type === ($pos === false ? $key : substr($key, $pos + 1))) {
                                        $this->value_type = self::DICTIONARY;
                                        $this->value_inline = $il;
                                        $this->value = new $key($this->pdf, $fin);
                                        return;
                                    }
                                }
                                throw new EntrySetValueException('wrong dictionary type.');
                            }
                            break;

                        case self::INTEGER:
                            if ($type === IPDFObject::TYPE_INT) {
                                $this->value_type = self::INTEGER;
                                $this->value_inline = $sub;
                                $this->value = $val->GetFinalValue();
                                return;
                            }
                            break;

                        case self::NAME:
                            if ($type === IPDFObject::TYPE_NAME) {
                                $this->value_type = self::NAME;
                                $this->value_inline = $sub;
                                $this->value = $val->GetFinalValue();
                                return;
                            }
                            break;

                        case self::NUMBER:
                            if ($type === IPDFObject::TYPE_FLOAT || $type === IPDFObject::TYPE_INT) {
                                $this->value_type = self::NUMBER;
                                $this->value_inline = $sub;
                                $this->value = $val->GetFinalValue();
                                return;
                            }
                            break;

                        case self::RECTANGLE:
                            if ($type === IPDFObject::TYPE_ARRAY) {
                                $this->value_type = self::RECTANGLE;
                                $this->value_inline = $sub;
                                $this->value = new Rectangle($this->pdf, $val->GetFinalValue());
                                return;
                            }
                            break;

                        case self::STREAM:
                            if ($type === IPDFObject::TYPE_STREAM) {
                                $key = array_key_first($sub);
                                $this->value_type = self::STREAM;
                                $this->value_inline = $sub[$key];
                                $this->value = new $key($this->pdf, $val->GetFinalValue());
                                return;
                            }
                            break;

                        case self::TEXT_STRING:
                            if ($type === IPDFObject::TYPE_STRING) {
                                $this->value_type = self::TEXT_STRING;
                                $this->value_inline = $sub;
                                $this->value = $val->GetFinalValue();
                                return;
                            }
                            break;

                        default:
                            throw new EntrySetValueException('unknown value type.');
                    }
                }

                throw new EntrySetValueException('wrong type.');
            } else {
                $val = $val->GetLinkedObject();
                foreach ($this->type as $t => $sub) {
                    switch ($t) {
                        case self::ARRAY:
                            $key = array_key_first($sub);
                            if (get_class($val) === $key) {
                                $this->value_type = self::ARRAY;
                                $this->value_inline = $sub[$key];
                                $this->value = $val;
                                return;
                            }
                            break;

                        case self::DICTIONARY:
                            foreach ($sub as $key => $il) {
                                if (get_class($val) === $key) {
                                    $this->value_type = self::DICTIONARY;
                                    $this->value_inline = $il;
                                    $this->value = $val;
                                    return;
                                }
                            }
                            break;

                        case self::STREAM:
                            $key = array_key_first($sub);
                            if (get_class($val) === $key) {
                                $this->value_type = self::STREAM;
                                $this->value_inline = $sub[$key];
                                $this->value = $val;
                                return;
                            }
                            break;
                    }
                }

                throw new EntrySetValueException('wrong type. (Oggetto già linkato)');
            }
        }

        foreach ($this->type as $t => $sub) {
        switch ($t) {
                case self::ARRAY:
                    if (is_array($val)) {
                        throw new EntrySetValueException('TODO: Array ancora da implementare!!!.');
                    } else {
                        $key = array_key_first($sub);
                        if (get_class($val) === $key) {
                            $this->value_type = self::ARRAY;
                            $this->value_inline = $sub[$key];
                            $this->value = $val;
                            return;
                        }
                    }
                    break;

                case self::BOOLEAN:
                    //print 'Valore da convertire in boolean: ' . print_r($val, true) . PHP_EOL;
                    $this->value_type = self::BOOLEAN;
                    $this->value_inline = $sub;
                    $this->value = boolval($val);
                    return;

                case self::BYTE_STRING:
                    //print 'Valore da convertire in string: ' . print_r($val, true) . PHP_EOL;
                    $this->value_type = self::BYTE_STRING;
                    $this->value_inline = $sub;
                    $this->value = (string)$val;
                    return;

                case self::DATE:
                    if ($val instanceof DateTimeInterface) {
                        $this->value_type = self::DATE;
                        $this->value_inline = $sub;
                        $this->value = $val;
                        return;
                    }
                    break;

                case self::DICTIONARY:
                    if ($sub) {
                        $cnVal = get_class($val);
                        foreach ($sub as $key => $il) {
                            if ($cnVal == $key) {
                                $this->value_type = self::DICTIONARY;
                                $this->value_inline = $il;
                                $this->value = $val;
                                return;
                            }
                        }
                    }
                    break;

                case self::INTEGER:
                    //print 'Valore da convertire in integer: ' . print_r($val, true) . PHP_EOL;
                    $this->value_type = self::INTEGER;
                    $this->value_inline = $sub;
                    $this->value = intval($val);
                    return;

                case self::NAME:
                    //print 'Valore da convertire in name: ' . print_r($val, true) . PHP_EOL;
                    $this->value_type = self::NAME;
                    $this->value_inline = $sub;
                    $this->value = (string)$val;
                    return;

                case self::NUMBER:
                    //print 'Valore da convertire in number: ' . print_r($val, true) . PHP_EOL;
                    $this->value_type = self::NUMBER;
                    $this->value_inline = $sub;
                    $this->value = floatval($val);
                    return;

                case self::RECTANGLE:
                    if ($val instanceof Rectangle) {
                        $this->value_type = self::RECTANGLE;
                        $this->value_inline = $sub;
                        $this->value = $val;
                        return;
                    }
                    break;

                case self::STREAM:
                    if ($val instanceof Stream) {
                        $this->value_type = self::STREAM;
                        $this->value_inline = false;
                        $this->value = $val;
                        return;
                    }
                    break;

                case self::TEXT_STRING:
                    //print 'Valore da convertire in string: ' . print_r($val, true) . PHP_EOL;
                    $this->value_type = self::TEXT_STRING;
                    $this->value_inline = $sub;
                    $this->value = (string)$val;
                    return;

                default:
                    throw new EntrySetValueException('unknown value type.' . $t);
            }
        }

        throw new EntrySetValueException('wrong type.');
    }

    public function delValue(): void
    {
        $this->value_type = self::EMPTY;
        $this->value_inline = true;
        $this->value = null;
    }

    public function hasValue(): bool
    {
        return $this->value_type != self::EMPTY;
    }

    public function getValue(): mixed
    {
        return $this->value_type == self::EMPTY ? null : $this->value;
    }

    public function appendObject(array &$objs): int
    {
        // solo gli oggetti che implementano la IBaseObject possono essere referenziati inoltre questi oggetti possono
        // contenere riferimenti ad altri oggetti simili, quindi devo processarli ricorsivamente
        if ($this->value_type == self::EMPTY) {
            return 1000;
        }

        $ver = $this->minVer;
        if ($this->value instanceof IBaseObject) {
            $v = $this->value->appendObject($objs, $this->isInline());
            if ($ver < $v) {
                $ver = $v;
            }
        }
        return $ver;
    }

    public function isOutputable(int $ver): bool
    {
        if ($ver < $this->minVer || $ver >= $this->maxVer) {
            return false;
        }
        return $this->value_type != self::EMPTY || $this->required;
    }

    public function startWithSeparator(): bool
    {
        if ($this->value_type == self::EMPTY || !$this->isInline()) {
            return false;
        }

        return in_array($this->value_type, [
            self::ARRAY,
            self::BYTE_STRING,
            self::DICTIONARY,
            self::DATE,
            self::NAME,
            self::RECTANGLE,
            self::STREAM,
            self::TEXT_STRING,
        ]);

        // restano esclusi
        //   self:BOOL
        //   self:INTEGER
        //   self:NUMBER
    }

    public function getOutput(int $ver): string
    {
        if ($this->value_type == self::EMPTY) {
            return 'null';
        }

        if (!$this->isInline()) {
            if ($this->value instanceof IBaseObject) {
                return $this->value->getObjectReference();
            }
            return 'null';
        }

        switch ($this->value_type) {
            case self::ARRAY:
                /**
                 * @disregard P1006 nel caso di ARRAY $this->value è sempre un oggetto figlio di BaseArray
                 */
                return $this->value->getInlineObject($ver);

            case self::BOOLEAN:
                return $this->value ? "true" : "false";

            case self::BYTE_STRING:
                return static::scriviTextString($this->value, true);

            case self::DATE:
                /**
                 * @disregard P1006 nel case di DATE $this->value è sempre un oggetto che implementa la DateTimeInterface
                 */
                return static::scriviTextString('D:' . $this->value->format('YmdHis') . str_replace(':', '\'', $this->value->format('p')), false, true);

            case self::DICTIONARY:
                /**
                 * @disregard P1006 nel caso di DICTIONARY $this->value è sempre un oggetto figlio di BaseDictionary
                 */
                return $this->value->getInlineObject($ver);

            case self::INTEGER:
                return (string)intval($this->value);

            case self::NAME:
                return static::scriviName($this->value);

            case self::NULL:
                return 'null';

            case self::NUMBER:
                return static::scriviNumber($this->value);

            case self::RECTANGLE:
                /**
                 * @disregard P1006 nel caso di RECTANGLE $this->value è sempre un oggetto figlio di BaseArray
                 */
                return $this->value->getInlineObject($ver);

            case self::STREAM:
                throw new PDFException('a stream can\'t be inline.');

            case self::TEXT_STRING:
                return static::scriviTextString($this->value);

            default:
                throw new PDFException('unknown value type.');
        }
    }

    private static function scriviName($str): string
    {
        $out = '/';
        $len = strlen($str);
        $i = 0;
        while ($i < $len) {
            $ord = ord($str[$i]);
            if ($ord === 35 || $ord < 33 || $ord > 126) {
                $out .= '#' . dechex($ord);
                $i += 3;
                continue;
            }
            $out .= $str[$i++];
        }
        return $out;
    }

    private static function scriviNumber($val): string
    {
        // N.B.: uso self::$float_decimals perché così tutti i numeri hanno le stesse cifre decimali
        $num = number_format(floatval($val), static::$float_decimals);
        if (static::$float_decimals <= 0)
            return $num;

        $last = strlen($num);
        while ($num[$last - 1] == '0')
            --$last;
        if ($num[$last - 1] == '.')
            --$last;
        return substr($num, 0, $last);
    }

    private static function scriviTextString($str, $forceByte = false, $forceText = false): string
    {
        // conto le parentesi aperte non bilanciate
        $parentesi_totali = 0;
        $parentesi_non_chiuse = 0;
        $len = strlen($str);
        for ($i = 0; $i < $len; ++$i) {
            if ($str[$i] === '(') {
                ++$parentesi_totali;
                ++$parentesi_non_chiuse;
            } else if ($str[$i] === ')' && $parentesi_non_chiuse > 0) {
                --$parentesi_non_chiuse;
            }
        }

        $parentesi_da_chiudere = $parentesi_totali - $parentesi_non_chiuse;
        $parentesi_non_chiuse = 0;

        $out1 = '';
        $out2 = '';
        for ($i = 0; $i < $len; ++$i) {
            $ord = ord($str[$i]);
            $out1 .= sprintf('%02X', $ord);

            if ($ord === 40) {
                ++$parentesi_non_chiuse;
                if ($parentesi_da_chiudere > 0) {
                    --$parentesi_da_chiudere;
                    $out2 .= '(';
                } else {
                    $out2 .= "\\(";
                }
            } else if ($ord === 41) {
                if ($parentesi_non_chiuse > 0) {
                    --$parentesi_non_chiuse;
                    $out2 .= ')';
                } else {
                    $out2 .= "\\)";
                }
            } else if ($ord === 92) {
                $out2 .= "\\\\";
            } else if ($ord >= 32 && $ord <= 126) {
                $out2 .= $str[$i];
            } else {
                switch ($ord) {
                    case 8:
                        $out2 .= "\\b";
                        break;

                    case 9:
                        $out2 .= "\\t";
                        break;

                    case 10:
                        $out2 .= "\\n";
                        break;

                    case 13:
                        $out2 .= "\\r";
                        break;

                    case 255:
                        $out2 .= "\\f";
                        break;

                    default:
                        if ($i + 1 < $len && $str[$i + 1] >= '0' && $str[$i + 1] <= '9') {
                            $out2 .= sprintf("\\%03o", $ord);
                        } else {
                            $out2 .= "\\" . decoct($ord);
                        }
                        break;
                }
            }
        }

        if ($forceByte)
            return '<' . $out1 . '>';

        if ($forceText)
            return '(' . $out2 . ')';

        return strlen($out1) < strlen($out2) ? '<' . $out1 . '>' : '(' . $out2 . ')';
    }
}
