<?php

namespace Boajr\PDF\Objects;

use Boajr\PDF\Parser\PDFObjectDictionary;
use Boajr\PDF\PDF;


/**
 * Classe che rappresenta un document catalog dictionary come specificato a pagina 98 del file 
 * ISO_32000-2-2020_sponsored.pdf
 */
class CatalogDictionary extends BaseDictionary
{
    public function __construct(PDF $pdf, ?PDFObjectDictionary $src = null)
    {
        $this->pdf = $pdf;

        $this->addEntry('Type', Entry::NAME, true, true, 1000);
        //$this->addEntry('Version', Entry::NAME, false, true, 1004);
        //$this->addEntry('Extensions', Entry::DICTIONARY, true, false, 1007);
        $this->addEntry('Pages', [Entry::DICTIONARY => PagesDictionary::class], true, false, 1000);
        //$this->addEntry('PageLabels', Entry::NUMBER_TREE, false, true, 1003);
        //$this->addEntry('Names', Entry::NUMBER_TREE, false, true, 1003);
        //$this->addEntry('Dests', Entry::NUMBER_TREE, false, true, 1003);
        //$this->addEntry('ViewerPreferences', [Entry::DICTIONARY => 'ViewerPreferencesDictionary'], false, true, 1002);
        //$this->addEntry('PageLayout', Entry::NUMBER_TREE, false, true, 1003);
        //$this->addEntry('PageMode', Entry::NUMBER_TREE, false, true, 1003);
        //$this->addEntry('Outlines', Entry::NUMBER_TREE, false, true, 1003);
        //$this->addEntry('Threads', Entry::NUMBER_TREE, false, true, 1003);
        //$this->addEntry('OpenAction', Entry::NUMBER_TREE, false, true, 1003);
        //$this->addEntry('AA', Entry::NUMBER_TREE, false, true, 1003);
        //$this->addEntry('URI', Entry::NUMBER_TREE, false, true, 1003);
        //$this->addEntry('AcroForm', Entry::NUMBER_TREE, false, true, 1003);
        //$this->addEntry('Metadata', [Entry::STREAM => 'MetadataStream'], false, false, 1004);
        //$this->addEntry('StructTreeRoot', [Entry::DICTIONARY => 'StructTreeRootDictionary'], false, true, 1003);
        //$this->addEntry('MarkInfo', [Entry::DICTIONARY => 'MarkInfoDictionary'], false, true, 1004);
        //$this->addEntry('Lang', Entry::TEXT_STRING, false, true, 1004);
        //$this->addEntry('SpiderInfo', Entry::NUMBER_TREE, false, true, 1003);
        //$this->addEntry('OutputIntents', [Entry::ARRAY => 'OutputIntentsArray'], false, true, 1004);
        //$this->addEntry('PieceInfo', Entry::NUMBER_TREE, false, true, 1003);
        //$this->addEntry('OCProperties', Entry::NUMBER_TREE, false, true, 1003);
        //$this->addEntry('Perms', Entry::NUMBER_TREE, false, true, 1003);
        //$this->addEntry('Legal', Entry::NUMBER_TREE, false, true, 1003);
        //$this->addEntry('Requirements', Entry::NUMBER_TREE, false, true, 1003);
        //$this->addEntry('Collection', Entry::NUMBER_TREE, false, true, 1003);
        //$this->addEntry('NeedsRendering', Entry::NUMBER_TREE, false, true, 1003);
        //$this->addEntry('DSS', Entry::NUMBER_TREE, false, true, 1003);
        //$this->addEntry('AF', Entry::NUMBER_TREE, false, true, 1003);
        //$this->addEntry('DPartRoot', Entry::NUMBER_TREE, false, true, 1003);

        if ($src) {
            $src->SetLinkedObject($this);
            $this->setData($src);

            // le pagine vengono caricate direttamente nel PDF, libero la
            // memoria occupata per processarle
            $this->Pages = null;
        } else {
            $this->Type = 'Catalog';
        }
    }
}
