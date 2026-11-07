<?php

/**
 * Libreria che legge i true type, ho preso spunto dalla php-font-lib, ma non 
 * la uso perché probabilmente perderei più tempo a capire come funziona che a
 * riscrivere una libreria con le sole funzioni che mi servono, e poi così
 * imparo come sono strutturati i TrueType/OpenFont
 */

namespace Boajr\PDF\font;

use stdClass;

class Font
{
    /**
     * @var ?resource $file - pointer to file to import
     */
    private $file = null;

    /**
     * @var ?string $memory - memory block with data to parse
     */
    private $memory = null;

    /**
     * @var int $end_of_file - end position of last %%EOF marker
     */
    private $end_of_file = 0;

    /**
     * @var int $pos - pointer to last read byte in $memory or in $read_data
     */
    private $pos = 0;




    private array|false|null $header = null;
    private array $tableRecords = [];
    private array $tables = [];



    public ?string $family = null;
    public ?string $style = null;
    public ?int $weight = null;

    public const EMBEDDABLE_NO = 0;
    public const EMBEDDABLE_READONLY = 1;
    public const EMBEDDABLE_FULL = 2;
    public ?bool $embeddable = null;

    public ?bool $shrinkable = null;

    public ?stdClass $fontDescriptorData = null;

    // Non vado in ordine di "peso" perché altrimenti extrabold verrebbe
    // riconosciuto come bold
    private const weights = [
        'thin' => 100,
        'extralight' => 200,
        'ultralight' => 200,
        'light' => 300,
        //'' => 400,
        //'normal' => 400,
        //'regular' => 400,
        'medium' => 500,
        'demibold' => 600,
        'semibold' => 600,
        'extrabold' => 800,
        'ultrabold' => 800,
        'bold' => 700,
        'heavy' => 900,
        'extrablack' => 950,
        'ultrablack' => 950,
        'black' => 900,
    ];

    private const apple_weight = [
        1 => 100,  # Ultra-Light
        2 => 200,  # Extra-Light
        3 => 300,  # Light
        4 => 350,  # Semi-Light
        5 => 400,  # Medium (normal)
        6 => 500,  # Semi-bold
        7 => 700,  # Bold
        8 => 800,  # Extra-Bold
        9 => 900   # Ultra-Bold
    ];

    private ?int $unitsPerEm = null;

    private array $charWidths = [];







    public function __construct(string $file_or_data)
    {
        if (is_file($file_or_data))
            $this->file = @fopen($file_or_data, 'rb');
        $this->end_of_file = filesize($file_or_data);
        if (!$this->file) {
            $this->memory = $file_or_data;
            $this->end_of_file = strlen($file_or_data);
        }

        // leggo i primi 4 byte del file
        $type = $this->Read(4);
        switch ($type) {
            case "\x00\x01\x00\x00":   // TrueType
            case "true":               // TrueType
            case "typ1":               // TrueType
            case "OTTO":               // OpenType
                $this->header = unpack('NsfntVersion/nnumTables/nsearchRange/nentrySelector/nrangeShift', $type . $this->read(8));
                for ($i = 0; $i < $this->header['numTables']; ++$i) {
                    $t = $this->Read(4);

                    echo ($i ? ' ' : '') . $t;

                    if ($t == 'fvar')
                        throw new FontException('La lettura dei font variabili non è ancora supportata');
                    if ($t == 'SVG ')
                        throw new FontException('La lettura dei font SVG non è ancora supportata');

                    $this->tableRecords[$t] = unpack('Ntag/NcheckSum/Noffset/Nlenght', $t . $this->read(12));
                }
                echo PHP_EOL;
                break;

            //case "wOFF":
            //    $class = "WOFF";
            //    break;

            //case "ttcf":
            //    $class = "TrueTypeCollection";
            //    break;

            // Unknown type or EOT
            //default:
            //    $magicNumber = file_get_contents($file, false, null, 34, 2);
            //    if ($magicNumber === "LP") {
            //        $class = "EOT\\File";
            //    }

            default:
                throw new FontException('Unknow file type (\'' . $type . '\')');
        }

        // estraggo dall'header la unitsPerEm

        /** @var FontTable_head $head */
        $head = $this->readTable('head');
        if (!$head)
            throw new FontException('missing \'head\' table');
        $this->unitsPerEm = $head->unitsPerEm;

        // provo a vedere se dalla tabella OS/2 riesco ad estrarre lo style, 
        // la larghezza e se il font può essere incluso nel pdf

        /** @var FontTable_hhea $hhea */
        $hhea = $this->readTable('hhea');

        /** @var FontTable_hmtx $hmtx */
        $hmtx = $this->readTable('hmtx');

        /** @var FontTable_OS2 $os2 */
        $os2 = $this->readTable('OS/2');

        /** @var FontTable_name $name */
        $name = $this->readTable('name');
        if (!$name)
            throw new FontException('missing \'name\' table');

        /** @var FontTable_post $post */
        $post = $this->readTable('post');

        if ($os2) {
            $this->weight = $os2->version == 0 && $os2->usWeightClass > 0 && $os2->usWeightClass < 10 ? static::apple_weight[$os2->usWeightClass] : $os2->usWeightClass;
            $this->style = $os2->fsSelection & 1 ? ($os2->version > 3 && ($os2->fsSelection & 512) ? 'oblique' : 'italic') : 'normal';

            if ($os2->fsType & 1024 == 0) {
                switch ($os2->fsType & 14) {
                    case 0: // Controllo completo sul font
                    case 8: // Font utilizzabile solo temporaneamente
                        $this->embeddable = static::EMBEDDABLE_FULL;
                        break;

                    case 2: // Font non utilizzabile
                        $this->embeddable = static::EMBEDDABLE_NO;
                        break;

                    case 4: // Font utilizzabile solo in lettura
                        $this->embeddable = static::EMBEDDABLE_READONLY;
                        break;

                    default:
                        throw new FontException('invalid OS/2.fsType value');
                }
            } else
                $this->embeddable = static::EMBEDDABLE_NO;

            $this->shrinkable = ($os2->fsType & 512) == 0;
        } else {
            // provo a leggere weight e style dalla sub Family
            $wss = $name->getString(22);
            if (!$wss)
                $wss = $name->getString(17);
            if (!$wss)
                $wss = $name->getString(2);

            if ($wss) {
                $wss = strtolower($wss);
                if (str_contains($wss, 'italic'))
                    $this->style = 'italic';
                else if (str_contains($wss, 'oblique'))
                    $this->style = 'oblique';
                else
                    $this->style = 'normal';

                $this->weight = 400;
                foreach (static::weights as $k => $w) {
                    if (str_contains($wss, $k)) {
                        $this->weight = $w;
                        break;
                    }
                }
            } else {
                $this->weight = $head->macStyle & 1 ? 700 : 400;
                $this->style = $head->macStyle & 2 ? 'italic' : 'normal';
            }
        }

        $this->family = $name->getString(21);
        if (!$this->family)
            $this->family = $name->getString(16);
        if (!$this->family)
            $this->family = $name->getString(1);

        // estrazione dei dati da scrive in un font descriprion data
        $k = 1000 / $this->unitsPerEm;

        $fdd = new stdClass;
        $fdd->FontName = $name ? $name->getString(6) : null;
        //$fdd->FontFamily = $this->family;
        // 'FontStretch' => $this->FontDescriptor->FontStretch,
        //$fdd->FontWeight = $this->weight;

        if ($os2) {
            $sFamily = $os2->sFamilyClass >> 8;
            $isSerif = ($sFamily > 0 && $sFamily <= 5) || $sFamily == 7;
            $isScript = $sFamily == 10;
            $isSymbolic = ($os2->ulCodePageRange1 & 0x80000000) !== 0 || $sFamily == 12;
        } else {
            $isSerif = false;
            $isScript = false;
            $isSymbolic = false;
        }

        $fdd->Flags =
            ($post && $post->isFixedPitch ? 1 : 0) +   // bit  1: FixedPitch (all glyphs have the same width)
            ($isSerif ? 2 : 0) +                       // bit  2: Serif (glyphs have serifs)
            ($isSymbolic ? 4 : 32) +                   // bit  3: Symbolic (font contains glyphs outside the Standard Latin character set)
            // bit  6: Nonsymbolic (font uses the Standard Latin character set or a subset of it)
            ($isScript ? 8 : 0) +                      // bit  4: Script (glyphs resemble cursive handwriting)
            ($this->style != 'normal' ? 64 : 0);       // bit  7: Italic (glyphs have dominant vertical strokes that are slanted)
        // 17 AllCap Font contains no lowercase letters; typically used for display purposes, such as for titles or headlines.
        // 18 SmallCap Font contains both uppercase and lowercase letters. The uppercase letters are similar to those in the regular version of the same typeface family. The glyphs for the lowercase letters have the same shapes as the corresponding uppercase letters, but they are sized and their proportions adjusted so that they have the same size and stroke weight as lowercase glyphs in the same typeface family.
        // 19 ForceBold See description after Note 1 in this subclause.

        $fdd->FontBBox = (object)[
            'xMin' => $head ? round($k * $head->xMin) : null,
            'yMin' => $head ? round($k * $head->yMin) : null,
            'xMax' => $head ? round($k * $head->xMax) : null,
            'yMax' => $head ? round($k * $head->yMax) : null,
        ];
        $fdd->ItalicAngle = $post ? $post->italicAngle : null;

        // meglio calcolarli dalle lettere p e d ???
        if ($os2) {
            if ($os2->fsSelection & 128) {
                $fdd->Ascent = round($k * $os2->sTypoAscender);
                $fdd->Descent = round($k * $os2->sTypoDescender);
            } else {
                $fdd->Ascent = round($k * $os2->usWinAscent);
                $fdd->Descent = round($k * $os2->usWinDescent);
            }
        } else if ($hhea) {
            $fdd->Ascent = round($k * $hhea->ascender);
            $fdd->Descent = round($k * $hhea->descender);
        } else {
            $fdd->Ascent = null;
            $fdd->Descent = null;

            echo 'A ' . round($k * $os2?->sTypoAscender) . ' ' . round($k * $hhea->ascender) . PHP_EOL;

            echo 'Ascendente d: ' . round($this->ascendenteFont('d') * $k) . PHP_EOL;
            echo 'Ascendente b: ' . round($this->ascendenteFont('b') * $k) . PHP_EOL;
            echo 'Ascendente f: ' . round($this->ascendenteFont('f') * $k) . PHP_EOL;
            echo 'Ascendente h: ' . round($this->ascendenteFont('h') * $k) . PHP_EOL;
            echo 'Ascendente k: ' . round($this->ascendenteFont('k') * $k) . PHP_EOL;
            echo 'Ascendente l: ' . round($this->ascendenteFont('l') * $k) . PHP_EOL;
            echo 'Discendente p: ' . round($this->discendenteFont('p') * $k) . PHP_EOL;
            echo 'Discendente g: ' . round($this->discendenteFont('g') * $k) . PHP_EOL;
            echo 'Discendente j: ' . round($this->discendenteFont('j') * $k) . PHP_EOL;
            echo 'Discendente q: ' . round($this->discendenteFont('q') * $k) . PHP_EOL;
            echo 'Discendente y: ' . round($this->discendenteFont('y') * $k) . PHP_EOL;
        }

        // 'Leading' => $this->FontDescriptor->Leading,
        $fdd->CapHeight = $os2 ? round($k * $os2->sCapHeight) : null;
        if (!$fdd->CapHeight) {
            // (Consigliato):Se stai effettuando il parsing del file TrueType,
            // leggi l'altezza massima (il valore yMax) della sola lettera H
            // maiuscola nella tabella glyf. Ricordati di normalizzarla su base
            // 1000. Questo è il /CapHeight perfetto e matematicamente
            // inattaccabile.
            $h = 0;
            foreach (['H', 'I'] as $c) {
                $h = $this->altezzaFont($c);
                if ($h)
                    break;
            }
            $fdd->CapHeight = $h ? round($k * $h) : null;
        }

        // 'XHeight' => round(),
        $fdd->StemV = round($this->weight / 5);
        // 'StemH' => $this->FontDescriptor->StemH,
        // 'AvgWidth' => $this->FontDescriptor->AvgWidth,
        // 'MaxWidth' => $this->FontDescriptor->MaxWidth,

        // è  obbligatorio metterla per la conformità PDF/A
        $fdd->MissingWidth = $hmtx ? round($k * $hmtx->getGlyphWidth(0)) : null;

        $this->fontDescriptorData = $fdd;
    }

    public function __destruct()
    {
        if (is_resource($this->file))
            @fclose($this->file);
    }

    public function seek(int $offset, int $whence = SEEK_SET): int
    {
        if ($this->memory) {
            switch ($whence) {
                case SEEK_CUR:
                    $this->pos += $offset;
                    break;

                case SEEK_END:
                    $this->pos = $this->end_of_file - $offset;
                    break;

                default:
                    $this->pos = $offset;
                    break;
            }

            if ($this->pos < 0)
                $this->pos = 0;
            else if ($this->pos > $this->end_of_file)
                $this->pos = $this->end_of_file;
            return 0;
        }

        return fseek($this->file, $offset, $whence);
    }

    //public function skip(int $n): bool
    //{
    //    if ($n <= 0)
    //        return false;
    //
    //    if ($this->memory)
    //        $this->pos += $n;
    //    else
    //        fseek($this->file, $n, SEEK_CUR);
    //    return true;
    //}

    public function read(int $length): string|false
    {
        if ($length <= 0)
            return false;

        if ($this->memory) {
            if ($this->pos + $length > $this->end_of_file)
                $length = $this->end_of_file - $this->pos;

            if ($length <= 0)
                return '';

            $ret = substr($this->memory, $this->pos, $length);
            $this->pos += $length;
            return $ret;
        }

        return fread($this->file, $length);
    }

    public function readTable(string $type)
    {
        if (!array_key_exists($type, $this->tableRecords))
            return null;

        if (!array_key_exists($type, $this->tables)) {
            $record = $this->tableRecords[$type] ?? null;
            if (!$record)
                throw new FontException('missing ' . $type . ' table');

            $class = '\\Boajr\PDF\font\FontTable_' . preg_replace("/[^A-Za-z0-9]/", '', $type);
            if (!@class_exists($class))
                throw new FontException('missing ' . $class . ' class');

            $this->tables[$type] = new $class($this, $record['offset'], $record['lenght']);
        }
        return $this->tables[$type];
    }

    /**
     * Restituisce posizione e spessore della riga da usare per sottolineare il testo
     * 
     * I dati si trovano nella tabella post (underlinePosition e underlineThickness)
     */
    public function getUnderlineMeasures(): ?array
    {
        $post = $this->readTable('post');
        if ($post) {
            $upe = $this->unitsPerEm;
            return [$post->underlinePosition / $upe, $post->underlineThickness / $upe];
        }
        return null;
    }

    /**
     * Restituisce posizione e spessore della riga da usare per barrare il testo
     * 
     * I dati si trovano nella tabella OS/2 (yStrikeoutPosition e yStrikeoutSize)
     */
    public function getStrikeoutMeasures(): ?array
    {
        $os2 = $this->readTable('OS/2');
        if ($os2) {
            $upe = $this->unitsPerEm;
            return [$os2->yStrikeoutPosition / $upe, $os2->yStrikeoutSize / $upe];
        }
        return null;
    }

    private function altezzaFont(string $c): int
    {
        // trovo su che carattere è mappata la lettera
        $cmap = $this->readTable('cmap');
        $ord = $cmap->getCharPosition($c);
        if (!$ord)
            return 0;

        // se ho la tabella glyf estraggo da qui l'altezza del font
        $glyf = $this->readTable('glyf');
        if ($glyf) {
            $glyph = $glyf->getGlyphHeader($ord);
            return $glyph ? $glyph->yMax - $glyph->yMin : 0;
        }

        //$cff = $this->readTable('CFF');
        //if ($cff) {
        //    return 2;
        //}

        //$cff2 = $this->readTable('CFF');
        //if ($cff2) {
        //    return 3;
        //}

        return 0;
    }

    private function ascendenteFont(string $c): int
    {
        // trovo su che carattere è mappata la lettera
        $cmap = $this->readTable('cmap');
        $ord = $cmap->getCharPosition($c);
        if (!$ord)
            return 0;

        // se ho la tabella glyf estraggo da qui l'altezza del font
        $glyf = $this->readTable('glyf');
        if ($glyf) {
            $glyph = $glyf->getGlyphHeader($ord);
            return $glyph ? $glyph->yMax : 0;
        }

        $cff = $this->readTable('CFF ');
        if ($cff) {
            die('OKKKKKK');
            return 2;
        }

        //$cff2 = $this->readTable('CFF2');
        //if ($cff2) {
        //    return 3;
        //}

        return 0;
    }

    private function discendenteFont(string $c): int
    {
        // trovo su che carattere è mappata la lettera
        $cmap = $this->readTable('cmap');
        $ord = $cmap->getCharPosition($c);
        if (!$ord)
            return 0;

        // se ho la tabella glyf estraggo da qui l'altezza del font
        $glyf = $this->readTable('glyf');
        if ($glyf) {
            $glyph = $glyf->getGlyphHeader($ord);
            return $glyph ? $glyph->yMin : 0;
        }

        //$cff = $this->readTable('CFF');
        //if ($cff) {
        //    return 2;
        //}

        //$cff2 = $this->readTable('CFF');
        //if ($cff2) {
        //    return 3;
        //}

        return 0;
    }

    public function appendMissingFontData(object $data)
    {
        foreach (get_object_vars($this->fontDescriptorData) as $k => $v)
            if ($v === null) {
                if ($k == 'FontName') {
                    $name = $data->FontDescriptor->FontName;
                    $this->fontDescriptorData->FontName = $name[6] == '+' ? substr($name, 7) : $name;
                } else if ($k == 'FontBBox') {
                    $rect = $data->FontDescriptor->FontBBox;
                    $this->fontDescriptorData->FontBBox = (object)[
                        'xMin' => $rect->left,
                        'yMin' => $rect->top,
                        'xMax' => $rect->right,
                        'yMax' => $rect->bottom,
                    ];
                } else
                    $this->fontDescriptorData->$k = $data->FontDescriptor->$k;
            }
    }

    public function getCharWidth(string $char): ?int
    {
        if (!array_key_exists($char, $this->charWidths)) {
            $cmap = $this->readTable('cmap');
            $ord = $cmap->getCharPosition($char);
            if ($ord) {
                $hmtx = $this->readTable('hmtx');
                $this->charWidths[$char] = $hmtx->getGlyphWidth($ord);
            } else
                $this->charWidths[$char] = null;
        }
        return $this->charWidths[$char];
    }

    public function isTrueType(): bool
    {
        return $this->readTable('glyf') !== null;
    }

    public function toFile(?array $usedChars = null): string
    {

$usedChars = null;

        // se non posso shrinkare il file o se devo tenere tutti i caratteri,
        // restituisco il file originale
        if ($usedChars === null || !$this->shrinkable) {
            if ($this->memory)
                return $this->memory;

            fseek($this->file, 0);
            return fread($this->file, $this->end_of_file);
        }

        if ($this->readTable('glyf')) {
        }


        throw new FontException('Something strange appened in file shrink');
    }
}
