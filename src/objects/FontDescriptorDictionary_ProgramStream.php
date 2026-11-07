<?php

namespace Boajr\PDF\Objects;

use Boajr\PDF\font\Font;
use Boajr\PDF\Parser\PDFObjectDictionary;
use Boajr\PDF\PDFException;

/**
 * Classe che rappresenta un font stream come specificato a pagina 313 (SubType Type1) e 317 (SubType Type3) del file 
 * ISO_32000-2-2020_sponsored.pdf
 */
class FontDescriptorDictionary_ProgramStream extends Stream
{
    public const TrueType = 1;

    private ?Font $font_obj = null;

    private $ver = 1000;

    public function __construct(InternalState $internalState, PDFObjectDictionary|int|null $src_or_type = null, ?string $data = null)
    {
        $this->internalState = $internalState;

        $this->addEntry('Length1', Entry::INTEGER, false, true, 1000);
        //$this->addEntry('Length2', Entry::INTEGER, false, true, 1000);
        //$this->addEntry('Length3', Entry::INTEGER, false, true, 1000);
        $this->addEntry('Subtype', Entry::NAME, false, true, 1002);

        if ($src_or_type instanceof PDFObjectDictionary) {
            $src_or_type->SetLinkedObject($this);
            $this->setStreamData($src_or_type, true);
            return;
        }

        // mi serve altrimenti non vengono create le entry
        $this->setStreamData(null, true);

        if (!$src_or_type)
            return;

        if ($data) {
            $this->data = $data;
            $this->Length = strlen($data);
        }

        switch ($src_or_type) {
            case static::TrueType:
                $this->Length1 = $this->Length;
                $this->ver = 1001;
                break;

            default:
                throw new PDFException('TODO: tipo di file sconosciuto');
        }
    }

    public function getFontObject(?FontDictionary $fontData = null): Font
    {
        if (!$this->font_obj) {
            $this->font_obj = new Font($this->data);

            // libero la memoria
            $this->data = null;
        }

        if ($fontData)
            $this->font_obj->appendMissingFontData($fontData);

        return $this->font_obj;
    }

    public function appendObject(array &$objs, bool $inline): int
    {
        $ver = parent::appendObject($objs, $inline);
        return $ver > $this->ver ? $ver : $this->ver;
    }
}
