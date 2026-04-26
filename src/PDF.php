<?php

namespace Boajr\PDF;

use Boajr\PDF\Objects\CatalogDictionary;
use Boajr\PDF\Objects\DocumentInformationDictionary;
use Boajr\PDF\Objects\Entry;
use Boajr\PDF\Objects\InternalState;
use Boajr\PDF\Objects\IResourcesWriter;
use Boajr\PDF\Objects\PageDictionary;
use Boajr\PDF\Objects\PagesDictionary;
use Boajr\PDF\Objects\Rectangle;
use Boajr\PDF\Parser\DataStream;
use Boajr\PDF\Parser\IPDFObject;
use Stringable;
use Throwable;


class PDF implements Stringable
{
    protected const units = [
        'pt' => 1,
        'mm' => 72 / 25.4,
        'cm' => 72 / 2.54,
        'in' => 72
    ];
    protected $k = 72 / 25.4;

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

    protected $defPageSize = [15120 / 25.4, 21384 / 25.4];   // 'a4'

    protected $curPage = null;













    protected CatalogDictionary $root;
    protected $encrypt = null;
    protected ?DocumentInformationDictionary $info = null;
    protected $id = null;

    protected ?DataStream $src = null;

    /**
     * array contenente tutte le pagine del PDF, in fase di output devo creare il PagesDictionary e inserirci questo 
     * array
     */
    protected array $pages = [];

    /**
     * internalState che verrà trasmesso in fase di scrittura delle pagine
     */
    protected InternalState $internalState;

    /**
     * array con chiave gli hash delle risorse utilizzate dalle varie pagine e come valore l'elenco delle pagine che
     * utilizzano quella risorsa. Serve in output per eliminare le risorse doppie.
     */
    protected array $resources = [];

    /**
     * array con tutti i font usati nelle varie pagine e oggetti
     */
    protected array $fonts = [];

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
            $this->root = new CatalogDictionary($this);
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
        $this->root = new CatalogDictionary($this, $xref['Root']->GetFinalValue());

        // legge le informazioni contenute nell'"Encrypt"
        if (isset($xref['Encrypt'])) {
            throw new PDFException('TODO: leggere l\'encrypt');
        }

        // legge le informazioni contenute nel "Document information dictionary"
        if (isset($xref['Info'])) {
            $this->info = new DocumentInformationDictionary($this, $xref['Info']->GetFinalValue());
        }










        // libero la memoria (forse, perché probabilmente gli stream la tengono occupata)
        $this->src = null;
    }

    /**
     * Legge tutte le strutture con i riferimenti agli oggetti del PDF e restituisce l'elenco completo
     */
    private function getMergedXRef(int $start): IPDFObject
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
            if ($trailer->GetType() !== IPDFObject::TYPE_DICTIONARY) {
                throw new PDFException('invalid trailer: no dictionary found');
            }
        } else {
            $obj = $this->src->ReadObject($start);
            if ($obj->GetType() !== IPDFObject::TYPE_STREAM) {
                throw new PDFException('invalid XRefObj: no stream found');
            }

            $type = $obj['Type'];
            if (!$type || $type->GetFinalValue() !== 'XRef') {
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

            /**
             * @var PDFObjectDictionary $obj
             */
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
            $trailer = new Parser\PDFObjectDictionary();
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
     * NON CANCELLARE!!!
     * 
     * aggiunge una pagina all'array delle pagine, senza verifcare nulla. Funzione chiamata dal PagesDictionary tramite
     * Reflection
     */
    private function pushPage(PageDictionary $page)
    {
        $this->pages[] = $page;
    }

    /**
     * NON CANCELLARE!!!
     * 
     * aggiunge l'hash di una risorsa all'array delle risorse. Funzione chiamata dal PagesDictionary (e altre classi) 
     * tramite Reflection
     */
    private function pushResources(IResourcesWriter $writer)
    {
        $hash = $writer->getResourcesHash(true);
        if (array_key_exists($hash, $this->resources))
            $this->resources[$hash][] = $writer;
        else
            $this->resources[$hash] = [$writer];
    }

    /**
     * NON CANCELLARE!!!
     * 
     * restituisce l'elenco delle pagine che utilizzano una certa risosrsa. Funzione chiamata dal ResourcesDictionary 
     * tramite Reflection
     */
    private function getIResourcesWriters(string $hash): array
    {
        if (array_key_exists($hash, $this->resources))
            return $this->resources[$hash];
        throw new PDFException('resources hash not found');
    }

    /**
     * NON CANCELLARE!!!
     * 
     * aggiunge una singola risorsa alle risorse globali
     */
    private function pushSingleResource(string $type, object $obj): array
    {
        if ($type == 'font') {
            $hash = $obj->getHash();
            if (array_key_exists($hash, $this->fonts))
                return $this->fonts[$hash];
            $f = [count($this->fonts) + 1, $obj];
            $this->fonts[$hash] = $f;
            return $f;
        }

        throw new PDFException('unkwown resources type');
    }







    /**
     * scrive il PDF
     */
    public function __toString(): string
    {
        // crea l'alberatura delle pagine dall'array pages
        $this->resources = [];
        $this->root->Pages = new PagesDictionary(
            $this,
            null,
            count($this->pages) > 0 ? $this->pages : [new PageDictionary($this, $this->defPageSize, 0)],
            new Rectangle($this, 0, 0, $this->defPageSize[0], $this->defPageSize[1])
        );

        // di default rotate è zero, quindi posso eliminarlo
        if ($this->root->Pages->Rotate == 0)
            unset($this->root->Pages->Rotate);

        // TODO: eliminare tutti i box che prenderebbero i valori di default

        // TODO: creare una struttura con tutte le risorse usate dalle pagine e renderle univoche
        foreach ($this->resources as $k => $r) {
            print $k . PHP_EOL;
            foreach ($r as $a => $b)
                print '  ' . $a . ' ' . get_class($b) . PHP_EOL;
        }
        //print get_class($r) . PHP_EOL;
        //print_r($resources);



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
            $this->id = new Entry($this, [Entry::BYTE_STRING => true], true, 1000, 999999);
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
     * 
     * @return ?float
     */
    private function toMeasure(float|string $str): ?float
    {
        if (is_numeric($str))
            return (float)$str * $this->k;

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
        if (!$this->curPage)
            throw new PDFException('No page selected');

        $this->curPage->SetPageSize($this->toPageSize($width, $height));
    }

    /**
     * Set the current page rotation
     * 
     * @param float|string|null $width  - page width or an orientation ('P', 'L') or a page format ('a4', 'letter' and so on)
     * @param float|string|null $height - page height or an orientation ('P', 'L') or a page format ('a4', 'letter' and so on)
     * 
     * @throws PDFException
     */
    public function SetPageRotation(int $rotate): void
    {
        if (!$this->curPage)
            throw new PDFException('No page selected');

        if ($rotate % 90 != 0)
            throw new PDFException('incorrect rotation value (' . $rotate . ')');

        $this->curPage->Rotate = $rotate % 360;
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

        $this->curPage = new PageDictionary($this, $size, $rotate ?: 0);
        if ($page_num == -1 || $page_num > count($this->pages))
            $this->pages[] = $this->curPage;
        else
            array_splice($this->pages, $page_num - 1, 0, [$this->curPage]);
    }

    /**
     * get number of pages in PDF
     * 
     * @return int - Number of pages
     */
    public function CountPages(): int
    {
        return count($this->pages);
    }

    /**
     * Choose the page to write on
     * 
     * @param int $page_num - Number of page to write on
     */
    public function GoToPage(int $page_num): void
    {
        if ($page_num <= 0 || $page_num > count($this->pages))
            throw new PDFException('page doesn\'t exist');

        $this->curPage = $this->pages[$page_num - 1];
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
     * @param int  $r - valore del colore rosso o della gradazione di grigio
     * @param ?int $g - valore del colore verde o null se l'immagine è in bianco e nero
     * @param ?int $b - valore del colore blu o null se l'immagine è in bianco e nero
     */
    public function SetTextColor($r, $g = null, $b = null)
    {
        $rf = $this->toColorValue($r);
        $gf = $this->toColorValue($g);
        $bf = $this->toColorValue($b);
        if (($rf == 0 && $gf == 0 && $bf == 0) || $gf === null || $bf === null)
            $this->internalState->textColor = [$rf];
        else
            $this->internalState->textColor = [$rf, $gf, $bf];
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
        if (!$this->curPage)
            throw new PDFException('No page selected');

        $mx = $this->toMeasure($x1);
        $my = $this->toMeasure($y1);
        $lx = $this->toMeasure($x2);
        $ly = $this->toMeasure($y2);
        if ($mx === null || $my === null || $lx === null || $ly === null)
            throw new PDFException('invalid parameters: unable to convert one or more values to valid measurements.');

        $this->curPage->MoveTo($mx, $my);
        $this->curPage->LineTo($lx, $ly);
        $this->curPage->Stroke();
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
        if (!$this->curPage)
            throw new PDFException('No page selected');

        $rx = $this->toMeasure($x);
        $ry = $this->toMeasure($y);
        $rw = $this->toMeasure($w);
        $rh = $this->toMeasure($h);
        if ($rx === null || $ry === null || $rw === null || $rh === null)
            throw new PDFException('invalid parameters: unable to convert one or more values to valid measurements.');

        $this->curPage->Rect($rx, $ry, $rw, $rh);
        if ($style == 'F')
            $this->curPage->Fill();
        elseif ($style == 'FD' || $style == 'DF')
            $this->curPage->FillAndStroke();
        elseif ($style === '' || $style == 'F')
            $this->curPage->Stroke();
        else
            throw new PDFException('invalid parameters: unknown style.');
    }
}
