<?php

namespace Boajr\PDF\Objects;

use Boajr\PDF\Parser\PDFObjectDictionary;
use Boajr\PDF\PDF;


/**
 * Classe che rappresenta un soft-mask dictionary come specificato a pagina 419
 * del pdf 2.0
 */
// class ExtGState_SMaskDictionary extends BaseDictionary
// {
//     public function __construct(?PDFObjectDictionary $src = null)
//     {
//         $this->addEntry('Type', Entry::NAME, false, true, 1000);
//         $this->addEntry('S', Entry::NAME, true, true, 1000);
//         $this->addEntry('G', [Entry::STREAM => 'XObjectStream'], true, false, 1000);
//         //$this->addEntry('BC', Entry::, false, true, 1000);
//         //$this->addEntry('TR', Entry::, false, true, 1000);

//         if ($src) {
//             $src->SetLinkedObject($this);
//             $this->setData($src);
//         }
//     }
// }

/**
 * Classe che rappresenta un graphics state parameter dictionary come
 * specificato a pagina 165 del pdf 2.0
 */
// class ExtGStateDictionary extends BaseDictionary
// {
//     public function __construct(?PDFObjectDictionary $src = null)
//     {
//         $this->addEntry('Type', Entry::NAME, false, true, 1000);
//         $this->addEntry('LW', Entry::NUMBER, false, true, 1003);
//         $this->addEntry('LC', Entry::INTEGER, false, true, 1003);
//         $this->addEntry('LJ', Entry::INTEGER, false, true, 1003);
//         $this->addEntry('ML', Entry::NUMBER, false, true, 1003);
//         //$this->addEntry('D', Entry::ARRAY, false, true, 1003);
//         //$this->addEntry('RI', Entry::NAME, false, true, 1003);
//         $this->addEntry('OP', Entry::BOOLEAN, false, true, 1000);
//         $this->addEntry('op', Entry::BOOLEAN, false, true, 1003);
//         $this->addEntry('OPM', Entry::INTEGER, false, true, 1003);
//         //$this->addEntry('Font', Entry::ARRAY, false, true, 1003);
//         //$this->addEntry('BG', Entry::FUNCTION, false, true, 1000);
//         //$this->addEntry('BG2', [Entry::FUNCTION, Entry::NAME], false, true, 1003);
//         //$this->addEntry('UCR', Entry::FUNCTION, false, true, 1000);
//         //$this->addEntry('UCR2', [Entry::FUNCTION, Entry::NAME], false, true, 1003);
//         //$this->addEntry('TR', [Entry::FUNCTION, Entry::NAME, Entry:ARRAY], false, true, 1000, 2000);
//         //$this->addEntry('TR2', [Entry::FUNCTION, Entry::NAME, Entry:ARRAY], false, true, 1003, 2000);
//         //$this->addEntry('HT', [Entry::DICTIONARY, Entry::STREAM, Entry:NAME], false, true, 1000, 2000);
//         //$this->addEntry('FL', Entry::NUMBER, false, true, 1003);
//         //$this->addEntry('SM', Entry::NUMBER, false, true, 1003);
//         $this->addEntry('SA', Entry::BOOLEAN, false, true, 1000);
//         $this->addEntry('BM', [Entry::NAME, Entry::ARRAY => 'ExtGState_BMArray'], false, true, 1004);
//         $this->addEntry('SMask', [Entry::DICTIONARY => 'ExtGState_SMaskDictionary', Entry::NAME], false, true, 1004);
//         $this->addEntry('CA', Entry::NUMBER, false, true, 1004);
//         $this->addEntry('ca', Entry::NUMBER, false, true, 1004);
//         $this->addEntry('AIS', Entry::BOOLEAN, false, true, 1004);
//         //$this->addEntry('TK', Entry::BOOLEAN, false, true, 1004);
//         //$this->addEntry('UseBlackPtComp', Entry::NAME, false, true, 1004);
//         //$this->addEntry('HTO', Entry::ARRAY, false, true, 2000);

//         if ($src) {
//             $src->SetLinkedObject($this);
//             $this->setData($src);
//         }
//     }
// }

// class Resource_ExtGStateDictionary extends GenericDictionary
// {
//     public function __construct(?PDFObjectDictionary $src = null)
//     {
//         $this->setElementType([Entry::DICTIONARY => 'ExtGStateDictionary'], false);

//         if ($src) {
//             $src->SetLinkedObject($this);
//             $this->setData($src);
//         }
//     }
// }












/**
 * Classe che rappresenta un Lab colour space dictionary come specificato a
 * pagina 189 del pdf 2.0
 */
// class RangeArray extends BaseArray
// {
//     public function __construct(?PDFObjectArray $src = null)
//     {
//         $this->setElementType(Entry::NUMBER, true);

//         if ($src) {
//             $src->SetLinkedObject($this);
//             foreach ($src as $elem) {
//                 $this->addElement($elem);
//             }
//         }
//     }
// }

// class Resource_ColorSpaceLabDictionary extends BaseDictionary
// {
//     public function __construct(?PDFObjectDictionary $src = null)
//     {
//         $this->addEntry('WhitePoint', [Entry::ARRAY => 'RangeArray'], true, true, 1000);
//         $this->addEntry('BlackPoint', [Entry::ARRAY => 'RangeArray'], false, true, 1000);
//         $this->addEntry('Range', [Entry::ARRAY => 'RangeArray'], false, true, 1000);

//         if ($src) {
//             $src->SetLinkedObject($this);
//             $this->setData($src);
//         }
//     }
// }

// class Resource_ColorSpaceDeviceNNamesArray extends BaseArray
// {
//     public function __construct(?PDFObjectArray $src = null)
//     {
//         $this->setElementType(Entry::NAME, true);

//         if ($src) {
//             $src->SetLinkedObject($this);
//             foreach ($src as $elem) {
//                 $this->addElement($elem);
//             }
//         }
//     }
// }

// class Resource_ColorSpaceDeviceNAttributesDictionary extends BaseDictionary
// {
//     public function __construct(?PDFObjectDictionary $src = null)
//     {
//         $this->addEntry('Subtype', Entry::NAME, false, true, 1006);
//         //$this->addEntry('Colorants', Entry::NAME, false, true, 1006);
//         //$this->addEntry('Process', Entry::NAME, false, true, 1006);
//         //$this->addEntry('MixingHints', Entry::NAME, false, true, 1006);

//         if ($src) {
//             $src->SetLinkedObject($this);
//             $this->setData($src);
//         }
//     }
// }


// class Resource_ColorSpace extends BaseArray
// {
//     /**
//      * Versione minima della versione del pdf, può cambiare in base al tipo di color space
//      */
//     private $min_ver = 1000;

//     // vedi pagina 189 del pdf 20
//     public function labColourSpace($dictionary)
//     {
//         $this->min_ver = 1001;
//         $this->array = [
//             new Entry(Entry::normalizeEntryType(Entry::NAME, true), true, 1001, 999999),
//             new Entry(Entry::normalizeEntryType([Entry::DICTIONARY => 'Resource_ColorSpaceLabDictionary'], true), true, 1001, 999999),
//         ];

//         $this->array[0]->setValue('Lab');
//         $this->array[1]->setValue($dictionary);
//     }

//     // vedi pagina 199 del pdf 20
//     public function indexedColourSpace($base, $hival, $lookup)
//     {
//         $this->min_ver = $lookup->GetFinalType() == Entry::BYTE_STRING ? 1002 : 1001;

//         $this->array = [
//             new Entry(Entry::normalizeEntryType(Entry::NAME, true), true, 1001, 999999),
//             new Entry(Entry::normalizeEntryType([Entry::NAME, Entry::ARRAY => 'Resource_ColorSpace'], true), true, 1001, 999999),
//             new Entry(Entry::normalizeEntryType(Entry::INTEGER, true), true, 1001, 999999),
//             new Entry(Entry::normalizeEntryType([Entry::STREAM, Entry::BYTE_STRING], [Entry::STREAM => false, Entry::BYTE_STRING => true]), true, 1001, 999999),
//         ];

//         $this->array[0]->setValue('Indexed');
//         $this->array[1]->setValue($base);
//         $this->array[2]->setValue($hival);
//         $this->array[3]->setValue($lookup);
//     }

//     // vedi pagina 203 del pdf 20
//     public function deviceNColourSpace($names, $alternateSpace, $tintTransform, $attributes)
//     {
//         $this->min_ver = 1003;

//         $this->array = [
//             new Entry(Entry::normalizeEntryType(Entry::NAME, true), true, 1001, 999999),
//             new Entry(Entry::normalizeEntryType([Entry::ARRAY => 'Resource_ColorSpaceDeviceNNamesArray'], true), true, 1003, 999999),
//             new Entry(Entry::normalizeEntryType([Entry::NAME, Entry::ARRAY => 'Resource_ColorSpace'], true), true, 1003, 999999),
//             //new Entry(Entry::normalizeEntryType(Entry::FUNCTION, true), true, 1000, 999999),
//             new Entry(Entry::normalizeEntryType(Entry::TEXT_STRING, true), true, 1000, 999999),
//         ];

//         $this->array[0]->setValue('DeviceN');
//         $this->array[1]->setValue($names);
//         $this->array[2]->setValue($alternateSpace);
//         //$this->array[3]->setValue($tintTransform);

//         foreach ($tintTransform->GetFinalValue() as $k => $v) {
//             print $k . PHP_EOL;
//         }

//         //print_r($tintTransform->GetFinalValue());
//         die();

//         if ($attributes) {
//             //throw new \Exception('TODO: implementare l\'alternate dictionary');
//             $this->array[] = new Entry(Entry::normalizeEntryType([Entry::DICTIONARY => 'Resource_ColorSpaceDeviceNAttributesDictionary'], true), true, 1006, 999999);
//             $this->array[4]->setValue($attributes);
//         }



//         //try {
//         //    $e->setValue($elem);
//         //} catch (EntrySetValueException $ex) {
//         //    throw new \Exception("Unable to set value in " . $this->arrayName() . ': ' . $ex->getMessage());
//         //}




//     }




//     public function __construct(?PDFObjectArray $src = null)
//     {
//         if ($src) {
//             $count = count($src);
//             if ($count == 0 || $src[0]->getFinalType() != IPDFObject::TYPE_NAME) {
//                 throw new \Exception('Unable to parse ColorSpace: unable to read family name');
//             }

//             switch ($src[0]->getFinalValue()) {
//                 //case 'DeviceGray': // (PDF 1.1)
//                 //case 'DeviceRGB': // (PDF 1.1) 
//                 //case 'DeviceCMYK': // (PDF 1.1) 
//                 //case 'CalGray': // (PDF 1.1) 
//                 //case 'CalRGB': // (PDF 1.1)

//                 case 'Lab': // (PDF 1.1) 
//                     if ($count != 2) {
//                         throw new \Exception('Unable to parse ColorSpace: wrong \'Lab\' family array size');
//                     }
//                     $this->labColourSpace($src[1]);
//                     break;

//                 //case 'ICCBased': // (PDF 1.3)

//                 case 'Indexed': // (PDF 1.1)
//                     if ($count != 4) {
//                         throw new \Exception('Unable to parse ColorSpace: wrong \'Indexed\' family array size');
//                     }
//                     $this->indexedColourSpace($src[1], $src[2], $src[3]);
//                     break;

//                 //case 'Pattern': // (PDF 1.2)
//                 //case 'Separation': // (PDF 1.2)

//                 case 'DeviceN': // (PDF 1.3)
//                     if ($count != 4 && $count != 5) {
//                         throw new \Exception('Unable to parse ColorSpace: wrong \'DeviceN\' family array size');
//                     }
//                     $this->deviceNColourSpace($src[1], $src[2], $src[3], $count == 5 ? $src[4] : null);
//                     break;

//                 default:
//                     throw new \Exception('Unable to parse ColorSpace: unknown family name (' . $src[0]->getFinalValue() . ')');
//             }
//         }
//     }

//     public function appendObject(array &$objs, bool $inline): int
//     {
//         $ver = parent::appendObject($objs, $inline);
//         return $this->min_ver > $ver ? $this->min_ver : $ver;
//     }
// }

// class Resource_ColorSpaceDictionary extends GenericDictionary
// {
//     public function __construct(?PDFObjectDictionary $src = null)
//     {
//         $this->setElementType([Entry::NAME, Entry::ARRAY => 'Resource_ColorSpace'], false);

//         if ($src) {
//             $src->SetLinkedObject($this);
//             $this->setData($src);
//         }
//     }
// }




/**
 * Classe che rappresenta un group XObjects come specificato a pagina 274 del
 * pdf 2.0
 */
// class GroupXObjectDictionary extends BaseDictionary
// {
//     public function __construct(?PDFObjectDictionary $src = null)
//     {
//         $this->addEntry('Type', Entry::NAME, false, true, 1000);
//         $this->addEntry('S', Entry::NAME, true, true, 1000);

//         // campi specifici per i transparency group (pagina 421)
//         $this->addEntry('CS', Entry::NAME /*[Entry::NAME, Entry::ARRAY => '???']*/, false, true, 1000);
//         $this->addEntry('I', Entry::BOOLEAN, false, true, 1000);
//         $this->addEntry('K', Entry::BOOLEAN, false, true, 1000);

//         if ($src) {
//             $src->SetLinkedObject($this);
//             $this->setData($src);
//         }
//     }
// }


/**
 * Classe che rappresenta un external object come specificato a
 * pagina 253 del pdf 2.0
 */
// class XObjectStream_DecodeArray extends BaseArray
// {
//     public function __construct(?PDFObjectArray $src = null)
//     {
//         $this->setElementType(Entry::NUMBER, true);

//         if ($src) {
//             $src->SetLinkedObject($this);
//             foreach ($src as $elem) {
//                 $this->addElement($elem);
//             }
//         }
//     }
// }

// class XObjectStream extends Stream
// {
//     public function __construct(?PDFObjectDictionary $src = null)
//     {
//         $this->addEntry('Type', Entry::NAME, false, true, 1000);
//         $this->addEntry('Subtype', Entry::NAME, false, true, 1000);

//         // entryes specific to image dictionary (pag 258)
//         $this->addEntry('Width', Entry::INTEGER, false, true, 1000);
//         $this->addEntry('Height', Entry::INTEGER, false, true, 1000);
//         $this->addEntry('ColorSpace', [Entry::NAME, Entry::ARRAY => 'Resource_ColorSpace'], false, true, 1000);
//         $this->addEntry('BitsPerComponent', Entry::INTEGER, false, true, 1000);
//         $this->addEntry('Intent', Entry::NAME, false, true, 1001);
//         $this->addEntry('ImageMask', Entry::BOOLEAN, false, true, 1000);
//         //$this->addEntry('Mask', Entry::BOOLEAN, false, true, 1003);
//         $this->addEntry('Decode', [Entry::ARRAY => 'XObjectStream_DecodeArray'], false, true, 1000);
//         //$this->addEntry('Interpolate', Entry::BOOLEAN, false, true, 1000);
//         //$this->addEntry('Alternates', Entry::BOOLEAN, false, true, 1003);
//         $this->addEntry('SMask', [Entry::STREAM => 'XObjectStream'], false, false, 1004);
//         //$this->addEntry('SMaskInData', Entry::BOOLEAN, false, true, 1005);
//         $this->addEntry('Name', Entry::NAME, false, true, 1000, 2000);


//         // entries specific to Type 1 form dictionary (pag 272)
//         $this->addEntry('FormType', Entry::INTEGER, false, true, 1000);
//         $this->addEntry('BBox', Entry::RECTANGLE, false, true, 1000);
//         $this->addEntry('Matrix', [Entry::ARRAY => 'Matrix'], false, true, 1000);
//         $this->addEntry('Resources', [Entry::DICTIONARY => 'ResourcesDictionary'], false, true, 1002);
//         $this->addEntry('Group', [Entry::DICTIONARY => 'GroupXObjectDictionary'], false, true, 1004);

//         if ($src) {
//             $src->SetLinkedObject($this);
//             $this->setStreamData($src, false);
//         }
//     }

//     public function appendObject(array &$objs, bool $inline): int
//     {
//         $ver = parent::appendObject($objs, $inline);
//         if ($ver < 1005 && $this->BitsPerComponent == 16) {
//             $ver = 1005;
//         }
//         return $ver;
//     }
// }


/**
 * Classe che rappresenta un resource dictionary come specificato a pagina 113 del file ISO_32000-2-2020_sponsored.pdf
 */
class ResourcesDictionary extends BaseDictionary
{
    private ?IResourcesWriter $resourcesWriter = null;

    public function __construct(PDF $pdf, PDFObjectDictionary|IResourcesWriter|null $src = null)
    {
        $this->pdf = $pdf;

        // il Type non c'è nelle specifiche, ma ho visto dei PDF che ce l'avevano, mettendolo deprecato dalla versione
        // 1.0, faccio in modo che se è presente, non venga copiato in uscita, ma almeno non da errore in lettura
        $this->addEntry('Type', Entry::NAME, false, true, 1000, 1000);

        //$this->addEntry('ExtGState', [Entry::DICTIONARY => 'Resource_ExtGStateDictionary'], false, true, 1000);
        //$this->addEntry('ColorSpace', [Entry::DICTIONARY => 'Resource_ColorSpaceDictionary'], false, true, 1000);
        //$this->addEntry('Pattern', [Entry::DICTIONARY => ''], false, true, 1000);
        //$this->addEntry('Shading', [Entry::DICTIONARY => ''], false, true, 1003);
        $this->addEntry('XObject', [Entry::DICTIONARY => ResourcesDictionary_XObjectDictionary::class], false, true, 1000);
        $this->addEntry('Font', [Entry::DICTIONARY => ResourcesDictionary_FontDictionary::class], false, true, 1000);
        $this->addEntry('ProcSet', [Entry::ARRAY => ResourcesDictionary_ProcSetArray::class], false, true, 1000, 2000);
        //$this->addEntry('Properties', [Entry::DICTIONARY => ''], false, true, 1002);

        if ($src instanceof PDFObjectDictionary) {
            $src->SetLinkedObject($this);
            $this->setData($src);
        } else if ($src instanceof IResourcesWriter)
            $this->resourcesWriter = $src;
    }

    public function isInLine(): ?bool
    {
        if ($this->resourcesWriter)
            return $this->resourcesWriter->isResourcesInLine();
        return parent::isInLine();
    }

    public function appendObject(array &$objs, bool $inline): int
    {
        if ($this->resourcesWriter)
            return $this->resourcesWriter->appendResources($objs, $inline);
        return parent::appendObject($objs, $inline);
    }

    public function getInlineObject(int $ver): string
    {
        if ($this->resourcesWriter)
            return $this->resourcesWriter->getInlineResourcesObject($ver);
        return parent::getInlineObject($ver);
    }

    public function getPDFObject(int $ver, int $offset, int $objNumber): string
    {
        if ($this->resourcesWriter)
            return $this->resourcesWriter->getResourcesPDFObject($ver, $offset, $objNumber);
        return parent::getPDFObject($ver, $offset, $objNumber);
    }

    public function getObjectReference(): string
    {
        if ($this->resourcesWriter)
            return $this->resourcesWriter->getResourcesObjectReference();
        return parent::getObjectReference();
    }

    public function getObjectOffset(int $objNumber): int
    {
        if ($this->resourcesWriter)
            return $this->resourcesWriter->getResourcesObjectOffset($objNumber);
        return parent::getObjectOffset($objNumber);
    }
}
