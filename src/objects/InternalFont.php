<?php

namespace Boajr\PDF\Objects;

use Boajr\PDF\font\Font;
use Boajr\PDF\PDFException;


/**
 * Parametri di default da usare nelle varie pagine
 */
class InternalFont
{
    private const encodeOneByte = [
        'WinAnsiEncoding' => [' ' => "\x20", '!' => "\x21", '"' => "\x22", '#' => "\x23", '$' => "\x24", '%' => "\x25", '&' => "\x26", '(' => "\x28", ')' => "\x29", '*' => "\x2a", '+' => "\x2b", ',' => "\x2c", '-' => "\x2d", '.' => "\x2e", '/' => "\x2f", '0' => "\x30", '1' => "\x31", '2' => "\x32", '3' => "\x33", '4' => "\x34", '5' => "\x35", '6' => "\x36", '7' => "\x37", '8' => "\x38", '9' => "\x39", ':' => "\x3a", ';' => "\x3b", '<' => "\x3c", '=' => "\x3d", '>' => "\x3e", '?' => "\x3f", '@' => "\x40", 'A' => "\x41", 'B' => "\x42", 'C' => "\x43", 'D' => "\x44", 'E' => "\x45", 'F' => "\x46", 'G' => "\x47", 'H' => "\x48", 'I' => "\x49", 'J' => "\x4a", 'K' => "\x4b", 'L' => "\x4c", 'M' => "\x4d", 'N' => "\x4e", 'O' => "\x4f", 'P' => "\x50", 'Q' => "\x51", 'R' => "\x52", 'S' => "\x53", 'T' => "\x54", 'U' => "\x55", 'V' => "\x56", 'W' => "\x57", 'X' => "\x58", 'Y' => "\x59", 'Z' => "\x5a", '[' => "\x5b", '\'' => "\x27", '\\' => "\x5c", ']' => "\x5d", '^' => "\x5e", '_' => "\x5f", '`' => "\x60", 'a' => "\x61", 'b' => "\x62", 'c' => "\x63", 'd' => "\x64", 'e' => "\x65", 'f' => "\x66", 'g' => "\x67", 'h' => "\x68", 'i' => "\x69", 'j' => "\x6a", 'k' => "\x6b", 'l' => "\x6c", 'm' => "\x6d", 'n' => "\x6e", 'o' => "\x6f", 'p' => "\x70", 'q' => "\x71", 'r' => "\x72", 's' => "\x73", 't' => "\x74", 'u' => "\x75", 'v' => "\x76", 'w' => "\x77", 'x' => "\x78", 'y' => "\x79", 'z' => "\x7a", '{' => "\x7b", '|' => "\x7c", '}' => "\x7d", '~' => "\x7e", ' ' => "\xa0", '¡' => "\xa1", '¢' => "\xa2", '£' => "\xa3", '¤' => "\xa4", '¥' => "\xa5", '¦' => "\xa6", '§' => "\xa7", '¨' => "\xa8", '©' => "\xa9", 'ª' => "\xaa", '«' => "\xab", '¬' => "\xac", '­' => "\xad", '®' => "\xae", '¯' => "\xaf", '°' => "\xb0", '±' => "\xb1", '²' => "\xb2", '³' => "\xb3", '´' => "\xb4", 'µ' => "\xb5", '¶' => "\xb6", '·' => "\xb7", '¸' => "\xb8", '¹' => "\xb9", 'º' => "\xba", '»' => "\xbb", '¼' => "\xbc", '½' => "\xbd", '¾' => "\xbe", '¿' => "\xbf", 'À' => "\xc0", 'Á' => "\xc1", 'Â' => "\xc2", 'Ã' => "\xc3", 'Ä' => "\xc4", 'Å' => "\xc5", 'Æ' => "\xc6", 'Ç' => "\xc7", 'È' => "\xc8", 'É' => "\xc9", 'Ê' => "\xca", 'Ë' => "\xcb", 'Ì' => "\xcc", 'Í' => "\xcd", 'Î' => "\xce", 'Ï' => "\xcf", 'Ð' => "\xd0", 'Ñ' => "\xd1", 'Ò' => "\xd2", 'Ó' => "\xd3", 'Ô' => "\xd4", 'Õ' => "\xd5", 'Ö' => "\xd6", '×' => "\xd7", 'Ø' => "\xd8", 'Ù' => "\xd9", 'Ú' => "\xda", 'Û' => "\xdb", 'Ü' => "\xdc", 'Ý' => "\xdd", 'Þ' => "\xde", 'ß' => "\xdf", 'à' => "\xe0", 'á' => "\xe1", 'â' => "\xe2", 'ã' => "\xe3", 'ä' => "\xe4", 'å' => "\xe5", 'æ' => "\xe6", 'ç' => "\xe7", 'è' => "\xe8", 'é' => "\xe9", 'ê' => "\xea", 'ë' => "\xeb", 'ì' => "\xec", 'í' => "\xed", 'î' => "\xee", 'ï' => "\xef", 'ð' => "\xf0", 'ñ' => "\xf1", 'ò' => "\xf2", 'ó' => "\xf3", 'ô' => "\xf4", 'õ' => "\xf5", 'ö' => "\xf6", '÷' => "\xf7", 'ø' => "\xf8", 'ù' => "\xf9", 'ú' => "\xfa", 'û' => "\xfb", 'ü' => "\xfc", 'ý' => "\xfd", 'þ' => "\xfe", 'ÿ' => "\xff", 'Œ' => "\x8c", 'œ' => "\x9c", 'Š' => "\x8a", 'š' => "\x9a", 'Ÿ' => "\x9f", 'Ž' => "\x8e", 'ž' => "\x9e", 'ƒ' => "\x83", 'ˆ' => "\x88", '˜' => "\x98", '–' => "\x96", '—' => "\x97", '‘' => "\x91", '’' => "\x92", '‚' => "\x82", '“' => "\x93", '”' => "\x94", '„' => "\x84", '†' => "\x86", '‡' => "\x87", '•' => "\x95", '…' => "\x85", '‰' => "\x89", '‹' => "\x8b", '›' => "\x9b", '€' => "\x80", '™' => "\x99"],
        'MacRomanEncoding' => [' ' => "\x20", '!' => "\x21", '"' => "\x22", '#' => "\x23", '$' => "\x24", '%' => "\x25", '&' => "\x26", '(' => "\x28", ')' => "\x29", '*' => "\x2a", '+' => "\x2b", ',' => "\x2c", '-' => "\x2d", '.' => "\x2e", '/' => "\x2f", '0' => "\x30", '1' => "\x31", '2' => "\x32", '3' => "\x33", '4' => "\x34", '5' => "\x35", '6' => "\x36", '7' => "\x37", '8' => "\x38", '9' => "\x39", ':' => "\x3a", ';' => "\x3b", '<' => "\x3c", '=' => "\x3d", '>' => "\x3e", '?' => "\x3f", '@' => "\x40", 'A' => "\x41", 'B' => "\x42", 'C' => "\x43", 'D' => "\x44", 'E' => "\x45", 'F' => "\x46", 'G' => "\x47", 'H' => "\x48", 'I' => "\x49", 'J' => "\x4a", 'K' => "\x4b", 'L' => "\x4c", 'M' => "\x4d", 'N' => "\x4e", 'O' => "\x4f", 'P' => "\x50", 'Q' => "\x51", 'R' => "\x52", 'S' => "\x53", 'T' => "\x54", 'U' => "\x55", 'V' => "\x56", 'W' => "\x57", 'X' => "\x58", 'Y' => "\x59", 'Z' => "\x5a", '[' => "\x5b", '\'' => "\x27", '\\' => "\x5c", ']' => "\x5d", '^' => "\x5e", '_' => "\x5f", '`' => "\x60", 'a' => "\x61", 'b' => "\x62", 'c' => "\x63", 'd' => "\x64", 'e' => "\x65", 'f' => "\x66", 'g' => "\x67", 'h' => "\x68", 'i' => "\x69", 'j' => "\x6a", 'k' => "\x6b", 'l' => "\x6c", 'm' => "\x6d", 'n' => "\x6e", 'o' => "\x6f", 'p' => "\x70", 'q' => "\x71", 'r' => "\x72", 's' => "\x73", 't' => "\x74", 'u' => "\x75", 'v' => "\x76", 'w' => "\x77", 'x' => "\x78", 'y' => "\x79", 'z' => "\x7a", '{' => "\x7b", '|' => "\x7c", '}' => "\x7d", '~' => "\x7e", ' ' => "\xca", '¡' => "\xc1", '¢' => "\xa2", '£' => "\xa3", '¤' => "\xdb", '¥' => "\xb4", '§' => "\xa4", '¨' => "\xac", '©' => "\xa9", 'ª' => "\xbb", '«' => "\xc7", '¬' => "\xc2", '®' => "\xa8", '¯' => "\xf8", '°' => "\xa1", '±' => "\xb1", '´' => "\xab", 'µ' => "\xb5", '¶' => "\xa6", '·' => "\xe1", '¸' => "\xfc", 'º' => "\xbc", '»' => "\xc8", '¿' => "\xc0", 'À' => "\xcb", 'Á' => "\xe7", 'Â' => "\xe5", 'Ã' => "\xcc", 'Ä' => "\x80", 'Å' => "\x81", 'Æ' => "\xae", 'Ç' => "\x82", 'È' => "\xe9", 'É' => "\x83", 'Ê' => "\xe6", 'Ë' => "\xe8", 'Ì' => "\xed", 'Í' => "\xea", 'Î' => "\xeb", 'Ï' => "\xec", 'Ñ' => "\x84", 'Ò' => "\xf1", 'Ó' => "\xee", 'Ô' => "\xef", 'Õ' => "\xcd", 'Ö' => "\x85", 'Ø' => "\xaf", 'Ù' => "\xf4", 'Ú' => "\xf2", 'Û' => "\xf3", 'Ü' => "\x86", 'ß' => "\xa7", 'à' => "\x88", 'á' => "\x87", 'â' => "\x89", 'ã' => "\x8b", 'ä' => "\x8a", 'å' => "\x8c", 'æ' => "\xbe", 'ç' => "\x8d", 'è' => "\x8f", 'é' => "\x8e", 'ê' => "\x90", 'ë' => "\x91", 'ì' => "\x93", 'í' => "\x92", 'î' => "\x94", 'ï' => "\x95", 'ñ' => "\x96", 'ò' => "\x98", 'ó' => "\x97", 'ô' => "\x99", 'õ' => "\x9b", 'ö' => "\x9a", '÷' => "\xd6", 'ø' => "\xbf", 'ù' => "\x9d", 'ú' => "\x9c", 'û' => "\x9e", 'ü' => "\x9f", 'ÿ' => "\xd8", 'ı' => "\xf5", 'Œ' => "\xce", 'œ' => "\xcf", 'Ÿ' => "\xd9", 'ƒ' => "\xc4", 'ˆ' => "\xf6", 'ˇ' => "\xff", '˘' => "\xf9", '˙' => "\xfa", '˚' => "\xfb", '˛' => "\xfe", '˜' => "\xf7", '˝' => "\xfd", '–' => "\xd0", '—' => "\xd1", '‘' => "\xd4", '’' => "\xd5", '‚' => "\xe2", '“' => "\xd2", '”' => "\xd3", '„' => "\xe3", '†' => "\xa0", '‡' => "\xe0", '•' => "\xa5", '…' => "\xc9", '‰' => "\xe4", '‹' => "\xdc", '›' => "\xdd", '⁄' => "\xda", '™' => "\xaa", 'ﬁ' => "\xde", 'ﬂ' => "\xdf"],
    ];

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

    private array $usedChars = [];
    private ?string $encoding = null;
    private int $firstChar = 0;
    private int $lastChar = 0;
    private ?array $differences = null;

    private function __construct(private InternalState $internalState, private string $postScriptName, private array $famStyWei, private bool $symbolicFont, private ?Font $font = null) {}

    static public function isStandard14Font(string $fontName): bool
    {
        return array_key_exists($fontName, static::std14);
    }

    static public function createFromFile(InternalState $internalState, Font $font): InternalFont
    {
        //if ($font->embeddable != Font::EMBEDDABLE_FULL)
        //    throw new PDFException('La licenza del font non permette l\'utilizzo in un pdf.');

        return new static($internalState, $font->fontDescriptorData->FontName, [
            $font->family,
            $font->style == 'normal' ? 'n' : 'i',
            $font->weight
        ], $font->fontDescriptorData->Flags & 4 != 0, $font);
    }

    static public function createStandard14Font(InternalState $internalState, string $fontName): InternalFont
    {
        if (!array_key_exists($fontName, static::std14))
            throw new PDFException($fontName . ' isn\'t one of 14 standard fonts.');
        return new static($internalState, $fontName, static::std14[$fontName], in_array($fontName, ['Symbol', 'ZapfDingbats']));
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
        // se l'oggetto è lo stesso ritono true e non faccio altro
        if ($this === $altro)
            return true;

        echo 'TODO: verificare che i due font siano uguali dalle caratteristiche del font, non dall\'oggetto' . PHP_EOL;
        echo '      $altro: ' . ($altro ? 'true' : 'false') . PHP_EOL;
        echo '      $this == $altro: ' . ($this == $altro ? 'true' : 'false') . PHP_EOL;
        if ($this == $altro) {
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

        $ver = 1000;

        if ($this->encoding === null)
            $this->calcolaEncoding();

        $this->resourceName = 'F' . $this->internalState->getNextFontNumber();

        // decido che tipo di file mi serve
        $isStandard = in_array($this->famStyWei, static::std14);
        $needEncoding = $this->encoding != 'SYMBOLIC' && (!$isStandard || $this->encoding != 'ASCII');
        $isOneByteEncoding = $this->encoding == 'ASCII' || $this->encoding == 'SYMBOLIC' || array_key_exists($this->encoding, static::encodeOneByte);
        if (!$needEncoding || $isOneByteEncoding) {
            $this->tmpFD = new FontDictionary($this->internalState, $isStandard ? 'Type1' : 'TrueType');
            $this->tmpFD->Name = $this->resourceName;
            $this->tmpFD->BaseFont = $this->postScriptName;
            if ($needEncoding) {
                $this->tmpFD->Encoding = $this->differences ?
                    new EncodingDictionary($this->internalState, $this->encoding, $this->differences) :
                    match ($this->encoding) {
                        'ASCII' => 'WinAnsiEncoding',
                        'SYMBOLIC' => '',
                        default => $this->encoding
                    };
                if ($this->encoding == 'MacRomanEncoding') {
                    echo 'TODO: aggiungere il ToUnicode per le legature se presenti.' . PHP_EOL;
                    /*
/CIDInit /ProcSet findresource begin
12 dict begin
begincmap
/CIDSystemInfo << /Registry (Adobe) /Ordering (UCS) /Supplement 0 >> def
/臨界名 /ToUnicode-Ligatures def
/CMapType 2 def
1 begincodespacerange
  <00> <FF>
endcodespacerange
2 beginbfrange
  <92> <92> <00660069>  ; Mappa il byte 92 in "fi" (U+0066 U+0069)
  <93> <93> <0066006C>  ; Mappa il byte 93 in "fl" (U+0066 U+006C)
endbfrange
endcmap
CMapName currentdict /CMap defineresource pop
end
end
*/
                }
            }

            if (!$isStandard) {
                $this->tmpFD->FirstChar = $this->firstChar;
                $this->tmpFD->LastChar = $this->lastChar;
                //$this->tmpFD->Widths = [];

                $this->tmpFD->Widths = new FontDictionary_WidthsArray($this->internalState);
                $enc = match ($this->encoding) {
                    'ASCII' => static::encodeOneByte['WinAnsiEncoding'],
                    'SYMBOLIC' => null,
                    default => static::encodeOneByte[$this->encoding]
                };

                if ($enc) {
                    $rev = array_flip($enc);
                    for ($i = $this->firstChar; $i <= $this->lastChar; ++$i) {
                        $w = null;
                        $c = chr($i);
                        if (array_key_exists($c, $rev)) {
                            $w = $this->font->getCharWidth($rev[$c]);

                            // processare se ci sono altri font
                        }

                        $this->tmpFD->Widths[] = $w ?: $this->font->fontDescriptorData->MissingWidth;
                    }
                } else {
                    throw new PDFException('TODO: aggiungere l\'array delle larhezze per i SYMBOLIC.');
                }

                $this->tmpFD->FontDescriptor = new FontDescriptorDictionary($this->internalState, $this->font->fontDescriptorData);

                // devo aggiungere lo stream del font al FontDescriptor
                if ($this->font->isTrueType()) {
                    $this->tmpFD->FontDescriptor->FontFile2 = new FontDescriptorDictionary_ProgramStream($this->internalState, FontDescriptorDictionary_ProgramStream::TrueType, $this->font->toFile($this->usedChars));
                    $ver = 1001;
                } else {
            throw new PDFException('TODO: gestire i font OpenType ecc...');
                }
            }

            $ret = $this->tmpFD->appendObject($objs, $inline);
            return $ret > $ver ? $ret : $ver;
        } else {
            throw new PDFException('TODO: gestire gli encoding non a 8bit.');
        }
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
        echo 'TODO: misurare la lunghezza del testo e processare i vari possibili scostamenti di testo' . PHP_EOL;
        $t = $text;
        $w = 1000;

        foreach (mb_str_split($text) as $char)
            $this->usedChars[$char] = true;


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

    private function decode8(string $text, array $conversion): string
    {
        // la stringa che viene passata a questa funzione è sempre a 8 bit
        $len = strlen($text);
        $out = '';
        for ($i = 0; $i < $len; ++$i) {
            $c = $conversion[$text[$i]] ?? '?';
            $out .= $c;
            $this->usedChars[$c] = true;
        }

        return $out;
    }

    public function decodeString(string $text, EncodingDictionary|string|null $encoding): string
    {
        // ad ogni carattere che aggiungo posso dover modificare l'encoding di
        // uscita
        $this->encoding = null;

        // se la stringa è una stringa di simboli, non converto nulla
        if ($this->symbolicFont)
            return $text;

        // se non ho un encoding vuol dire che è un aggiunta di testo da codice
        // (UTF-8) oppure è un font Type1 o un TrueType, quindi ho una stringa
        // a singolo byte da mappare.
        if (!$encoding) {
            // se la stringa è UTF-8 o ASCII (l'ascii è un sottoinsieme di
            // utf-8), la restituisco così com'è
            if (mb_check_encoding($text, 'UTF-8')) {
                foreach (mb_str_split($text) as $char)
                    $this->usedChars[$char] = true;
                return $text;
            }

            // altrimenti assegno un encoding di default
            $encoding = 'StandardEncoding';
        }

        if ($encoding instanceof EncodingDictionary)
            return $this->decode8($text, $encoding->getDecodingArray());

        //if ($encoding instanceof EncodingStream)
        //    return ???

        if (array_key_exists($encoding, EncodingDictionary::decodeOneByte))
            return $this->decode8($text, EncodingDictionary::decodeOneByte[$encoding]);

        throw new PDFException('TODO: gestire gli altri encoding (' . $encoding . ').');
    }

    private function calcolaEncoding()
    {
        // se il font contiene symboli, non converto nulla
        if ($this->symbolicFont) {
            $this->encoding = 'SYMBOLIC';
            return;
        }

        // se utilizzo meno di 128 caratteri potrebbero essere tutti ascii
        $numChars = count($this->usedChars);
        if ($numChars <= 128) {
            $isAscii = true;
            $this->firstChar = 127;
            $this->lastChar = 0;
            foreach ($this->usedChars as $ch => $x)
                if (isset($ch[0])) {
                    $ord = ord($ch[0]);
                    if ($ord > 127) {
                        $isAscii = false;
                        break;
                    }

                    if ($this->firstChar > $ord)
                        $this->firstChar = $ord;

                    if ($this->lastChar < $ord)
                        $this->lastChar = $ord;
                }

            if ($isAscii) {
                $this->encoding = 'ASCII';
                return;
            }
        }

        // se utilizzo meno di 256 caratteri potrei utilizzare uno dei due
        // metodi standard
        if ($numChars <= 256) {
            // verifico se i caratteri scritti con questo font sono un 
            // sottoinsieme di quelli degli encoding standard
            foreach (static::encodeOneByte as $type => $encChars) {
                $ok = true;
                $this->firstChar = 255;
                $this->lastChar = 0;
                foreach ($this->usedChars as $ch => $x) {
                    if (!array_key_exists($ch, $encChars)) {
                        $ok = false;
                        break;
                    }

                    $ord = ord($encChars[$ch]);

                    if ($this->firstChar > $ord)
                        $this->firstChar = $ord;

                    if ($this->lastChar < $ord)
                        $this->lastChar = $ord;
                }

                if ($ok) {
                    $this->encoding = $type;

                    if ($type == 'WinAnsiEncoding') {
                        $nonbreakingspace = array_key_exists(' ', $this->usedChars) ? 160 : false;
                        $softhyphen = array_key_exists('­', $this->usedChars) ? 173 : false;
                    } else if ($type == 'WinAnsiEncoding') {
                        $nonbreakingspace = array_key_exists(' ', $this->usedChars) ? 202 : false;
                        $softhyphen = false;
                    } else {
                        $nonbreakingspace = false;
                        $softhyphen = false;
                    }

                    if ($nonbreakingspace || $softhyphen) {
                        $this->differences = [];
                        if ($nonbreakingspace) {
                            $this->differences[] = $nonbreakingspace;
                            $this->differences[] = 'nonbreakingspace';
                        }
                        if ($softhyphen) {
                            $this->differences[] = $softhyphen;
                            $this->differences[] = 'softhyphen';
                        }
                    } else
                        $this->differences = null;

                    return;
                }
            }
        }

        echo 'TODO: verificare che posso convertire tutti i caratteri in UTF-16BE' . PHP_EOL;

        $this->encoding = 'UTF-16BE';
    }

    private function encode8(string $text, array $conversion): string
    {
        // la stringa che viene passata a questa funzione è sempre utf8
        $out = '';
        foreach (mb_str_split($text) as $char)
            $out .= $conversion[$char] ?? '?';
        return $out;
    }

    public function encodeString(string $text): string
    {
        // se non ho già calcolato l'encoding d'uscita, lo faccio ora
        if ($this->encoding === null)
            $this->calcolaEncoding();

        if ($this->encoding === 'ASCII' || $this->encoding === 'SYMBOLIC')
            return $text;

        if (array_key_exists($this->encoding, static::encodeOneByte))
            return $this->encode8($text, static::encodeOneByte[$this->encoding]);

        throw new PDFException('TODO: gestire la codifica UTF16-BE.');
    }
}
