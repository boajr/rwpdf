<?php

namespace Boajr\PDF\Objects;

use Boajr\PDF\Parser\PDFObjectDictionary;
use Boajr\PDF\PDFException;


/**
 * Classe che rappresenta un font dictionary come specificato nel file ISO_32000-2-2020_sponsored.pdf
 * 
 * I riferimenti sono per i vari subtype sono:
 * - Type 0: pagina 341
 * - Type 1: pagina 313
 * 
 * 
 * 
 * 
 * 
 * 
 *  dei suoi subtype come specificato
 * a pagina 341
 * (SubType Type0), 313 (SubType Type1), 317 (SubType Type3) e 330 (CIDFont
 * Type) del pdf 2.0
 */
class FontDictionary extends BaseDictionary
{
    private function setSubType(string $subType)
    {
        $this->entryList = [];

        $this->addEntry('Type', Entry::NAME, true, true, 1000);

        switch ($subType) {
            //case 'Type0': // pagina 340 del pdf 2.0
            //    $this->addEntry('Subtype', Entry::NAME, true, true, 1002);
            //    $this->addEntry('BaseFont', Entry::NAME, true, true, 1000);
            //    $this->addEntry('Encoding', [Entry::NAME, Entry::STREAM => 'Font_CMapStream'], true, [Entry::NAME => true, Entry::STREAM => false], 1000);
            //    $this->addEntry('DescendantFonts', [Entry::ARRAY => 'Font_DescendantFontsArray'], true, true, 1000);
            //    $this->addEntry('ToUnicode', [Entry::STREAM => 'Font_CMapStream'], false, false, 1002);
            //    break;

            case 'Type1':
            case 'MMType1':
            case 'TrueType':
                $this->addEntry('Subtype', Entry::NAME, true, true, 1000);
                //$this->addEntry('Name', Entry::NAME, false, true, 1000, 2000);
                $this->addEntry('Name', Entry::NAME, false, true, 1000, 1001); // ho messo 1001 e non 2000 perché non serve salvare il valore dal pdf 1.1 in poi
                $this->addEntry('BaseFont', Entry::NAME, true, true, 1000);
                $this->addEntry('FirstChar', Entry::INTEGER, false, true, 1000);
                $this->addEntry('LastChar', Entry::INTEGER, false, true, 1000);
                $this->addEntry('Widths', [Entry::ARRAY => FontDictionary_WidthsArray::class], false, false, 1000);
                $this->addEntry('FontDescriptor', [Entry::DICTIONARY => FontDescriptorDictionary::class], false, false, 1000);
                $this->addEntry('Encoding', [Entry::DICTIONARY => EncodingDictionary::class, Entry::NAME], false, true, 1000);
                $this->addEntry('ToUnicode', [Entry::STREAM => FontDictionary_ToUnicodeCMapStream::class], false, false, 1002);
                return;

                //case 'Type3':
                //    $this->addEntry('Subtype', Entry::NAME, true, true, 1000);
                //    $this->addEntry('Name', Entry::NAME, false, true, 1000);
                //    $this->addEntry('FontBBox', Entry::RECTANGLE, true, true, 1000);
                //    $this->addEntry('FontMatrix', [Entry::ARRAY => 'Matrix'], false, true, 1000);
                //    //$this->addEntry('CharProcs', Entry::NAME, false, true, 1000);
                //    $this->addEntry('Encoding', [Entry::DICTIONARY => 'EncodingDictionary'], true, true, 1000);
                //    $this->addEntry('FirstChar', Entry::INTEGER, false, true, 1000);
                //    $this->addEntry('LastChar', Entry::INTEGER, false, true, 1000);
                //    $this->addEntry('Widths', [Entry::ARRAY => 'Font_WidthsArray'], false, false, 1000);
                //    $this->addEntry('FontDescriptor', [Entry::DICTIONARY => 'FontDescriptorDictionary'], false, false, 1000);
                //    $this->addEntry('Resources', [Entry::DICTIONARY => 'ResourcesDictionary'], false, true, 1002);
                //    $this->addEntry('ToUnicode', [Entry::STREAM => 'Font_CMapStream'], false, false, 1002);
                //    break;

                //case 'CIDFontType0':
                //case 'CIDFontType2':
                //    $this->addEntry('Subtype', Entry::NAME, true, true, 1002);
                //    $this->addEntry('BaseFont', Entry::NAME, true, true, 1000);
                //    $this->addEntry('CIDSystemInfo', [Entry::DICTIONARY => 'Font_CIDSystemInfoDictionary'], true, true, 1000);
                //    $this->addEntry('FontDescriptor', [Entry::DICTIONARY => 'FontDescriptorDictionary'], true, false, 1000);
                //    $this->addEntry('DW', Entry::NUMBER, false, true, 1000);
                //    $this->addEntry('W', [Entry::ARRAY => 'Font_CIDWidthsArray'], false, true, 1000);
                //    //$this->addEntry('DW2', Entry::ARRAY, false, true, 1000);
                //    //$this->addEntry('W2', Entry::ARRAY, false, true, 1000);
                //    $this->addEntry('CIDToGIDMap', [Entry::NAME, Entry::STREAM => 'Font_CIDToGIDMapStream'], false, [Entry::NAME => true, Entry::STREAM => false], 1000);
                //    break;
        }

        throw new PDFException('unknown font SubType');
    }

    public function __construct(InternalState $internalState, PDFObjectDictionary|string $src)
    {
        $this->internalState = $internalState;
        if ($src instanceof PDFObjectDictionary) {
            $this->setSubType($src['Subtype']->GetFinalValue());
            $src->SetLinkedObject($this);
            $this->setData($src);
        } else {
            $this->setSubType($src);
            $this->Type = 'Font';
            $this->Subtype = $src;
        }
    }

    public function appendObject(array &$objs, bool $inline): int
    {
        $ver = parent::appendObject($objs, $inline);
        echo $ver . PHP_EOL;
        echo 'TODO: la versione del pdf dipende dal tipo di font che viene incluso.' . PHP_EOL;
        return $ver;
    }

    private ?InternalFont $internalFont = null;

    public function toInternalFont(): InternalFont
    {
        echo '***** To internalFont' . PHP_EOL;
        if (!$this->internalFont) {
            switch ($this->Subtype) {
                case 'Type1':
                case 'MMType1':
                    if (InternalFont::isStandard14Font($this->BaseFont))
                        $this->internalFont = InternalFont::createStandard14Font($this->internalState, $this->BaseFont);
                    break;

                case 'TrueType':
                    $fontFile = $this->FontDescriptor->FontFile2 ?: $this->FontDescriptor->FontFile3;

                    //print_r($this->FontDescriptor);

                    $this->internalFont = InternalFont::createFromFile($this->internalState, $fontFile->getFontObject($this));
                    break;
            }
        }

        if (!$this->internalFont)
            throw new PDFException('***** BISOGNA CREARE L\'InternalFont *****');

        return $this->internalFont;
    }
}
