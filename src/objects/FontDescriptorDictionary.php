<?php

namespace Boajr\PDF\Objects;

use Boajr\PDF\Parser\PDFObjectDictionary;


/**
 * Classe che rappresenta un font descriptor dictionary come specificato a pagina 343 e 348 del file
 * ISO_32000-2-2020_sponsored.pdf
 */
class FontDescriptorDictionary extends BaseDictionary
{
    public function __construct(InternalState $internalState, ?PDFObjectDictionary $src = null)
    {
        $this->internalState = $internalState;

        $this->addEntry('Type', Entry::NAME, true, true, 1000);
        $this->addEntry('FontName', Entry::NAME, true, true, 1000);
        //$this->addEntry('FontFamily', Entry::BYTE_STRING, false, true, 1005);
        //$this->addEntry('FontStretch', Entry::NAME, false, true, 1005);
        //$this->addEntry('FontWeight', Entry::NUMBER, false, true, 1005);
        $this->addEntry('Flags', Entry::INTEGER, true, true, 1000);
        $this->addEntry('FontBBox', Entry::RECTANGLE, false, true, 1000);
        $this->addEntry('ItalicAngle', Entry::NUMBER, true, true, 1000);
        $this->addEntry('Ascent', Entry::NUMBER, false, true, 1000);
        $this->addEntry('Descent', Entry::NUMBER, false, true, 1000);
        //$this->addEntry('Leading', Entry::NUMBER, false, true, 1000);
        $this->addEntry('CapHeight', Entry::NUMBER, false, true, 1000);
        $this->addEntry('XHeight', Entry::NUMBER, false, true, 1000);
        $this->addEntry('StemV', Entry::NUMBER, false, true, 1000);
        //$this->addEntry('StemH', Entry::NUMBER, false, true, 1000);
        //$this->addEntry('AvgWidth', Entry::NUMBER, false, true, 1000);
        //$this->addEntry('MaxWidth', Entry::NUMBER, false, true, 1000);
        $this->addEntry('MissingWidth', Entry::NUMBER, false, true, 1000);
        $this->addEntry('FontFile', [Entry::STREAM => FontDescriptorDictionary_ProgramStream::class], false, false, 1000);
        $this->addEntry('FontFile2', [Entry::STREAM => FontDescriptorDictionary_ProgramStream::class], false, false, 1001);
        $this->addEntry('FontFile3', [Entry::STREAM => FontDescriptorDictionary_ProgramStream::class], false, false, 1002);
        $this->addEntry('CharSet', Entry::TEXT_STRING, false, true, 1001, 2000);

        // voci aggiuntive per i CIDFont
        //$this->addEntry('Style', Entry::DICTIONARY, false, true, 1000);
        //$this->addEntry('Lang', Entry::NAME, false, true, 1005);
        //$this->addEntry('FD', Entry::DICTIONARY, false, true, 1000);
        //$this->addEntry('CIDSet', [Entry::STREAM => 'Font_CIDSetStream'], false, false, 1000, 2000);


        if ($src) {
            $src->SetLinkedObject($this);
            $this->setData($src);
        }
    }


    public function appendObject(array &$objs, bool $inline): int
    {
        $ver = parent::appendObject($objs, $inline);
        //if ($ver < 1003 && $this->FontFile2 && $this->)

        //print 'TODO: la versione del pdf dipende dal tipo di font che viene incluso.' . PHP_EOL;

        return $ver;
    }
}