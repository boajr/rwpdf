<?php

namespace Boajr\PDF\Objects;

use Boajr\PDF\font\Font;
use Boajr\PDF\PDFException;
use Throwable;

//use Boajr\PDF\Parser\PDFObjectDictionary;


/**
 * Parametri di default da usare nelle varie pagine
 */
class InternalState
{
    /**
     * Draw Color
     * Colore usato per disegnare le linee. Viene convertito nello stroking
     * color nelle operazione di grafica
     */
    public array $drawColor = [0];

    /**
     * Fill Color
     * Colore usato per riempire le figure. Viene convertito nel filling color
     * nelle operazioni di grafica
     */
    public array $fillColor = [0];

    /**
     * Text Color
     * Colore usato per i testi. Viene convertito nel filling color quando si
     * scrive testo e nello stroking color se serve anche sottolinearlo
     */
    public ?array $textColor = [0];

    /**
     * Text Stroke
     * Spessore della linea e colore usati per i contorni dei testi. Viene
     * convertito nello stroking color quando si scrive testo
     * 
     * I quattro parametri width, color, lineJoin e miterLimit
     */
    public array $textStroking = [0, null, null, null];



    /**
     * Font Family
     * Nome del font da usare quando si scrive del testo, deve essere uno dei valori inseriti come primo livello
     * dell'array dei font
     */
    public string $fontFamily = 'times';

    /**
     * Font Size
     * Dimensione del font da usare quando si scrive del testo
     */
    public float $fontSize = 10.0;

    /**
     * Font Weight
     * Spessore del font da usare quando si scrive del testo, deve essere uno dei valori inseriti come secondo livello
     * dell'array dei font in corrispondenza del primo livello $fontFamily
     */
    public int $fontWeight = 400;

    /**
     * Font Width
     * proporzione tra altezza e larghezza del font. è il modo che usano i PDF per rappresentare i font condensed ecc.
     */
    public float $fontWidth = 100.0;

    /**
     * Font Italic
     * indica se il font da utilizzare è normale (n) o in corsivo (i)
     */
    public string $fontStyle = 'n';

    /**
     * Line Height
     * spazio da tenere tra una riga di testo e un'altra
     */
    public array $lineHeight = [1.25, 'rel'];

    /**
     * Letter Spacing
     * spazio da aggiungere o sottrarre tra una lettere ed un'altra di una parola
     */
    public float $letterSpacing = 0;

    /**
     * Text Decoration
     * indica se il testo deve essere sottolineato (U - underline) o barrato (S - strikethrough)
     */
    public ?string $textDecoration = null;

    /**
     * Text Rise
     * spazio da aggiungere alla posizione verticale del testo
     */
    public float $textRise = 0;

    /**
     * Word Spacing
     * spazio da aggiungere o sottrarre tra una parola ed un'altra
     */
    public float $wordSpacing = 0;



    /**
     * Line Width (8.4.3.2)
     * spessore della linea da disegnare
     */
    public float $lineWidth = 1.0;

    /**
     * Line cap styles (8.4.3.3)
     * Specifica le forma delle terminazioni delle linee non chiuse, sia nei percorsi aperti, sia nei segmenti delle 
     * linee tratteggiate
     */
    public int $lineCap = 0;

    /**
     * Line join styles (8.4.3.4)
     * Specifica come viene disegnata la congiunzione tra due linee consecutive
     */
    public int $lineJoin = 0;

    /**
     * Miter limit (8.4.3.5)
     * distanza massima in cui la congiunzione miter si trasforma in bevel
     */
    public float $miterLimit = 10.0;





    /**
     * array contenente tutte le pagine del PDF, in fase di output devo creare il PagesDictionary e inserirci questo 
     * array
     */
    private array $pages = [];

    /**
     * pagina su cui si sta scrivendo/disegnando
     */
    private ?PageDictionary $curPage = null;

    /**
     * array con chiave gli hash delle risorse utilizzate dalle varie pagine e come valore l'elenco delle pagine che
     * utilizzano quella risorsa. Serve in output per eliminare le risorse doppie.
     */
    private array $resources = [];

    /**
     * directory di base dove cercare i file dei font da caricare
     */
    protected static ?string $fontpath = null;

    /**
     * numero di font già aggiunti all'output
     */
    private int $fontNumber = 0;

    /**
     * array con i 14 font standard da includere sempre
     */
    private array $std_14_fonts = [
        'courier' => [
            'n' => [
                400 => 'Courier',
                700 => 'Courier-Bold',
            ],
            'i' => [
                400 => 'Courier-Oblique',
                700 => 'Courier-BoldOblique',
            ],
        ],
        'helvetica' => [
            'n' => [
                400 => 'Helvetica',
                700 => 'Helvetica-Bold',
            ],
            'i' => [
                400 => 'Helvetica-Oblique',
                700 => 'Helvetica-BoldOblique',
            ],
        ],
        'times' => [
            'n' => [
                400 => 'Times-Roman',
                700 => 'Times-Bold',
            ],
            'i' => [
                400 => 'Times-Italic',
                700 => 'Times-BoldItalic',
            ],
        ],
        'symbol' => [
            'n' => [
                400 => 'Symbol',
            ],
        ],
        'zapfdingbats' => [
            'n' => [
                400 => 'ZapfDingbats',
            ],
        ],
    ];

    /**
     * array con tutti i font aggiunti al PDF
     */
    private ?array $fonts_added = null;

    /**
     * array con i font letti dai PDF importati
     */
    private array $fonts_read = [];



    /**
     * inizializzo le variabili statiche se non sono già state settate
     * esplicitamente.
     */
    public function __construct()
    {
        if (static::$fontpath === null)
            static::$fontpath = defined('FPDF_FONTPATH') ? rtrim(\FPDF_FONTPATH, '/\\') . '/' : substr(__DIR__, 0, -7) . 'fonts';
    }

    /**
     * aggiunge una pagina all'array delle pagine, senza verifcare nulla.
     */
    public function pushPage(PageDictionary $page)
    {
        $this->pages[] = $page;
    }

    /**
     * aggiunge una pagina all'array delle pagine, senza verifcare nulla.
     */
    public function insertPage(PageDictionary $page, int $page_num)
    {
        if ($page_num == -1 || $page_num > count($this->pages))
            $this->pages[] = $page;
        else
            array_splice($this->pages, $page_num - 1, 0, [$page]);
        $this->curPage = $page;
    }

    /**
     * restituisce la pagina corrente
     * 
     * @throws PDFException
     */
    public function getCurrentPage(): PageDictionary
    {
        if (!$this->curPage)
            throw new PDFException('No page selected');

        return $this->curPage;
    }

    /**
     * restituisce l'array delle pagine o null se non ci sono pagine
     * 
     * @throws PDFException
     */
    public function getPages(): ?array
    {
        return count($this->pages) ? $this->pages : null;
    }

    /**
     * get number of pages in PDF
     * 
     * @return int - Number of pages
     */
    public function countPages(): int
    {
        return count($this->pages);
    }

    /**
     * Choose the page to write on
     * 
     * @param int $page_num - Number of page to write on
     */
    public function goToPage(int $page_num): void
    {
        if ($page_num <= 0 || $page_num > count($this->pages))
            throw new PDFException('page doesn\'t exist');

        $this->curPage = $this->pages[$page_num - 1];
    }

    /**
     * svuota l'array delle risorse
     */
    public function emptyResources()
    {
        $this->resources = [];
        $this->fontNumber = 0;
    }

    /**
     * aggiunge l'hash di una risorsa all'array delle risorse.
     */
    public function pushResources(IResourcesWriter $writer)
    {
        $hash = $writer->getResourcesHash(true);
        if (array_key_exists($hash, $this->resources))
            $this->resources[$hash][] = $writer;
        else
            $this->resources[$hash] = [$writer];
    }

    /**
     * aggiunge una singola risorsa alle risorse globali.
     */
    public function pushSingleResource(string $type, object $obj): object
    {
        if ($type == 'font') {
            $this->fonts_read[] = $obj;
            return $obj->toInternalFont();
        }

        throw new PDFException('unkwown resources type');
    }

    /**
     * 
     */
    public function shrinkResources()
    {
        print 'TODO: creare una struttura con tutte le risorse usate dalle pagine e renderle univoche' . PHP_EOL;
        foreach ($this->resources as $k => $r) {
            print $k . PHP_EOL;
            foreach ($r as $a => $b)
                print '  ' . $a . ' ' . get_class($b) . PHP_EOL;
        }
        //print get_class($r) . PHP_EOL;
        //print_r($resources);
    }

    /**
     * restituisce l'elenco delle pagine che utilizzano una certa risosrsa.
     */
    public function getIResourcesWriters(string $hash): array
    {
        if (array_key_exists($hash, $this->resources))
            return $this->resources[$hash];
        throw new PDFException('resources hash not found');
    }










    /**
     * Cambia la cartella di default dove cercare i font
     * 
     * @param string $path - path completo di dove cercare i font
     */
    static public function setFontPath(string $path)
    {
        static::$fontpath = rtrim($path, '/\\') . '/';
    }

    /**
     * se presente carica il file dei metadati dei font presente nella cartella
     * dei font, altrimenti lo crea.
     */
    private function caricaFontsArray()
    {
        $json = static::$fontpath . 'boapdf.json';
        if (is_file($json)) {
            $this->fonts_added = array_merge($this->std_14_fonts, json_decode(file_get_contents($json), true));
            return;
        }

        // se il file non esiste devo scorrere tutti i file della directory e
        // creare l'array da salvare
        $f = [];
        if ($handle = @opendir(static::$fontpath)) {
            while (false !== ($file = readdir($handle))) {
                if ($file == '.' || $file == '..' || !is_file(static::$fontpath . $file))
                    continue;

                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (!in_array($ext, ['otf', 'ttf']))
                    continue;

                try {
                    $ttf = new Font(static::$fontpath . $file);

                    $fam = $ttf->family;
                    if (!array_key_exists($fam, $f))
                        $f[$fam] = [];

                    $sty = $ttf->style == 'normal' ? 'n' : 'i';
                    if (!array_key_exists($sty, $f[$fam]))
                        $f[$fam][$sty] = [];

                    $f[$fam][$sty][$ttf->weight] = static::$fontpath . $file;
                } catch (Throwable $tr) {
                }
            }
            closedir($handle);
            file_put_contents($json, json_encode($f, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        }
        $this->fonts_added = array_merge($this->std_14_fonts, $f);
    }

    /**
     * Aggiunge un font TrueType o OpenType al pdf.
     */
    public function addFont(string $file, ?string $family, ?string $style, ?int $weight)
    {
        if (strpos($file, '/') === false && strpos($file, "\\") === false)
            $file = static::$fontpath . $file;

        while (is_link($file))
            $file = readlink($file);

        if (!is_file($file))
            throw new PDFException('File doesn\'t exist.');

        echo '***** InternalFont::createFromFile('.$file.');'.PHP_EOL;
        $obj = InternalFont::createFromFile($this, $file);
        [$fam, $sty, $wei] = $obj->getFamilyStyleWeight();

        if ($family)
            $fam = $family;

        if ($weight !== null)
            $wei = $weight;

        if ($style !== null)
            $sty = $style;

        if (!$this->fonts_added)
            $this->caricaFontsArray();

        if (!array_key_exists($fam, $this->fonts_added))
            $this->fonts_added[$fam] = [];

        if (!array_key_exists($sty, $this->fonts_added[$fam]))
            $this->fonts_added[$fam][$sty] = [];

        if (!array_key_exists($wei, $this->fonts_added[$fam][$sty]) || is_string($this->fonts_added[$fam][$sty][$wei])) {
            $this->fonts_added[$fam][$sty][$wei] = $obj;
            return;
        }

        $this->fonts_added[$fam][$sty][$wei]->mergeFont($obj);
    }

    private function getFontWeight(string $family, string $style, int $weight): int
    {
        $f = $this->fonts_added[$family][$style] ?? false;
        if (!$f)
            throw new PDFException('Invalid parameters: font not found.');

        if (array_key_exists($weight, $f))
            return $weight;

        // penso che non avrò mai tantissimi valori quindi scorro i font uno ad uno, non implemento una ricerca binaria
        $prima = null;
        $dopo = null;
        foreach ($f as $k => $v) {
            if ($k > $weight) {
                $dopo = $k;
                break;
            }
            $prima = $k;
        }

        if ($weight >= 400 || $weight <= 500)
            return $dopo && $dopo <= 500 ? $dopo : ($prima ?: $dopo);
        elseif ($weight < 0)
            return $prima ?: $dopo;
        else
            return $dopo ?: $prima;
    }

    /**
     * Imposta il font per la scrittura
     */
    public function setFont(string $family, string $style, int $weight, float $size, ?string $decoration)
    {

        echo '***** ' . $family . ' ' . $style . ' ' . $weight . PHP_EOL;


        // se non ho ancora caricato l'elenco dei font lo faccio
        if (!$this->fonts_added)
            $this->caricaFontsArray();

        // applico le correzioni dell'fpdf
        if ($family == 'arial' && !array_key_exists('arial', $this->fonts_added))
            $family = 'helvetica';
        else if (($family == 'symbol' || $family == 'zapfdingbats') && $style != 'n' && !array_key_exists($style, $this->fonts_added[$family]))
            $style = 'n';

        // verifico che il family esista
        if (!array_key_exists($family, $this->fonts_added))
            throw new PDFException('invalid parameters: font family not found.');

        if (!array_key_exists($style, $this->fonts_added[$family]))
            throw new PDFException('invalid parameters: font family style not found.');

        // salvo i nuovi valori nell'internal state
        $this->fontFamily = $family;
        $this->fontWeight = $this->getFontWeight($family, $style, $weight);
        $this->fontStyle = $style;
        $this->textDecoration = $decoration ?: null;
        $this->fontSize = $size;

        echo '      ' . $this->fontFamily . ' ' . $this->fontStyle . ' ' . $this->fontWeight . PHP_EOL;
    }

    public function setFontWeight(int $weight)
    {
        $this->fontWeight = $this->getFontWeight($this->fontFamily, $this->fontStyle, $weight);
    }


    public function setFontStyle(string $style)
    {
        $this->fontStyle = $style;
        $this->fontWeight = $this->getFontWeight($this->fontFamily, $style, $this->fontWeight);
    }

    public function getFont(): InternalFont
    {
        $f = $this->fonts_added[$this->fontFamily][$this->fontStyle][$this->fontWeight];
        if (is_string($f)) {
            while (is_link($f))
                $f = readlink($f);

            if (is_file($f)) {
        echo '***** InternalFont::createFromFile('.$f.');'.PHP_EOL;
                $f = InternalFont::createFromFile($this, $f);
            }else{
        echo '***** InternalFont::createStandard14Font('.$f.');'.PHP_EOL;
                $f = InternalFont::createStandard14Font($this, $f, null, null);
            }
            $this->fonts_added[$this->fontFamily][$this->fontStyle][$this->fontWeight] = $f;
        }
        return $f;
    }

    public function getNextFontNumber(): int
    {
        return ++$this->fontNumber;
    }
}
