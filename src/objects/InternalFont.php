<?php

namespace Boajr\PDF\Objects;

use Boajr\PDF\font\Font;
use Boajr\PDF\PDFException;
use Throwable;

//use Boajr\PDF\Parser\PDFObjectDictionary;


/**
 * Parametri di default da usare nelle varie pagine
 */
class InternalFont
{
    private const std14 = [
        'Courier' => ['courier', 'n', 400],
        'Courier-Bold' => ['courier', 'n', 700],
        'Courier-Oblique' => ['courier', 'i', 400],
        'Courier-BoldOblique' => ['courier', 'i', 700],
        'Helvetica' => ['helvetica', 'n', 400],
        'Helvetica-Bold' => ['helvetica', 'n', 700],
        'Helvetica-Oblique' => ['helvetica', 'i', 400],
        'Helvetica-BoldOblique' => ['helvetica', 'i', 700],
        'Times-Roman' => ['times', 'n', 400],
        'Times-Bold' => ['times', 'n', 700],
        'Times-Italic' => ['times', 'i', 400],
        'Times-BoldItalic' => ['times', 'i', 700],
        'Symbol' => ['symbol', 'n', 400],
        'ZapfDingbats' => ['zapfdingbats', 'n', 400],
    ];

    private ?string $resourceName = null;
    private ?FontDictionary $tmpFD = null;

    private function __construct(private InternalState $internalState, private string $postScriptName, private array $famStyWei) {}

    static public function isStandard14Font(string $fontName): bool
    {
        return array_key_exists($fontName, static::std14);
    }

    static public function createFromFile(InternalState $internalState, string $fileName): InternalFont {}

    static public function createStandard14Font(InternalState $internalState, string $fontName): InternalFont
    {
        if (!array_key_exists($fontName, static::std14))
            throw new PDFException($fontName . ' isn\'t one of 14 standard fonts.');
        return new static($internalState, $fontName, static::std14[$fontName]);
    }



    public function getFamilyStyleWeight(): array
    {
        echo 'getFamilyStyleWeight(): ' . json_encode($this->famStyWei) . PHP_EOL;
        return $this->famStyWei;
    }

    public function getPostScriptName(): string
    {
        echo 'getPostScriptName(): ' . $this->postScriptName . PHP_EOL;
        return $this->postScriptName;
    }

    public function isSameFont(?InternalFont $altro): bool
    {
        print 'TODO: verificare che i due font sono uguali dalle caratteristiche del font, non dall\'oggetto' . PHP_EOL;
        print '      $altro: ' . ($altro ? 'true' : 'false') . PHP_EOL;
        print '      $this == $altro: ' . ($this == $altro ? 'true' : 'false') . PHP_EOL;
        if ($this === $altro) {
            $altro->mergeFont($this);
            return true;
        }

        return false;
    }

    public function mergeFont(InternalFont $toMerge)
    {
        echo 'TODO: mergeFont()' . PHP_EOL;
    }

    // inline è sempre false
    public function appendObject(array &$objs, bool $inline): int
    {
        echo 'appendObject(): ' . ($inline ? 'true' : 'false') . PHP_EOL;

        $this->resourceName = 'F' . $this->internalState->getNextFontNumber();

        $this->tmpFD = new FontDictionary($this->internalState, 'Type1');
        $this->tmpFD->Name = $this->resourceName;
        $this->tmpFD->BaseFont = $this->postScriptName;
        return $this->tmpFD->appendObject($objs, $inline);
    }

    // restituisce qualcosa del tipo F1
    public function getName(): string
    {
        echo 'getName(): ' . $this->resourceName . PHP_EOL;
        return $this->resourceName;
    }

    //restituisce qualcosa del tipo /F1 1 0 R
    public function getNameAndObjectReference(): string
    {
        echo 'getNameAndObjectReference(): ' . '/' . $this->resourceName . ' ' . $this->tmpFD->getObjectReference() . PHP_EOL;
        return '/' . $this->resourceName . ' ' . $this->tmpFD->getObjectReference();
    }

    // restituisce un array contenete il testo da inserire nel PDF e la lunghezza del testo stesso
    public function processText(string $text): array
    {
        print 'TODO: misurare la lunghezza del testo e processare i vari possibili scostamenti di testo' . PHP_EOL;
        $t = $text;
        $w = 1000;

        return [$t, $w];
    }

    // restituisce lo spessore da usare per le sottolineature
    public function getDecorationMeasures(string $textDecoration): array
    {
        echo 'TODO: prelevare le misure della sottolineatura dal font' . PHP_EOL;
        //I Dati della SottolineaturaI parametri della sottolineatura si trovano nella tabella post (PostScript) del font.
        //underlinePosition: Indica la coordinata Y suggerita per il bordo superiore della linea di sottolineatura rispetto alla linea di base (baseline).
        //underlineThickness: Definisce lo spessore ideale della linea.



        // Underline text
        //$up = $this->CurrentFont['up'];
        //$ut = $this->CurrentFont['ut'];
        //$w = $this->GetStringWidth($txt) + $this->ws * substr_count($txt, ' ');
        //return sprintf('%.2F %.2F %.2F %.2F re f', $x * $this->k, ($this->h - ($y - $up / 1000 * $this->FontSize)) * $this->k, $w * $this->k, -$ut / 1000 * $this->FontSizePt);

        // tutti i font di base hanno come posizione -100 e come spessore 50,
        // lo uso per tutti i font che non hanno i dati nel ttf
        return [-100 / 1000, 50 / 1000];
    }

    private array $userChars = [];
    public function addUsedChars(string $text, EncodingDictionary|string|null $encoding)
    {
        print 'TODO: processare l\'encoding';
        if ($encoding) {
            if ($encoding instanceof EncodingDictionary)
                print ' contenuto nel dictionary (base: ' . $encoding->BaseEncoding . ')';
            else
                print ' ' . $encoding;
        }
        print PHP_EOL;

        foreach (str_split($text) as $char) {
            $this->userChars[$char] = true;
        }
    }
}
