<?php

namespace Boajr\PDF\Objects;

use Boajr\PDF\Parser\PDFObjectDictionary;
use Boajr\PDF\PDF;


/**
 * Classe che rappresenta un generico stream, come specificato a pagina 32 del file ISO_32000-2-2020_sponsored.pdf. Se 
 * vengono passati dei dati, è possibile decidere se decodificare lo stream e azzerare i campi dei filtri.
 */
class Stream extends BaseDictionary
{
    protected ?string $data;

    public function __construct(PDF $pdf, ?PDFObjectDictionary $src = null)
    {
        $this->pdf = $pdf;

        if ($src) {
            $src->SetLinkedObject($this);
            $this->setStreamData($src, true);
        }
    }

    protected function get_stream_data(int $ver): string
    {
        // comprime lo stream se non lo è già
        //if (!$this->Filter) {
        //    $this->Filter = 'FlateDecode';
        //    $data = zlib_encode($this->data, ZLIB_ENCODING_DEFLATE);
        //    $this->Length = strlen($data);
        //} else {
        $data = $this->data;
        //}

        return $data;
    }

    public function getInlineObject(int $ver): string
    {
        $data = $this->get_stream_data($ver);
        return parent::getInlineObject($ver) . "stream\r\n" . $data . "\r\nendstream";
    }

    public function setData(?PDFObjectDictionary $src): void
    {
        throw new \Exception('to set stream data you have to call setStreamData method instead of this one');
    }

    public function setStreamData(?PDFObjectDictionary $src, bool $decode): void
    {
        // aggiunge le entry per la gestione degli stream. Lo metto qui perché ci si potrebbe dimenticare di
        // aggiungerle nel costruttore delle sottoclassi
        $this->addEntry('Length', Entry::INTEGER, true, true, 1000);
        $this->addEntry('Filter', [Entry::NAME, Entry::ARRAY => Stream_FilterArray::class], false, true, 1000);
        $this->addEntry('DecodeParms', [Entry::DICTIONARY => GenericDictionary::class, Entry::ARRAY => Stream_DecodeParamsArray::class], false, true, 1000);
        //$this->addEntry('F', Entry::FILE_SPECIFICATION, false, true, 1002);        
        //$this->addEntry('FFilter', [Entry::NAME, Entry::ARRAY => 'Stream_FilterArray'], false, true, 1002);
        //$this->addEntry('FDecodeParams', [Entry::DICTIONARY => 'GenericDictionary', Entry::ARRAY => 'Stream_DecodeParamsArray'], false, true, 1002);
        $this->addEntry('DL', Entry::INTEGER, false, true, 1005);

        if ($src) {
            if ($src['F'] ?? false) {
                throw new \Exception('reading pdf external streams is\'n implemented (yet???)');
            }

            parent::setData($src);

            $this->data = $src->GetFinalValue()->GetStream($decode);
            if ($decode) {
                $this->Length = strlen($this->data);
                unset($this->Filter);
                unset($this->DecodeParams);
                //$this->F = null;
                //$this->FFilter = null;
                //$this->FDecodeParams = null;
            }
        } else
            $this->data = null;
    }
}
