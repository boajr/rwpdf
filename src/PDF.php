<?php

namespace Boajr\PDF;

use Boajr\PDF\Objects\CatalogDictionary;
use Boajr\PDF\Objects\DocumentInformationDictionary;
use Boajr\PDF\Objects\Entry;
use Boajr\PDF\Objects\InternalState;
use Boajr\PDF\Objects\PageDictionary;
use Boajr\PDF\Objects\PagesDictionary;
use Boajr\PDF\Objects\Rectangle;
use Boajr\PDF\Parser\DataStream;
use Boajr\PDF\Parser\IPDFObject;
use Boajr\PDF\Parser\PDFObjectDictionary;
use Stringable;
use Throwable;


class PDF implements Stringable
{
    public const CAP_BUTT = 0;
    public const CAP_ROUND = 1;
    public const CAP_SQUARE = 2;

    public const JOIN_MITER = 0;
    public const JOIN_ROUND = 1;
    public const JOIN_BEVEL = 2;



    protected const units = [
        'pt' => 1,
        'mm' => 72 / 25.4,
        'cm' => 72 / 2.54,
        'in' => 72
    ];
    protected float $k = 72 / 25.4;

    protected const orientation = [
        'P' => true,
        'L' => true,
        'PORTRAIT' => true,
        'LANDSCAPE' => true
    ];

    protected const pageSizes = [
        'a3' => [21384 / 25.4, 30240 / 25.4],   // [297mm, 420mm],
        'a4' => [15120 / 25.4, 21384 / 25.4],   // [210mm, 297mm],
        'a5' => [10656 / 25.4, 15120 / 25.4],   // [148mm, 210mm],
        'letter' => [612, 792],
        'legal' => [612, 1008]
    ];
    protected array $defPageSize = [15120 / 25.4, 21384 / 25.4];   // 'a4'

    protected const fpdfWeight = [
        '' => [400, false],
        'b' => [700, false],
        'i' => [400, true],
        'bi' => [700, true],
        'ib' => [700, true]
    ];

    protected const commonWeight = [
        'thin' => 100,
        'extralight' => 200,
        'ultralight' => 200,
        'light' => 300,
        '' => 400,
        'normal' => 400,
        'regular' => 400,
        'medium' => 500,
        'demibold' => 600,
        'semibold' => 600,
        'bold' => 700,
        'extrabold' => 800,
        'ultrabold' => 800,
        'black' => 900,
        'heavy' => 900,
        'extrablack' => 950,
        'ultrablack' => 950
    ];


















    protected CatalogDictionary $root;
    protected $encrypt = null;
    protected ?DocumentInformationDictionary $info = null;
    protected ?Entry $id = null;

    protected ?DataStream $src = null;

    /**
     * internalState che verrà trasmesso in fase di scrittura delle pagine
     */
    protected InternalState $internalState;

    /**
     * Cambia la cartella di default dove cercare i font
     * 
     * @param string $path - path completo di dove cercare i font
     */
    static public function SetFontPath(string $path)
    {
        InternalState::SetFontPath($path);
    }

    /**
     * 
     */
    public function __construct(?string $file_data_or_orientation = null, ?string $unit = null, mixed $size = null)
    {
        // inizializzo lo stato di default
        $this->internalState = new InternalState;
        $this->internalState->lineCap = 2;            // mi adeguo al "2 J" di fpdf
        $this->internalState->lineWidth = 72 / 127;   // mi adeguo al "0.57 w" di fpdf (0.2mm = 0.2 * 72 / 25.4)

        // se non ho un file da leggere, creo un pdf vuoto
        if (!$file_data_or_orientation || $this->toOrientation($file_data_or_orientation)) {
            $this->root = new CatalogDictionary($this->internalState);
            if ($unit)
                $this->SetMeasureUnit($unit);
            if ($file_data_or_orientation || $size)
                $this->SetDefaultPageSize($file_data_or_orientation, $size);
            return;
        }

        // apro lo stream di dati
        $this->src = new DataStream($file_data_or_orientation);

        // leggo i 128 byte finali, in questo modo sono sicuro di avere le ultime tre righe
        $data = $this->src->ReadData(-128, 128);

        // cerca la stringa 'startxref'
        if (!preg_match_all('/(?:\r|\n)startxref(?:\r|\n)/', $data, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER)) {
            throw new PDFException('unable to find \'startxref\' value');
        }

        // leggo la riga successiva, che dovrebbe contenere l'offset dell'xref
        $matches = end($matches);
        $startxref = intval($this->src->ReadNextRow($matches[0][1] + 11 - strlen($data)));
        if (!$startxref) {
            throw new PDFException('unable to read \'startxref\' offset');
        }

        // verifico che la riga successiva sia l'%%EOF
        if ($this->src->ReadNextRow() !== '%%EOF') {
            throw new PDFException('last line of \'startxref\' block isn\'t \'%%EOF\'');
        }

        // estraggo la concatenazione di tutti gli xref presenti nel file
        $xref = $this->getMergedXRef($startxref);
        if ($xref === false) {
            throw new PDFException('invalid cross-reference data');
        }

        // legge le informazioni contenute nel "Document catalog dictionary"
        $this->root = new CatalogDictionary($this->internalState, $xref['Root']->GetFinalValue());

        // legge le informazioni contenute nell'"Encrypt"
        if (isset($xref['Encrypt'])) {
            throw new PDFException('TODO: leggere l\'encrypt');
        }

        // legge le informazioni contenute nel "Document information dictionary"
        if (isset($xref['Info'])) {
            $this->info = new DocumentInformationDictionary($this->internalState, $xref['Info']->GetFinalValue());
        }










        // libero la memoria (forse, perché probabilmente gli stream la tengono occupata)
        $this->src = null;
    }

    /**
     * Legge tutte le strutture con i riferimenti agli oggetti del PDF e restituisce l'elenco completo
     */
    private function getMergedXRef(int $start): PDFObjectDictionary
    {
        if (!$this->src) {
            throw new PDFException('DataStream already dismissed');
        }

        // leggo 4 byte per vedere se è un xref + trailer o un oggetto /XRef
        $row = $this->src->ReadNextRow($start);
        if ($row === false) {
            throw new PDFException('unable to read cross-reference data');
        }

        if (substr($row, 0, 4) === 'xref') {
            // finche non trovo la riga contenete il trailer, estraggo gli indici degli oggetti
            while (1) {
                $row = $this->src->ReadNextRow();
                if ($row === false) {
                    throw new PDFException('unexpected end of file');
                }

                if ($row[0] === '%') {
                    continue;
                }

                $row = trim($row);
                if ($row === 'trailer') {
                    break;
                }

                if (!preg_match('/^([0-9]+) ([0-9]+)\s*$/', $row, $m)) {
                    throw new PDFException('invalid cross-reference subsection');
                }

                $index = intval($m[1]);
                $cnt = intval($m[2]);
                while ($cnt) {
                    $row = $this->src->ReadNextRow();
                    if ($row === false) {
                        throw new PDFException('unexpected end of file');
                    }

                    if ($row[0] === '%') {
                        continue;
                    }

                    if (!preg_match('/^([0-9]{10}) ([0-9]{5}) ([n|f])\s*$/', $row, $m)) {
                        throw new PDFException('invalid cross-reference entry');
                    }

                    $this->src->AddXRefEntry($index, $m[3] === 'f' ? 0 : 1, intval($m[1]), intval($m[2]));

                    ++$index;
                    --$cnt;
                }
            }

            // a questo punto ho letto tutte le entry dell'xref, non mi resta che leggere il trailer (che è un dizionario)
            $trailer = $this->src->ReadObjectData('startxref');
            if ($trailer->GetType() !== IPDFObject::TYPE_DICTIONARY)
                throw new PDFException('invalid trailer: no dictionary found');
            /** @var PDFObjectDictionary $trailer */
        } else {
            $obj = $this->src->ReadObject($start);
            if ($obj->GetType() !== IPDFObject::TYPE_STREAM)
                throw new PDFException('invalid XRefObj: no stream found');
            /** @var PDFObjectDictionary $obj */

            $type = $obj['Type'];
            if (!$type || $type->GetFinalType() !== IPDFObject::TYPE_NAME || $type->GetFinalValue() !== 'XRef') {
                throw new PDFException('invalid XRefObj: dictionary Type field isn\'t \'XRef\'');
            }

            $size = $obj['Size'];
            if (!$size || $size->GetFinalType() !== IPDFObject::TYPE_INT) {
                throw new PDFException('invalid XRefObj: dictionary Size field isn\'t an integer');
            }

            // se il campo index non è definito parto dal valore 0, altrimenti estraggo le sezioni dall'Index
            $indexObj = $obj['Index'];
            $index = [[0, $size->GetFinalValue()]];
            if ($indexObj !== null) {
                if ($indexObj->GetFinalType() !== IPDFObject::TYPE_ARRAY || count($indexObj) % 2 !== 0) {
                    throw new PDFException('invalid XRefObj: dictionary Index isn\'t an array with even length');
                }

                $len = count($indexObj);
                $index = [];
                for ($i = 0; $i < $len; $i += 2) {
                    if ($indexObj[$i]->GetFinalType() !== IPDFObject::TYPE_INT || $indexObj[$i + 1]->GetFinalType() !== IPDFObject::TYPE_INT) {
                        throw new PDFException('invalid XRefObj: the Index array contains a non-integer value');
                    }

                    $start = $indexObj[$i]->GetFinalValue();
                    $index[] = [$start, $start + $indexObj[$i + 1]->GetFinalValue()];
                }
            }

            $wObj = $obj['W'];
            if (
                $wObj === null || $wObj->GetFinalType() !== IPDFObject::TYPE_ARRAY || count($wObj) !== 3
                || $wObj[0]->GetFinalType() !== IPDFObject::TYPE_INT
                || $wObj[1]->GetFinalType() !== IPDFObject::TYPE_INT
                || $wObj[2]->GetFinalType() !== IPDFObject::TYPE_INT
            ) {
                throw new PDFException('invalid XRefObj: the W array does not contain 3 integers');
            }
            $w = [
                $wObj[0]->GetFinalValue(),
                $wObj[1]->GetFinalValue(),
                $wObj[2]->GetFinalValue()
            ];

            $str = $obj->GetStream(true);
            $len = strlen($str);
            if ($len % ($w[0] + $w[1] + $w[2]) != 0) {
                throw new PDFException('invalid XRefObj: data block has an incorrect size');
            }

            $i = 0;
            $idx = 0;
            while ($i < $len) {
                while ($index[$idx][0] >= $index[$idx][1]) {
                    if ($idx++ >= count($index)) {
                        throw new PDFException('invalid XRefObj: data block contains more entry than specified in Index/Size');
                    }
                }

                $row = [];
                for ($j = 0; $j < 3; ++$j) {
                    if ($w[$j] === 0) {
                        $val = $j === 0 ? 1 : 0;
                    } else {
                        $val = 0;
                        for ($z = 0; $z < $w[$j]; ++$z) {
                            $val = $val * 256 + ord($str[$i++]);
                        }
                    }
                    $row[] = $val;
                }

                $this->src->AddXRefEntry($index[$idx][0]++, $row[0], $row[1], $row[2]);
            }

            // creo il trailer copiando gli elementi dall'XRefObj appena estratto
            $trailer = new PDFObjectDictionary();
            foreach ($obj as $k => $v) {
                if (in_array($k, ['Size', 'Prev', 'Root', 'Encrypt', 'Info', 'ID', 'XRefStm'])) {
                    $trailer[$k] = $v;
                }
            }
        }

        // se ci sono altri xref li processo
        if ($trailer['XRefStm'] !== null) {
            $this->getMergedXRef($trailer['XRefStm']->GetFinalValue());
        }

        if (isset($trailer['Prev'])) {
            $this->getMergedXRef($trailer['Prev']->GetFinalValue());
        }

        return $trailer;
    }

    /**
     * scrive il PDF
     */
    public function __toString(): string
    {
        // crea l'alberatura delle pagine dall'array pages
        $this->internalState->emptyResources();
        $this->root->Pages = new PagesDictionary(
            $this->internalState,
            null,
            $this->internalState->getPages() ?: [new PageDictionary($this->internalState, $this->defPageSize, 0)],
            new Rectangle($this->internalState, 0, 0, $this->defPageSize[0], $this->defPageSize[1])
        );

        // di default rotate è zero, quindi posso eliminarlo
        if ($this->root->Pages->Rotate == 0)
            unset($this->root->Pages->Rotate);

        // ???
        print 'TODO: eliminare tutti i box che prenderebbero i valori di default' . PHP_EOL;

        // riduce la dimensione delle risorse accorpando quelle uguali
        $this->internalState->shrinkResources();

        // crea la lista di tutti gli oggetti da includere nel pdf
        $objs = [];
        $ver = $this->root->appendObject($objs, false);
        if ($this->encrypt) {
            $v = $this->encrypt->appendObject($objs, false);
            if ($ver < $v) {
                $ver = $v;
            }
        }
        if ($this->info) {
            $v = $this->info->appendObject($objs, false);
            if ($ver < $v) {
                $ver = $v;
            }
        }

        // scrive l'intestazione del pdf
        $out = '%PDF-' . intval($ver / 1000) . '.' . ($ver % 1000) . "\r\n%\xab\xab\xbb\xbb\r\n";

        // scrive tutti gli oggetti nel file
        $len = strlen($out);
        $idx = 0;
        foreach ($objs as $o) {
            $part = $o->getPDFObject($ver, $len, ++$idx);
            $len += strlen($part);
            $out .= $part;
        }

        // scrivo il trailer
        //if ($ver >= 1005) {
        //    // dalla versione 1.5 scrivo un cross-reference streams (7.5.8 - 
        //    // pagina 65 del file ISO_32000-2-2020_sponsored.pdf)
        //    throw new PDFException('TODO: scrivere il cross-reference streams');
        //} else {
        // scrivo una cross-reference table (7.5.4 - pagina 55 del file 
        // ISO_32000-2-2020_sponsored.pdf)
        $objsNum = count($objs) + 1;
        $out .= "xref\r\n0 $objsNum\r\n0000000000 65535 f\r\n";
        $idx = 0;
        foreach ($objs as $o) {
            $out .= str_pad($o->getObjectOffset(++$idx), 10, '0', STR_PAD_LEFT) . " 00000 n\r\n";
        }
        $out .= "trailer\r\n<</Size $objsNum/Root " . $this->root->getObjectReference();
        if ($this->encrypt) {
            $out .= "/Encrypy " . $this->encrypt->getObjectReference();
        }
        if ($this->info) {
            $out .= "/Info " . $this->info->getObjectReference();
        }
        if ($this->encrypt || $ver >= 2000) {
            $this->crea_id();
        }
        if ($this->id) {
            $out .= "/ID[" . $this->id->getOutput(1000) . $this->id->getOutput(1000) . "]";
        }
        $out .= ">>";
        //}

        // scrive il riferimento all'xref
        $out .= "\r\nstartxref\r\n$len\r\n%%EOF";

        // a questo punto posso nuovamente eliminare l'alberatura delle pagine
        $this->root->Pages = null;

        // restituisce il pdf
        return $out;
    }

    public function removeViewerPreferences()
    {
        // elimina la viewerpreferences dal catalog
    }

    public function removeLogicalStructure()
    {
        // elimina la logicalstructure e la markinfo dal catalog (da verificare cosa si deve fare)
    }

    private function crea_id()
    {
        if (!$this->id) {
            $this->id = new Entry($this->internalState, [Entry::BYTE_STRING => true], true, 1000, 999999);
            $id = '';
            for ($i = 0; $i < 16; ++$i) {
                $id .= chr(rand(0, 255));
            }
            $this->id->setValue($id);
        }
    }

    public function toPDFA()
    {
        // crea l'id del file
        $this->crea_id();

        // altro...
    }

    public function esportaFont() {}











    /**
     * Da qui iniziano le funzioni aggiunte per gestire il PDF in modo simile all'FPDF
     */

    /**
     * Determina se una stringa è un termine usato per l'orientamento del foglio.
     * 
     * Restituisce 'P' per portrait, 'L' per landscape e false se non riconosce la stringa.
     * 
     * @param string $str - Orientation definition to convert
     * 
     * @return string|false
     */
    private function toOrientation(string $str): string|false
    {
        if (strlen($str) > 9)
            return false;
        $str = strtoupper($str);
        return array_key_exists($str, static::orientation) ? $str[0] : false;
    }

    /**
     * Converte un numero o un numero seguito da un'unita di misura nel valore da usare nel pdf per rappresentare quella misura.
     * 
     * @param float|string $str - Misura da convertire
     * @param float        $k   - Fattore di conversione se non si vuole usare quello impostato per la pagina
     * 
     * @return ?float
     */
    private function toMeasure(float|string $str, float $k = 0): ?float
    {
        if (is_numeric($str))
            return (float)$str * ($k ?: $this->k);

        $unit = substr($str, -2);
        if (!array_key_exists($unit, static::units))
            return null;

        $str = substr($str, 0, -2);
        return is_numeric($str) ? (float)$str / static::units[$unit] : null;
    }

    /**
     * Prova a convertire i parametri passati in un array bidimensionale contenete larghezza e altezza del foglio.
     * 
     * @param float|string|null       $width  - larghezza o un parametro per determinare la dimensione della pagina
     * @param float|string|array|null $height - altezza o un parametro per determinare la dimensione della pagina
     * 
     * @return array
     * 
     * @throws PDFException
     */
    private function toPageSize(float|string|null $width, float|string|array|null $height): array
    {
        $r = false;
        $w = $width !== null ? $this->toMeasure($width) : null;
        $h = $height !== null && !is_array($height) ? $this->toMeasure($height) : null;
        if ($w === null || $h === null) {
            if ($w !== null || $h !== null)
                throw new PDFException('invalid parameters: pass page width and height, or sheet type and orientation');

            $o = 'P';
            if ($width) {
                $lower = strtolower($width);
                if (array_key_exists($lower, static::pageSizes)) {
                    if ($height) {
                        $o = is_array($height) ? false : $this->toOrientation($height);
                        if (!$o)
                            throw new PDFException('invalid parameters: unknown sheet orientation (' . $height . ')');
                    }
                    $w = static::pageSizes[$lower][0];
                    $h = static::pageSizes[$lower][1];
                } else {
                    $o = $this->toOrientation($width);
                    if (!$o)
                        throw new PDFException('invalid parameters: unknown sheet type/orientation (' . $width . ')');
                }
            }

            if ($w === null) {
                if ($height) {
                    if (is_array($height)) {
                        if (count($height) != 2 || !is_numeric($height[0]) || !is_numeric($height[1]))
                            throw new PDFException('invalid parameters: size array must contain two numeric values');
                        $w = $height[0] * $this->k;
                        $h = $height[1] * $this->k;
                    } else {
                        $lower = strtolower($height);
                        if (!array_key_exists($height, static::pageSizes))
                            throw new PDFException('invalid parameters: unknown sheet type (' . $height . ')');
                        $w = static::pageSizes[$lower][0];
                        $h = static::pageSizes[$lower][1];
                    }
                } else {
                    $w = $this->defPageSize[0];
                    $h = $this->defPageSize[1];
                }
            }

            $r = $o == 'P' ? $h < $w : $w < $h;
        }

        if ($w <= 0 || $h <= 0)
            throw new PDFException('Invalid parameters: measures have to be positive numbers');

        return $r ? [$h, $w] : [$w, $h];
    }

    /**
     * trasforma proporzionalmente un numero intero compreso tra 0 e 255 in un float compreso tra 0 e 1
     * 
     * @param int $value - Numero intero da trasformare
     * 
     * @return ?float - Valore convertito
     */
    private function toColorValue(?int $value): ?float
    {
        if ($value === null)
            return null;

        if ($value <= 0)
            return 0.0;

        if ($value >= 255)
            return 1.0;

        return $value / 255.0;
    }

    /**
     * trasforma un array di interi compresi tra 0 e 255 in un array di float compresi tra 0 e 1
     * 
     * @param int|array|null $value - Array da trasformare
     * 
     * @return ?array - Valore convertito
     */
    private function toColorArray(int|array|null $value): ?array
    {
        if ($value === null)
            return null;

        if (!is_array($value)) {
            return [$this->toColorValue($value)];
        }

        $c = count($value);
        if (!$c)
            return null;

        if (($c != 1 && $c != 3) || !is_integer($value[0]))
            throw new PDFException('invalid color array');

        if ($c == 3)
            return [$this->toColorValue($value[0])];

        if (!is_integer($value[1]) || !is_integer($value[2]))
            throw new PDFException('invalid color array');

        $r = $this->toColorValue($value[0]);
        $g = $this->toColorValue($value[1]);
        $b = $this->toColorValue($value[2]);
        if ($r == 0 && $g == 0 && $b == 0)
            return [0.0];
        return [$r, $g, $b];
    }

    /**
     * Measure unit to use in all methods (except in SetFont that dafault is always 'pt')
     * 
     * @param string $unit - Measure unit to use ('pt', 'mm', 'cm', 'in')
     * 
     * @throws PDFException
     */
    public function SetMeasureUnit(string $unit): void
    {
        if (!array_key_exists($unit, static::units))
            throw new PDFException('unknown measure unit (' . $unit . ').');
        $this->k = static::units[$unit];
    }

    /**
     * Set the default page size
     * 
     * @param float|string|null $width  - page width or an orientation ('P', 'L') or a page format ('a4', 'letter' and so on)
     * @param float|string|null $height - page height or an orientation ('P', 'L') or a page format ('a4', 'letter' and so on)
     * 
     * @throws PDFException
     */
    public function SetDefaultPageSize(float|string|null $width, float|string|array|null $height = null): void
    {
        $this->defPageSize = $this->toPageSize($width, $height);
    }

    /**
     * Set the current page size
     * 
     * @param float|string|null $width  - page width or an orientation ('P', 'L') or a page format ('a4', 'letter' and so on)
     * @param float|string|null $height - page height or an orientation ('P', 'L') or a page format ('a4', 'letter' and so on)
     * 
     * @throws PDFException
     */
    public function SetPageSize(float|string|null $width, float|string|array|null $height = null): void
    {
        // in questo modo verifica prima l'esistenza della pagina
        $page = $this->internalState->getCurrentPage();
        $page->SetPageSize($this->toPageSize($width, $height));
    }

    /**
     * Set the current page rotation
     * 
     * @param int $rotate - page rotation, shall be a multiple of 90
     * 
     * @throws PDFException
     */
    public function SetPageRotation(int $rotate): void
    {
        // in questo modo verifica prima l'esistenza della pagina
        $page = $this->internalState->getCurrentPage();

        if ($rotate % 90 != 0)
            throw new PDFException('incorrect rotation value (' . $rotate . ')');

        $page->Rotate = $rotate % 360;
    }

    /**
     * Add a new page as last one
     * 
     * @param string $orientation - Page orientation, 'P' portrait or 'L' landscape
     * @param mixed  $size        - Page size, [width, height] array or a standard format ('a4, 'letter' and so on)
     * @param int    $rotate      - Page rotation in degrees (multiple of 90)
     * 
     * @throws PDFException
     */
    public function AddPage(?string $orientation = null, mixed $size = null, ?int $rotate = null): void
    {
        $this->InsertPage(-1, $orientation, $size, $rotate);
    }

    /**
     * Insert a new page at chosen position
     * 
     * @param int    $page_num    - Position to insert the new page
     * @param string $orientation - Page orientation, 'P' portrait or 'L' landscape
     * @param mixed  $size        - Page size, [width, height] array or a standard format ('a4, 'letter' and so on)
     * @param int    $rotate      - Page rotation in degrees (multiple of 90)
     * 
     * @throws PDFException
     */
    public function InsertPage(int $page_num, ?string $orientation = null, mixed $size = null, ?int $rotate = null): void
    {
        if ($page_num <= 0 && $page_num != -1)
            throw new PDFException('invalid parameters: page numbers start from 1');

        $size = $orientation || $size ? $this->toPageSize($orientation, $size) : $this->defPageSize;
        if ($rotate) {
            if ($rotate % 90 != 0)
                throw new PDFException('incorrect rotation value (' . $rotate . ')');
            $rotate %= 360;
        }

        $this->internalState->insertPage(new PageDictionary($this->internalState, $size, $rotate ?: 0), $page_num);
    }

    /**
     * get number of pages in PDF
     * 
     * @return int - Number of pages
     */
    public function CountPages(): int
    {
        return $this->internalState->countPages();
    }

    /**
     * Choose the page to write on
     * 
     * @param int $page_num - Number of page to write on
     */
    public function GoToPage(int $page_num): void
    {
        $this->internalState->goToPage($page_num);
    }

    /**
     * Fatal error
     * 
     * @param string         $message  - message to display as error
     * @param Throwable|null $previous - previous exception that generate the fatal error
     * 
     * @throws PDFException
     */
    function Error(string $message, Throwable|null $previous = null)
    {
        throw new PDFException($message, 0, $previous);
    }

    /*

AcceptPageBreak - ammette o meno l'interruzione di pagina automatico
AddFont - aggiunge un nuovo font
AddLink - crea un link interno
AliasNbPages - definisce un alias per il numero di pagine
Cell - stampa una cella
Close - chiude il documento
Footer - piè di pagina
GetPageHeight - restituisce l'altezza della pagina corrente
GetPageWidth - restituisce la larghezza della pagina corrente
GetStringWidth - calcola la lungheza di una stringa
GetX - calcola la posizione corrente di x
GetY - calcola la posizione corrente di y
Header - intestazione della pagina
Image - disegna un'immagine
Link - inserisce un link
Ln - interruzione di linea
MultiCell - stampa del testo con interruzioni di linea
Output - salva o invia il documento
PageNo - numero di pagina
SetAuthor - imposta l'autore del documento
SetAutoPageBreak - imposta la modalità di interruzione di pagina automatica
SetCompression - attiva o disattiva la compressione
SetCreator - imposta il creatore del document
SetDisplayMode - imposta la modalità di visualizzazione
SetFont - imposta il font
SetFontSize - imposta la dimensione del font
SetKeywords - associa keywords al documento
SetLeftMargin - imposta il margine sinistro
SetLink - imposta la destinazione di un link interno
SetMargins - imposta i margini
SetRightMargin - imposta il margine destro
SetSubject - imposta il soggetto del documento
SetTitle - imposta il titolo del documento
SetTopMargin - imposta il margine superiore
SetX - imposta la posizione corrente di x
SetXY - imposta le posizioni correnti di x e y
SetY - imposta la posizione corrente di y
Text - stampa una stringa
Write - stampare testo continuo

*/
    /**
     * SetDrawColor - Imposta il colore delle linee
     * 
     * @param int  $r - valore del colore rosso o della gradazione di grigio
     * @param ?int $g - valore del colore verde o null se l'immagine è in bianco e nero
     * @param ?int $b - valore del colore blu o null se l'immagine è in bianco e nero
     */
    public function SetDrawColor(int $r, ?int $g = null, ?int $b = null)
    {
        $rf = $this->toColorValue($r);
        $gf = $this->toColorValue($g);
        $bf = $this->toColorValue($b);
        if (($rf == 0 && $gf == 0 && $bf == 0) || $gf === null || $bf === null)
            $this->internalState->drawColor = [$rf];
        else
            $this->internalState->drawColor = [$rf, $gf, $bf];
    }

    /**
     * SetFillColor - imposta il colore di riempimento
     * 
     * @param int  $r - valore del colore rosso o della gradazione di grigio
     * @param ?int $g - valore del colore verde o null se l'immagine è in bianco e nero
     * @param ?int $b - valore del colore blu o null se l'immagine è in bianco e nero
     */
    public function SetFillColor($r, $g = null, $b = null)
    {
        $rf = $this->toColorValue($r);
        $gf = $this->toColorValue($g);
        $bf = $this->toColorValue($b);
        if (($rf == 0 && $gf == 0 && $bf == 0) || $gf === null || $bf === null)
            $this->internalState->fillColor = [$rf];
        else
            $this->internalState->fillColor = [$rf, $gf, $bf];
    }

    /**
     * SetTextColor - imposta il colore del testo
     * 
     * @param ?int $r - valore del colore rosso o della gradazione di grigio
     * @param ?int $g - valore del colore verde o null se l'immagine è in bianco e nero
     * @param ?int $b - valore del colore blu o null se l'immagine è in bianco e nero
     */
    public function SetTextColor(?int $r, ?int $g = null, ?int $b = null)
    {
        $rf = $this->toColorValue($r);
        $gf = $this->toColorValue($g);
        $bf = $this->toColorValue($b);
        if ($rf === null)
            $this->internalState->textColor = null;
        elseif (($rf == 0 && $gf == 0 && $bf == 0) || $gf === null || $bf === null)
            $this->internalState->textColor = [$rf];
        else
            $this->internalState->textColor = [$rf, $gf, $bf];
    }

    /**
     * SetTextRenderingMode - imposta il colore del testo
     * 
     * @param int|array|null    $fillColor        - colore da usare per il riempimento dei caratteri, se null il font sarà trasparente
     * @param float|string      $borderWidth      - spessore del bordo da disegnare attorno al carattere, se 0 non verrà disegnato
     * @param int|array|null    $borderColor      - colore del bordo, se null non verrà disegnato
     * @param ?int              $borderJoin       - tipo di join da usare per le linee, se null verrà usato quello del draw
     * @param float|string|null $borderMiterLimit - miterLimit usato per disegnare il bordo, se null verrà usato quello del draw
     */
    public function SetTextRenderingMode(int|array|null $fillColor, float|string $borderWidth, int|array|null $borderColor, ?int $borderJoin = null, float|string|null $borderMiterLimit = null)
    {
        $fc = $this->toColorArray($fillColor);
        $bw = $this->toMeasure($borderWidth);
        if ($bw === null)
            throw new PDFException('invalid parameters: unable to convert borderWidth to valid measurement.');
        $bc = $this->toColorArray($borderColor);
        if ($borderJoin < 0 || $borderJoin > 2)
            throw new PDFException('invalid parameters: unknow join type.');
        $ml = $this->toMeasure($borderMiterLimit);
        if ($ml === null)
            throw new PDFException('invalid parameters: unable to convert borderMiterLimit to valid measurements.');

        $this->internalState->textColor = $fc;
        $this->internalState->textStroking = [$bw, $bc, $borderJoin, $ml];
    }

    /**
     * SetLineWidth - imposta lo spessore delle linee
     * 
     * @param float|string $width - spessore della linea espresso come numero o come numero e unità di misura
     * 
     * @throws PDFException
     */
    public function SetLineWidth(float|string $width)
    {
        $w = $this->toMeasure($width);
        if ($w === null)
            throw new PDFException('invalid parameters: unable to convert width to valid measurement.');
        $this->internalState->lineWidth = $w;
    }

    public function SetLineCap(int $cap)
    {
        if ($cap < 0 || $cap > 2)
            throw new PDFException('invalid parameters: unknow cap type.');
        $this->internalState->lineCap = $cap;
    }

    public function SetLineJoin(int $join, float|string $miterLimit = 0)
    {
        if ($join < 0 || $join > 2)
            throw new PDFException('invalid parameters: unknow join type.');
        $ml = $this->toMeasure($miterLimit);
        if ($ml === null)
            throw new PDFException('invalid parameters: unable to convert miterLimit to valid measurements.');
        $this->internalState->lineJoin = $join;
        $this->internalState->miterLimit = $ml;
    }

    /**
     * Line - traccia una linea da un punto ad un altro
     * 
     * @param float|string $x1 - posizione x del punto iniziale
     * @param float|string $y1 - posizione y del punto iniziale
     * @param float|string $x2 - posizione x del punto finale
     * @param float|string $y2 - posizione y del punto finale
     * 
     * @throws PDFException
     */
    public function Line(float|string $x1, float|string $y1, float|string $x2, float|string $y2)
    {
        // in questo modo verifica prima l'esistenza della pagina
        $page = $this->internalState->getCurrentPage();

        $mx = $this->toMeasure($x1);
        $my = $this->toMeasure($y1);
        $lx = $this->toMeasure($x2);
        $ly = $this->toMeasure($y2);
        if ($mx === null || $my === null || $lx === null || $ly === null)
            throw new PDFException('invalid parameters: unable to convert one or more values to valid measurements.');

        $page->MoveTo($mx, $my);
        $page->LineTo($lx, $ly);
        $page->Stroke();
    }

    /**
     * Rect - disegna un rettangolo
     * 
     * @param float|string $x     - posizione x del punto iniziale
     * @param float|string $y     - posizione y del punto iniziale
     * @param float|string $w     - larghezza del rettangolo
     * @param float|string $h     - altezza del rettangolo
     * @param string       $style - tipo di rettangolo da disegnare: D disegna solo il contorno (default), F riempie l'area, FD o DF entrambi 
     * 
     * @throws PDFException
     */
    public function Rect(float|string $x, float|string $y, float|string $w, float|string $h, string $style = '')
    {
        // in questo modo verifica prima l'esistenza della pagina
        $page = $this->internalState->getCurrentPage();

        $rx = $this->toMeasure($x);
        $ry = $this->toMeasure($y);
        $rw = $this->toMeasure($w);
        $rh = $this->toMeasure($h);
        if ($rx === null || $ry === null || $rw === null || $rh === null)
            throw new PDFException('invalid parameters: unable to convert one or more values to valid measurements.');

        $page->Rect($rx, $ry, $rw, $rh);
        if ($style == 'F')
            $page->Fill();
        elseif ($style == 'FD' || $style == 'DF')
            $page->FillAndStroke();
        elseif ($style === '' || $style == 'F')
            $page->Stroke();
        else
            throw new PDFException('invalid parameters: unknown style.');
    }









    /**
     * funzione che converte uno style in weight e italic
     * 
     * Lo style può essere:
     * - una qualsiasi combinazione di 'B' (grassetto), 'I' (corsivo), 'U' (sottolineato) e 'S' (barrato)
     * - uno dei common weight di OpenType ('Thin', 'ExtraLight', 'UltraLight', 'Light', 'Normal', 'Regular', 'Medium',
     *   'DemiBold', 'SemiBold', 'Bold', 'ExtraBold', 'UltraBold', 'Black', 'Heavy', 'ExtraBlack', 'UltraBlack')
     *   seguito o no dalla parola 'Italic'
     * - un numero da 1 a 1000 seguito da una qualsiasi combinazione di 'I' (corsivo), 'U' (sottolineato) e 'S'
     *   (barrato)
     */
    private function convertiFontStyle(string|int $style): array
    {
        $italic = 'n';
        $decoration = '';
        if (!is_integer($style)) {
            $style = strtolower($style);

            // verifico i common weight
            if (str_ends_with($style, 'italic')) {
                $italic = 'i';
                $style = substr($style, 0, -6);
            }

            if (array_key_exists($style, static::commonWeight))
                return [static::commonWeight[$style], $italic, null];

            $weight = 400;
            if (strpos($style, 'b') !== false) {
                $weight = 700;
                $style = str_replace('b', '', $style);
            }

            if (strpos($style, 'i') !== false) {
                $italic = 'i';
                $style = str_replace('i', '', $style);
            }

            if (strpos($style, 's') !== false) {
                $decoration = 'S';
                $style = str_replace('s', '', $style);
            }

            if (strpos($style, 'u') !== false) {
                $decoration = 'U';
                $style = str_replace('u', '', $style);
            }

            if ($style === '')
                return [$weight, $italic, $decoration];

            $style = intval($style);
        }

        if ($style <= 0 || $style > 1000)
            throw new PDFException('invalid parameters: unknown style.');
        return [$style, $italic, $decoration];
    }

    /**
     * Aggiunge un font nel formato dell'fpdf. Per caricare file TrueType senza
     * pre-processarli di deve usare la funzione AddTrueTypeFont.
     */
    public function AddFont(string $family, string $style = '', string $file = '', string $dir = '')
    {
        // Add a TrueType, OpenType or Type1 font
        $family = strtolower($family);
        if ($file == '')
            $file = str_replace(' ', '', $family) . strtolower($style) . '.json';
        if ($dir != '')
            $file = rtrim($dir, '/\\') . '/' . $file;
        $this->AddTrueTypeFont($file, $family, $style);
    }

    /**
     * Aggiunge un font TrueType o OpenType al pdf. Estrapola i vari valori dal
     * file del font, ma se si vogliono modificare è possibile forzarli
     * passando il nome di un family o uno style
     * 
     * Il file puà essere il nome di un file (in questo caso viene cercato
     * nella directory di default), o un path completo.
     * 
     * Il family può essere:
     * - null, in questo caso il nome della famiglia verrà estratto dal file
     *   del font
     * - un testo che sarà usato come nome della famiglia del font
     * 
     * Lo style può essere:
     * - null, in questo caso il peso del font verrà estratto dal file del font
     * - uno dei valori accettati da fpdf ('', 'B', 'I', 'BI', 'IB')
     * - uno dei common weight di OpenType ('Thin', 'ExtraLight', 'UltraLight',
     *   'Light', 'Normal', 'Regular', 'Medium', 'DemiBold', 'SemiBold', 
     *   'Bold', 'ExtraBold', 'UltraBold', 'Black', 'Heavy', 'ExtraBlack',
     *   'UltraBlack'). Se il nome è seguito della parola 'Italic', si passa
     *   alla variante in corsivo
     * - un numero da 1 a 1000. Se seguito dalla lettera 'I' si passa alla
     *   variante in corsivo
     */
    public function AddTrueTypeFont(string $file, ?string $family = null, string|int|null $style = null)
    {
        $family = trim($family ?: '');

        if ($style !== null)
            [$weight, $style, $decoration] = $this->convertiFontStyle($style ?: '');
        else
            $weight = null;

        $this->internalState->addFont($file, $family ? trim($family) : null, $style, $weight);
    }

    /**
     * Imposta il font per la scrittura
     */
    public function SetFont(string $family, string|int $style = '', float|string|null $size = 0)
    {
        if ($family == '')
            $family = $this->internalState->fontFamily;
        else
            $family = strtolower($family);

        // converte lo style in spessore, corsivo e decorazione
        [$weight, $style, $decoration] = $this->convertiFontStyle($style);

        // converte la dimensione in un valore utilizzabile
        if (!$size)
            $size = $this->internalState->fontSize;
        else {
            $size = $this->toMeasure($size, 1);
            if (!$size)
                throw new PDFException('invalid parameters: unable to convert size to valid measurement.');
        }

        $this->internalState->setFont($family, $style, $weight, $size, $decoration);
    }

    public function SetFontSize(float|string $size = 0)
    {
        $size = $this->toMeasure($size, 1);
        if (!$size)
            throw new PDFException('invalid parameters: unable to convert size to valid measurement.');
        $this->internalState->fontSize = $size;
    }

    public function SetFontWeight(string|int $weight)
    {
        if (is_string($weight)) {
            switch (strtolower($weight)) {
                case 'normal':
                    $weight = 400;
                    break;

                case 'bold':
                    $weight = 700;
                    break;

                case 'lighter':
                    if ($this->internalState->fontWeight <= 550)
                        $weight = 100;
                    elseif ($this->internalState->fontWeight <= 750)
                        $weight = 400;
                    else
                        $weight = 700;
                    break;

                case 'bolder':
                    if ($this->internalState->fontWeight > 550)
                        $weight = 900;
                    elseif ($this->internalState->fontWeight > 350)
                        $weight = 700;
                    else
                        $weight = 400;
                    break;

                default:
                    $weight = intval($weight);
                    if (!$weight)
                        throw new PDFException('invalid parameters: unknown weight descriprion.');
                    break;
            }
        }

        if ($weight <= 0 || $weight > 1000)
            throw new PDFException('invalid parameters: invalid weight value.');

        $this->internalState->setFontWeight($weight);
    }

    public function SetFontStyle(string $style)
    {
        switch (strtolower($style)) {
            case '':
            case 'n':
            case 'normal':
                $style = 'n';
                break;

            case 'i':
            case 'italic':
                $style = 'i';
                break;

            default:
                throw new PDFException('invalid parameters: unknown style descriprion.');
        }

        $this->internalState->setFontStyle($style);
    }

    public function SetLineHeight(string|int $height)
    {
        if (is_numeric($height)) {
            $this->internalState->lineHeight = [(float)$height, 'rel'];
        } else {
            $height = $this->toMeasure($height, 1);
            if (!$height)
                throw new PDFException('invalid parameters: unable to convert height to valid measurement.');
            $this->internalState->lineHeight = [$height, 'abs'];
        }
    }

    public function SetTextDecoration(?string $decoration)
    {
        if (!$decoration) {
            $this->internalState->textDecoration = null;
            return;
        }
        switch (strtolower($decoration)) {
            case '':
            case 'none':
                $this->internalState->textDecoration = null;
                return;

            case 'u':
            case 'underline':
                $this->internalState->textDecoration = 'u';
                return;

            //case 'o':
            //case 'overline':
            //    $this->internalState->textDecoration = 'o';
            //    return;

            case 's':
            case 'line-through':
            case 'strikethrough':
                $this->internalState->textDecoration = 's';
                return;

                //text-decoration-line: blink;
                //text-decoration-line: spelling-error;
                //text-decoration-line: grammar-error;
        }
    }












    /**
     * Text - Stampa una stringa di caratteri.
     * L'origine è alla sinistra del primo carattere, sulla baseline. 
     * 
     * @param float|string $x   - posizione x del punto iniziale
     * @param float|string $y   - posizione y del punto iniziale
     * @param ?string      $txt - testo da scrivere
     * 
     * @throws PDFException
     */
    public function Text(float|string $x, float|string $y, ?string $txt)
    {
        // in questo modo verifica prima l'esistenza della pagina
        $page = $this->internalState->getCurrentPage();

        if (!$txt)
            return;

        $x = $this->toMeasure($x);
        $y = $this->toMeasure($y);
        if ($x === null || $y === null)
            throw new PDFException('invalid parameters: unable to convert one or more values to valid measurements.');

        $page->PlainText($x, $y, $txt);
    }
}
