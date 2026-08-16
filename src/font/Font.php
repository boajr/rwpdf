<?php

/**
 * Libreria che legge i true type, ho preso spunto dalla php-font-lib, ma non 
 * la uso perché probabilmente perderei più tempo a capire come funziona che a
 * riscrivere una libreria con le sole funzioni che mi servono, e poi così
 * imparo come sono strutturati i TrueType/OpenFont
 */

namespace Boajr\PDF\font;


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







    public function __construct(string $file_or_data)
    {
        if (is_file($file_or_data))
            $this->file = @fopen($file_or_data, 'rb');
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

                    print ($i ? ' ' : '') . $t;

                    if ($t == 'fvar')
                        throw new FontException('La lettura dei font variabili non è ancora supportata');
                    if ($t == 'SVG ')
                        throw new FontException('La lettura dei font SVG non è ancora supportata');

                    $this->tableRecords[$t] = unpack('Ntag/NcheckSum/Noffset/Nlenght', $t . $this->read(12));
                }
                print PHP_EOL;
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
        $head = array_key_exists('head', $this->tableRecords) ? $this->readTable('head') : null;
        if (!$head)
            throw new FontException('missing \'head\' table');
        $this->unitsPerEm = $head->unitsPerEm;

        // provo a vedere se dalla tabella OS/2 riesco ad estrarre lo style, 
        // la larghezza e se il font può essere incluso nel pdf

        /** @var FontTable_OS2 $os2 */
        $os2 = array_key_exists('OS/2', $this->tableRecords) ? $this->readTable('OS/2') : null;

        /** @var FontTable_name $name */
        $name = array_key_exists('name', $this->tableRecords) ? $this->readTable('name') : null;
        if (!$name)
            throw new FontException('missing \'name\' table');

        if ($os2) {
            $this->weight = $os2->version == 0 && $os2->usWeightClass > 0 && $os2->usWeightClass < 10 ? static::apple_weight[$os2->usWeightClass] : $os2->usWeightClass;
            $this->style = $os2->fsSelection & 1 ? ($os2->version > 3 && ($os2->fsSelection & 512) ? 'oblique' : 'italic') : 'normal';

            //print 'usWidthClass: ' . $os2->usWidthClass . PHP_EOL;
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

    //private function Skip(int $n)
    //{
    //    if ($this->memory) {
    //        $this->pos += $n;
    //        return;
    //    }
    //
    //    fseek($this->file, $n, SEEK_CUR);
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
        $post = array_key_exists('post', $this->tableRecords) ? $this->readTable('post') : null;
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
        $os2 = array_key_exists('OS/2', $this->tableRecords) ? $this->readTable('OS/2') : null;
        if ($os2) {
            $upe = $this->unitsPerEm;
            return [$os2->yStrikeoutPosition / $upe, $os2->yStrikeoutSize / $upe];
        }
        return null;
    }
}
