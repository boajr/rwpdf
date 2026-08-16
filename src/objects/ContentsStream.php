<?php

namespace Boajr\PDF\Objects;

use Boajr\PDF\Parser\DataStream;
use Boajr\PDF\Parser\IPDFObject;
use Boajr\PDF\Parser\PDFObjectDictionary;
use Boajr\PDF\Parser\PDFOperator;
use Boajr\PDF\Parser\PDFParserEndOfFileException;
use Boajr\PDF\PDFException;


class ContentsStream extends Stream
{
    /**
     * Tabella che riporta gli operatori presenti in un content stream come riportato a pagina 844 del file 
     * ISO_32000-2-2020_sponsored.pdf
     */
    private const operatori = [
        'b' => ['ver' => 1000, 'procSet' => 1],     // Close, fill, and stroke path using non-zero winding number rule
        'B' => ['ver' => 1000, 'procSet' => 1],     // Fill and stroke path using non-zero winding number rule
        'b*' => ['ver' => 1000, 'procSet' => 1],    // Close, fill, and stroke path using even-odd rule
        'B*' => ['ver' => 1000, 'procSet' => 1],    // Fill and stroke path using even-odd rule

        // 'BDC' => ['ver' => 1002, 'procSet' => 0, 'params' => "Table 352 — Marked-content operators"],
        // 'BI' => ['ver' => 1000, 'procSet' => 0, 'params' => "Table 90 — Inline image operators"],
        // 'BMC' => ['ver' => 1002, 'procSet' => 0, 'params' => "Table 351 — Entries in a data dictionary"],
        'BT' => ['ver' => 1000, 'procSet' => 2], // Begin a text object
        'BX' => ['ver' => 1001, 'procSet' => 0], // Begin compatibility section
        // 'c' => ['ver' => 1000, 'procSet' => 0, 'params' => "Table 60 — Clipping path operators"],
        // 'cm' => ['ver' => 1000, 'procSet' => 0, 'params' => "Table 56 — Graphics state operators"],

        // 'CS' => ['ver' => 1001, 'procSet' => 0, 'params' => "Table 73 — Colour operators"],
        // 'cs' => ['ver' => 1001, 'procSet' => 0, 'params' => "Table 73 — Colour operators"],
        // 'd' => ['ver' => 1000, 'procSet' => 0, 'params' => "Table 56 — Graphics state operators"],
        // 'd0' => ['ver' => 1000, 'procSet' => 0, 'params' => "Table 111 — Type 3 font operators"],
        // 'd1' => ['ver' => 1000, 'procSet' => 0, 'params' => "Table 111 — Type 3 font operators"],
        // 'Do' => ['ver' => 1000, 'procSet' => 0, 'params' => "Table 86 — XObject operator"],
        // 'DP' => ['ver' => 1002, 'procSet' => 0, 'params' => "Table 352 — Marked-content operators"],
        // 'EI' => ['ver' => 1000, 'procSet' => 0, 'params' => "Table 90 — Inline image operators"],
        // 'EMC' => ['ver' => 1002, 'procSet' => 0, 'params' => "Table 352 — Marked-content operators"],
        'ET' => ['ver' => 1000, 'procSet' => 2], // End a text object
        'EX' => ['ver' => 1001, 'procSet' => 0], // End compatibility section
        'f' => ['ver' => 1000, 'procSet' => 1],    // Fill path using non-zero winding number rule
        //'F' => ['ver' => 1000, 'procSet' => 1],    // equivalente di 'f'
        'f*' => ['ver' => 1000, 'procSet' => 1],   // Fill path using even-odd rule
        'G' => ['ver' => 1000, 'procSet' => 1, 'params' => ['gray' => [Entry::NUMBER, 0.0, 1.0]], 'graphicsState' => ['color_space_stroking' => 'DeviceGray', 'color_stroking' => '#params']],   // Set gray level for stroking operations
        'g' => ['ver' => 1000, 'procSet' => 1, 'params' => ['gray' => [Entry::NUMBER, 0.0, 1.0]], 'graphicsState' => ['color_space' => 'DeviceGray', 'color' => '#params']],   // Set gray level for nonstroking operations
        // 'gs' => ['ver' => 1002, 'procSet' => 0, 'params' => "Table 56 — Graphics state operators"],
        'h' => ['ver' => 1000, 'procSet' => 1],   // Close subpath
        // 'i' => ['ver' => 1000, 'procSet' => 0, 'params' => "Table 56 — Graphics state operators"],
        // 'ID' => ['ver' => 1000, 'procSet' => 0, 'params' => "Table 90 — Inline image operators"],
        'j' => ['ver' => 1000, 'procSet' => 1, 'params' => ['lineJoin' => Entry::INTEGER], 'graphicsState' => ['line_join' => '$lineJoin']],   // Set line join style
        'J' => ['ver' => 1000, 'procSet' => 1, 'params' => ['lineCap' => Entry::INTEGER], 'graphicsState' => ['line_cap' => '$lineCap']],   // Set line cap style
        'K' => ['ver' => 1000, 'procSet' => 1, 'params' => ['c' => [Entry::NUMBER, 0.0, 1.0], 'm' => [Entry::NUMBER, 0.0, 1.0], 'y' => [Entry::NUMBER, 0.0, 1.0], 'k' => [Entry::NUMBER, 0.0, 1.0]], 'graphicsState' => ['color_space_stroking' => 'DeviceCMYK', 'color_stroking' => '#params']],   // Set CMYK colour for stroking operations

        'k' => ['ver' => 1000, 'procSet' => 1, 'params' => ['c' => [Entry::NUMBER, 0.0, 1.0], 'm' => [Entry::NUMBER, 0.0, 1.0], 'y' => [Entry::NUMBER, 0.0, 1.0], 'k' => [Entry::NUMBER, 0.0, 1.0]], 'graphicsState' => ['color_space' => 'DeviceCMYK', 'color' => '#params']],   // Set CMYK colour for nonstroking operations
        'l' => ['ver' => 1000, 'procSet' => 1, 'params' => ['x' => Entry::NUMBER, 'y' => Entry::NUMBER]],   // Append straight line segment to path
        'm' => ['ver' => 1000, 'procSet' => 1, 'params' => ['x' => Entry::NUMBER, 'y' => Entry::NUMBER]],   // Begin new subpath
        'M' => ['ver' => 1000, 'procSet' => 1, 'params' => ['miterLimit' => Entry::NUMBER], 'graphicsState' => ['miter_limit' => '$miterLimit']],   // Set miter limit
        // 'MP' => ['ver' => 1002, 'procSet' => 0, 'params' => "Table 352 — Marked-content operators"],
        'n' => ['ver' => 1000, 'procSet' => 1],   // End path without filling or stroking
        'q' => ['ver' => 1000, 'procSet' => 0],   // Save graphics state, posso anche commentare
        'Q' => ['ver' => 1000, 'procSet' => 0],   // Restore graphics state, posso anche commentare
        're' => ['ver' => 1000, 'procSet' => 1, 'params' => ['x' => Entry::NUMBER, 'y' => Entry::NUMBER, 'width' => Entry::NUMBER, 'height' => Entry::NUMBER]],   // Append rectangle to path
        'RG' => ['ver' => 1000, 'procSet' => 1, 'params' => ['r' => [Entry::NUMBER, 0.0, 1.0], 'g' => [Entry::NUMBER, 0.0, 1.0], 'b' => [Entry::NUMBER, 0.0, 1.0]], 'graphicsState' => ['color_space_stroking' => 'DeviceRGB', 'color_stroking' => '#params']],   // Set RGB colour for stroking operations
        'rg' => ['ver' => 1000, 'procSet' => 1, 'params' => ['r' => [Entry::NUMBER, 0.0, 1.0], 'g' => [Entry::NUMBER, 0.0, 1.0], 'b' => [Entry::NUMBER, 0.0, 1.0]], 'graphicsState' => ['color_space' => 'DeviceRGB', 'color' => '#params']],   // Set RGB colour for nonstroking operations
        // 'ri' => ['ver' => 1000, 'procSet' => 0, 'params' => "Table 56 — Graphics state operators"],
        's' => ['ver' => 1000, 'procSet' => 1],   // Close and stroke path
        'S' => ['ver' => 1000, 'procSet' => 1],   // Stroke path
        // 'SC' => ['ver' => 1001, 'procSet' => 0, 'params' => "Table 73 — Colour operators"],
        // 'sc' => ['ver' => 1001, 'procSet' => 0, 'params' => "Table 73 — Colour operators"],
        // 'SCN' => ['ver' => 1002, 'procSet' => 0, 'params' => "Table 73 — Colour operators"],
        // 'scn' => ['ver' => 1002, 'procSet' => 0, 'params' => "Table 73 — Colour operators"],
        // 'sh' => ['ver' => 1003, 'procSet' => 0, 'params' => "Table 76 — Shading operator"],
        'T*' => ['ver' => 1000, 'procSet' => 2, 'textState' => ['T*' => 'setMatrix']], // Move to the start of the next line
        'Tc' => ['ver' => 1000, 'procSet' => 2, 'params' => ['charSpace' => Entry::NUMBER], 'textState' => ['Tc' => '$charSpace']], // Set the character spacing

        'Td' => ['ver' => 1000, 'procSet' => 2, 'params' => ['tx' => Entry::NUMBER, 'ty' => Entry::NUMBER], 'textState' => ['Td' => 'setMatrix']], // Move to the start of the next line, offset from the start of the current line
        'TD' => ['ver' => 1000, 'procSet' => 2, 'params' => ['tx' => Entry::NUMBER, 'ty' => Entry::NUMBER], 'textState' => ['Tl' => '-$ty', 'TD' => 'setMatrix']], // Move to the start of the next line, offset from the start of the current line and set Tl
        'Tf' => ['ver' => 1000, 'procSet' => 2, 'params' => ['font' => Entry::INTERNAL_FONT, 'size' => Entry::NUMBER], 'textState' => ['Tf' => 'setFont', 'Tfs' => '$size']], // Set the text font and the text font size
        'Tj' => ['ver' => 1000, 'procSet' => 2, 'params' => ['string' => Entry::TEXT_STRING], 'textState' => ['string' => 'setText']], // Show a text string
        'TJ' => ['ver' => 1000, 'procSet' => 2, 'params' => ['array' => Entry::ARRAY], 'textState' => ['array' => 'setText']], // Show zero or more text strings
        'TL' => ['ver' => 1000, 'procSet' => 2, 'params' => ['leading' => Entry::NUMBER], 'textState' => ['Tl' => '$leading']], // Set the text leading
        'Tm' => ['ver' => 1000, 'procSet' => 2, 'params' => ['a' => Entry::NUMBER, 'b' => Entry::NUMBER, 'c' => Entry::NUMBER, 'd' => Entry::NUMBER, 'e' => Entry::NUMBER, 'f' => Entry::NUMBER], 'textState' => ['Tm' => 'setMatrix']], // Set the text matrix
        'Tr' => ['ver' => 1000, 'procSet' => 2, 'params' => ['render' => Entry::NUMBER], 'textState' => ['Tmode' => '$render']], // Set the text rendering mode
        'Ts' => ['ver' => 1000, 'procSet' => 2, 'params' => ['rise' => Entry::NUMBER], 'textState' => ['Trise' => '$rise']], // Set the text rise
        'Tw' => ['ver' => 1000, 'procSet' => 2, 'params' => ['wordSpace' => Entry::NUMBER], 'textState' => ['Tw' => '$wordSpace']], // Set the word spacing
        'Tz' => ['ver' => 1000, 'procSet' => 2, 'params' => ['scale' => Entry::NUMBER], 'textState' => ['Th' => '$scale']],   // Set the font horizontal scaling
        // 'v' => ['ver' => 1000, 'procSet' => 0, 'params' => "Table 58 — Path construction operators"],
        'w' => ['ver' => 1000, 'procSet' => 1, 'params' => ['lineWidth' => Entry::NUMBER], 'graphicsState' => ['line_width' => '$lineWidth']],   // Set line width
        'W' => ['ver' => 1000, 'procSet' => 1],    // Set clipping path using non-zero winding number rule
        'W*' => ['ver' => 1000, 'procSet' => 1],   // Set clipping path using even-odd rule
        // 'y' => ['ver' => 1000, 'procSet' => 0, 'params' => "Table 58 — Path construction operators"],
        '\'' => ['ver' => 1000, 'procSet' => 0, 'params' => ['string' => Entry::TEXT_STRING], 'textState' => ['string' => 'setText']], // Move to the next line and show a text string
        '"' => ['ver' => 1000, 'procSet' => 0, 'params' => ['aw' => Entry::NUMBER, 'ac' => Entry::NUMBER, 'string' => Entry::TEXT_STRING], 'textState' => ['Tw' => '$aw', 'Tc' => '$ac', 'string' => 'setText']], // Move to the next line and show a text string
    ];

    private const conversioni = [
        //IPDFObject::TYPE_ARRAY => Gestione particolare,
        IPDFObject::TYPE_BOOL => Entry::BOOLEAN,
        //IPDFObject::TYPE_DICTIONARY => Gestione particolare,
        IPDFObject::TYPE_FLOAT => Entry::NUMBER,
        IPDFObject::TYPE_INT => Entry::INTEGER,
        IPDFObject::TYPE_NAME => Entry::NAME,
        IPDFObject::TYPE_NULL => Entry::NULL,
        //IPDFObject::TYPE_REFERENCE => Non può essere,
        //IPDFObject::TYPE_STREAM => Non può essere,
        IPDFObject::TYPE_STRING => Entry::TEXT_STRING,
    ];

    /**
     * tengo traccia di quali procSet utilizzo nella pagina
     */
    protected int $procSet = 0;

    /**
     * elenco di tutti gli operators che compongono la pagina
     */
    protected array $operators = [];

    protected IResourcesWriter $resourcesWriter;
    protected GraphicsState $graphicsState;
    protected ResourcesDictionary $resources;




    public bool $needContents = false;


    public function __construct(InternalState $internalState, ?PDFObjectDictionary $src = null, ?IResourcesWriter $resourcesWriter = null, ?GraphicsState $graphicsState = null, ?ResourcesDictionary $resources = null)
    {
        $this->internalState = $internalState;
        $this->resourcesWriter = $resourcesWriter;
        $this->graphicsState = $graphicsState;
        $this->resources = $resources;

        //if ($src) {
        //$src->SetLinkedObject($this);
        $this->setStreamData($src, true);
        if (!$this->data)
            return;
        $this->AppendContents(null);
        //}
    }

    public function AppendContents(?string $data)
    {
        $this->needContents = false;

        $data = $data ? $this->data . ' ' . $data : $this->data;
        $ds = new DataStream($data, strlen($data));
        try {
            while (($src = $ds->ReadOperator()) instanceof PDFOperator) {
                $this->AppendOperator($src->operator, ...$src->parameters);
            }

            $this->data = null;
            $this->Length = 0;
            return true;
        } catch (PDFParserEndOfFileException $ex) {
            $this->needContents = true;
            $this->data = substr($this->data, $ex->getOperatorOffset());
            $this->Length = strlen($this->data);
            return false;
        }
    }

    public function AppendOperator(string $operator, mixed ...$parameters): void
    {
        $p = null;

        if ($operator === 'q') {
            $this->graphicsState->save();
        } else if ($operator === 'Q') {
            $this->graphicsState->restore();
        } else if ($operator === 'BT') {
            $this->graphicsState->startTextObject();
        } else if ($operator === 'ET') {
            $this->graphicsState->endTextObject();
        } else if ($operator === 'BX' || $operator === 'EX') {
            // non so se è il caso di verificare che BX e EX devono essere bilanciati, anche perché non è chiaro se
            // devono esserlo all'interno dello stream o all'interno della pagina
            $this->graphicsState->throwIfUnknown = $operator === 'EX';
        } else {
            if ($operator === 'F')
                $operator = 'f';

            if (array_key_exists($operator, static::operatori)) {
                $operatore = static::operatori[$operator];

                $op = $operatore['params'] ?? [];
                if (count($op) != count($parameters))
                    throw new PDFException('invalid \'' . $operator . '\' operator operands');

                if ($op) {
                    $p = [];
                    $i = 0;
                    foreach ($op as $k => $v) {
                        $min = null;
                        $max = null;
                        if (is_array($v) && $v[0] === Entry::NUMBER) {
                            $p[$k] = new Entry($this->internalState, [Entry::NUMBER => true], true, 1000, 999999);
                            $min = $v[1];
                            $max = $v[2];
                        } else if ($v === Entry::ARRAY)
                            $p[$k] = new Entry($this->internalState, [Entry::ARRAY => [ContentsStream_TextArray::class => true]], true, 1000, 999999);
                        else
                            $p[$k] = new Entry($this->internalState, [$v => true], true, 1000, 999999);

                        try {
                            $p[$k]->setValue($parameters[$i++]);
                        } catch (EntrySetValueException $ex) {
                            throw new PDFException('invalid \'' . $operator . '\' operator \'' . $k . '\' value');
                        }

                        if ($min !== null) {
                            $val = $p[$k]->getValue();
                            if ($val < $min || $val > $max)
                                throw new PDFException('out of range \'' . $operator . '\' operator \'' . $k . '\' value');
                        }
                    }
                }

                $this->procSet |= $operatore['procSet'];

                $gs = $operatore['graphicsState'] ?? false;
                if ($gs) {
                    foreach ($gs as $state => $value) {
                        if ($value == '#params') {
                            $this->graphicsState->$state = [];
                            foreach ($p as $e)
                                $this->graphicsState->$state[] = $e->getValue();
                        } else if (is_string($value) && $value && $value[0] == '$') {
                            $this->graphicsState->$state = $p[substr($value, 1)]->getValue();
                        } else {
                            $this->graphicsState->$state = $value;
                        }
                    }
                }

                $ts = $operatore['textState'] ?? false;
                if ($ts) {
                    foreach ($ts as $state => $value) {
                        if ($value == 'setFont') {
                            if (!$this->resources)
                                throw new PDFException('invalid pdf: contents stream has no resources to look for font');
                            if (!$this->resources->Font)
                                throw new PDFException('invalid pdf: resources has no font dictionary');
                            $fontName = $p['font']->getValue();
                            $font = $this->resources->Font->$fontName;
                            $this->graphicsState->text_state->Tf = $this->resourcesWriter->addFont($font);
                            $this->graphicsState->text_state->encoding = $font->Encoding;
                            $p['font']->setValue($this->graphicsState->text_state->Tf);
                        } else if ($value == 'setMatrix') {
                            switch ($state) {
                                case 'Td':
                                case 'TD':
                                    $this->graphicsState->alterLineTextMatrix($p['tx']->getValue(), $p['ty']->getValue());
                                    break;
                                case 'Tm':
                                    $this->graphicsState->setLineTextMatrix($p['a']->getValue(), $p['b']->getValue(), $p['c']->getValue(), $p['d']->getValue(), $p['e']->getValue(), $p['f']->getValue());
                                    break;
                                case 'T*':
                                    $this->graphicsState->alterLineTextMatrix(0, -$this->graphicsState->text_state->Tl);
                                    break;
                            }
                        } else if ($value == 'setText') {
                            $this->graphicsState->addUsedChars($p[$state]->getValue());
                        } else {
                            if ($value && is_string($value)) {
                                if ($value[0] == '$') {
                                    $this->graphicsState->text_state->$state = $p[substr($value, 1)]->getValue();
                                    continue;
                                }
                                if (substr($value, 0, 2) == '-$') {
                                    $this->graphicsState->text_state->$state = -$p[substr($value, 2)]->getValue();
                                    continue;
                                }
                            }
                            $this->graphicsState->text_state->$state = $value;
                        }
                    }
                }
            } else {
                if ($this->graphicsState->throwIfUnknown)
                    throw new PDFException('invalid operator');

                print $operator . PHP_EOL;

                if ($parameters) {
                    $p = [];
                    foreach ($parameters as $v) {
                        $t = $v->GetType();

                        if ($t === IPDFObject::TYPE_ARRAY)
                            throw new PDFException('TODO: conversione degli array');

                        if ($t === IPDFObject::TYPE_DICTIONARY)
                            throw new PDFException('TODO: conversione dei dictionary');

                        if (!array_key_exists($t, static::conversioni))
                            throw new PDFException('invalid operand type');

                        $t = static::conversioni[$t];
                        $e = new Entry($this->internalState, [$t => true], true, 1000, 999999);
                        $e->setValue($v);
                        $p[] = $e;
                    }
                }
            }
        }
        $this->operators[] = ['o' => $operator, 'p' => $p];
    }

    public function AppendFontOperator(InternalFont $font, float $size): void
    {
        $this->graphicsState->text_state->Tf = $font;
        $this->graphicsState->text_state->encoding = null;
        $f = new Entry($this->internalState, [Entry::INTERNAL_FONT => true], true, 1000, 999999);
        $f->setValue($this->resourcesWriter->addFont($font));

        $this->graphicsState->text_state->Tfs = $size;
        $s = new Entry($this->internalState, [Entry::NUMBER => true], true, 1000, 999999);
        $s->setValue($size);

        $this->operators[] = ['o' => 'Tf', 'p' => ['font' => $f, 'size' => $s]];
    }

    public function AppendTextOperator(string $testo): float
    {
        [$text, $width] = $this->graphicsState->processText($testo);
        if (is_array($text)) {
            $t = new Entry($this->internalState, [Entry::ARRAY => true], true, 1000, 999999);
            $t->setValue($text);
            $this->operators[] = ['o' => 'TJ', 'p' => ['array' => $t]];
        } else {
            $t = new Entry($this->internalState, [Entry::TEXT_STRING => true], true, 1000, 999999);
            $t->setValue($text);
            $this->operators[] = ['o' => 'Tj', 'p' => ['string' => $t]];
        }
        return $width;
    }

    public function IsEmpty(): bool
    {
        return !!count($this->operators);
    }

    public function getProcSet(): int
    {
        $ps = $this->procSet;

        print 'TODO: scorrere tutte le risorse per vedere se devo aggiungere altri procset' . PHP_EOL;

        return $ps;
    }



    private function getOperatorOutput(int $ver, string $op, ?array $parms): string
    {
        $out = '';
        if ($parms) {
            foreach ($parms as $entry) {
                if ($entry->startWithSeparator() || ($out != '' && strpos(" \t\r\n\f()<>[]/%", $out[strlen($out) - 1]) !== false)) {
                    $out .= $entry->getOutput($ver);
                    continue;
                }
                $out .= ' ' . $entry->getOutput($ver);
            }
        }
        return $out . ($out != '' && strpos(" \t\r\n\f()<>[]/%", $out[strlen($out) - 1]) ? '' : ' ') . $op;
    }

    protected function get_stream_data(int $ver): string
    {
        $first = true;
        $data = '';
        foreach ($this->operators as $op) {
            if ($first) {
                $data = ltrim($this->getOperatorOutput($ver, $op['o'], $op['p']));
                $first = false;
            } else {
                $data .= $this->getOperatorOutput($ver, $op['o'], $op['p']);
            }
        }

        // comprime lo stream
        //$this->Filter = 'FlateDecode';
        //$data = zlib_encode($data, ZLIB_ENCODING_DEFLATE);
        $this->Length = strlen($data);

        return $data;
    }
}
