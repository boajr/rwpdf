<?php

namespace Boajr\PDF\font;


class FontTable_cmap extends FontTable
{
    #[FontPropertyType(FontPropertyType::uint16)] public ?int $version = null;
    #[FontPropertyType(FontPropertyType::uint16)] public ?int $numTables = null;
    public array $encodingRecords = [];

    //#[FontPropertyType(FontPropertyType::uint16)]         public ?int $numGlyphs = null;
    //#[FontPropertyType(FontPropertyType::uint16)]         public ?int $glyphNameIndex[numGlyphs] = null;
    //#[FontPropertyType(FontPropertyType::uint8)]          public ?int $stringData[variable] = null;

    //#[FontPropertyType(FontPropertyType::uint16)]         public ?int $numGlyphs = null;
    //#[FontPropertyType(FontPropertyType::int8)]           public ?int $offset[numGlyphs] = null;

    private ?array $unicodeCharMap = null;

    public function __construct(Font $font, int $offset, int $length)
    {
        parent::__construct($font, $offset, $length);

        $this->seek(0);
        $this->parseProperty('version');
        $this->parseProperty('numTables');
        for ($i = 0; $i < $this->numTables; ++$i) {
            $this->encodingRecords[] = (object)unpack('nplatformID/nencodingID/NsubtableOffset', $this->read(8));
        }

        // prendo esempio dalla php-font-lib e tengo solo le sottotabelle con formato 2, 4, 12
        foreach ($this->encodingRecords as $idx => &$record) {
            $this->seek($record->subtableOffset);
            $format = $this->readUInt16();
            if (!in_array($format, [2, 4, 12/*, 13*/])) {
                unset($this->encodingRecords[$idx]);
                --$this->numTables;
                continue;
            }

            $record->format = $format;
        }

        $this->unicodeCharMap = $this->getUnicodeCharMap();

        //print_r($this->unicodeCharMap);
    }

    private function getUnicodeCharMap(): array
    {
        $encoding_record_16bit = null;

        // scorro tutti gli encoding, se ne trovo uno "completo" lo utilizzo
        foreach ($this->encodingRecords as $record) {
            if (($record->platformID == 0 && $record->encodingID >= 4) || ($record->platformID == 3 && $record->encodingID == 10))
                return $this->readSubTable($record->subtableOffset, $record->format, null);

            if (!$encoding_record_16bit && ($record->platformID == 0 || ($record->platformID == 3 && $record->encodingID == 1)))
                $encoding_record_16bit = $record;
        }

        if ($encoding_record_16bit)
            return $this->readSubTable($encoding_record_16bit->subtableOffset, $encoding_record_16bit->format, null);

        $system_encodings = array_change_key_case(array_fill_keys(mb_list_encodings(), true), CASE_UPPER);
        foreach ($this->encodingRecords as $record) {
            $encoding = null;
            switch ($record->platformID) {
                case 3:
                    switch ($record->encodingID) {
                        case 2:
                            if (array_key_exists("SJIS", $system_encodings))
                                $encoding = "SJIS";
                            break;
                        case 3:
                            if (array_key_exists("GB18030", $system_encodings))
                                $encoding = "GB18030";
                            break;
                        case 4:
                            if (array_key_exists("BIG-5", $system_encodings))
                                $encoding = "BIG-5";
                            break;
                        case 5:
                            if (array_key_exists("UHC", $system_encodings))
                                $encoding = "UHC";
                            break;
                        case 6:
                            if (array_key_exists("CP1361", $system_encodings))
                                $encoding = "CP1361";
                            break;
                    }
                    break;
            }
            if ($encoding)
                return $this->readSubTable($record->subtableOffset, $record->format, $encoding);
        }

        return [];
    }

    private function readSubTable(int $offset, int $format, ?string $encoding): array
    {
        switch ($format) {
            case 2:
                /*
          $subtable += $font->unpack(self::$subtable_v2_format);

          $subHeaderKeys = array_map(function($val) { return $val / 8; }, $font->readUInt16Many(256));
          $subHeaders = array();

          $glyphIdArray = array();
          $maxSubHeaderIndex = max($subHeaderKeys);
          for ($i = 0; $i <= $maxSubHeaderIndex; $i++) {
            $subHeader = $font->unpack(self::$subtable_v2_format_subheader);
            $offset = $font->pos();
            $subHeader["glyphIdArrayOffset"] = $offset + $subHeader["idRangeOffset"] - 2;
            $subHeaders[$i] = $subHeader;

            if (!\array_key_exists($subHeader["glyphIdArrayOffset"], $glyphIdArray) || count($glyphIdArray[$subHeader["glyphIdArrayOffset"]]) < $subHeader["entryCount"]) {
              $font->seek($subHeader["glyphIdArrayOffset"]);
              $glyphIdArray[$subHeader["glyphIdArrayOffset"]] = $font->readUInt16Many($subHeader["entryCount"]);
              $font->seek($offset);
            }
          }

          $glyphIndexArray = array();
          foreach ($subHeaderKeys as $highByte => $subHeaderKey) {
            $subHeader = $subHeaders[$subHeaderKey];
            if ($subHeaderKey === 0) {
              $c = $highByte;
              if ($c < $subHeader["firstCode"] || $c >= ($subHeader["firstCode"] + $subHeader["entryCount"])) {
                $glyphIndexArray[$c] = 0;
                continue;
              }
              $c = $highByte;
              $index = $c - $subHeader["firstCode"];
              $glyphId = $glyphIdArray[$subHeader["glyphIdArrayOffset"]][$index];
              if ($glyphId === 0) {
                $glyphIndexArray[$c] = 0;
              } else {
                $glyphIndexArray[$c] = ($glyphId + $subHeader["idDelta"]) & 0xFFFF;
              }
            } else {
              for ($index = 0; $index < $subHeader["entryCount"]; $index++) {
                $c = null;
                $lowByte = $subHeader["firstCode"] + $index;
                $c = (($highByte & 0xFF) << 8) | ($lowByte & 0xFF);
                $glyphId = $glyphIdArray[$subHeader["glyphIdArrayOffset"]][$index];
                if ($glyphId === 0) {
                  $glyphIndexArray[$c] = 0;
                } else {
                  $glyphIndexArray[$c] = ($glyphId + $subHeader["idDelta"]) & 0xFFFF;
                }
              }
            }
          }

          $subtable += array(
            "subHeaderKeys" => $subHeaderKeys,
            "subHeaders" => $subHeaders,
            "glyphIdArray" => $glyphIdArray,
            "glyphIndexArray" => $glyphIndexArray
          );
          break;
*/
                throw new FontException('TODO: leggere il formato 2');

            case 4:
                $this->seek($offset + 2);
                $subtable = (object)unpack('nlength/nlanguage/nsegCountX2/nsearchRange/nentrySelector/nrangeShift', $this->read(12));

                $segCount = $subtable->segCountX2 / 2;

                $endCode = $this->readUInt16Many($segCount);
                $this->skip(2); // reservedPad
                $startCode = $this->readUInt16Many($segCount);
                $idDelta = $this->readInt16Many($segCount);
                $ro_start = $this->pos();
                $idRangeOffset = $this->readUInt16Many($segCount);

                $glyphIndexArray = [];
                for ($i = 0; $i < $segCount; $i++) {
                    $c1 = $startCode[$i];
                    $c2 = $endCode[$i];
                    $d  = $idDelta[$i];
                    $ro = $idRangeOffset[$i];

                    for ($c = $c1; $c <= $c2; $c++) {
                        if ($c === 0xFFFF)
                            continue;

                        if ($ro == 0) {
                            $gid = ($c + $d) & 0xFFFF;
                        } else {
                            $gid = 0;
                            if ($this->seek($ro_start + 2 * $i + ($c - $c1) * 2 + $ro) == 0)
                                $gid = $this->readUInt16();
                            $gid = ($gid + $d) & 0xFFFF;
                        }

                        $glyphIndexArray[$c] = $gid;
                    }
                }

                return $glyphIndexArray;

            case 12:
                $this->seek($offset + 4);
                $subtable = (object)unpack('Nlength/Nlanguage/Nngroups', $this->read(12));

                $glyphIndexArray = [];
                for ($p = 0; $p < $subtable->ngroups; $p++) {
                    $startCode = $this->readUInt32();
                    $endCode = $this->readUInt32();
                    $startGlyphCode = $this->readUInt32();

                    for ($c = $startCode; $c <= $endCode; $c++) {
                        $glyphIndexArray[$c] = $startGlyphCode;
                        $startGlyphCode++;
                    }
                }

                return $glyphIndexArray;
        }

        return [];









        /*




                $glyphIndexArray = array();
                foreach ($_subtable["glyphIndexArray"] as $c => $gid) {
                    $str = trim(pack("N", $c));
                    if (\strlen($str) > 0) {
                        $ord = $this->uniord($str, $encoding);
                        if ($ord > 0) {
                            $glyphIndexArray[$ord] = $gid;
                        }
                    }
                }
                return $glyphIndexArray;






  private function uniord (string $c, ?string $encoding = null) {
    if (function_exists("mb_ord")) {
      if (PHP_VERSION_ID < 80000 && $encoding === null) {
          // in PHP < 8 the encoding argument, if supplied, must be a valid encoding
          $encoding = "UTF-8";
      }
      return mb_ord($c, $encoding);
    }

    if ($encoding != "UTF-8" && $encoding !== null) {
      $c = mb_convert_encoding($c, "UTF-8", $encoding);
    }

    $length = mb_strlen(mb_substr($c, 0, 1), '8bit');
    $ord = false;
    $bytes = [];
    $numbytes = 1;
    for ($i = 0; $i < $length; $i++) {
      $o = \ord($c[$i]); // get one string character at time
      if (\count($bytes) === 0) { // get starting octect
        if ($o <= 0x7F) {
          $ord = $o;
          $numbytes = 1;
        } elseif (($o >> 0x05) === 0x06) { // 2 bytes character (0x06 = 110 BIN)
          $bytes[] = ($o - 0xC0) << 0x06;
          $numbytes = 2;
        } elseif (($o >> 0x04) === 0x0E) { // 3 bytes character (0x0E = 1110 BIN)
          $bytes[] = ($o - 0xE0) << 0x0C;
          $numbytes = 3;
        } elseif (($o >> 0x03) === 0x1E) { // 4 bytes character (0x1E = 11110 BIN)
          $bytes[] = ($o - 0xF0) << 0x12;
          $numbytes = 4;
        } else {
          $ord = false;
          break;
        }
      } elseif (($o >> 0x06) === 0x02) { // bytes 2, 3 and 4 must start with 0x02 = 10 BIN
          $bytes[] = $o - 0x80;
          if (\count($bytes) === $numbytes) {
            // compose UTF-8 bytes to a single unicode value
            $o = $bytes[0];
            for ($j = 1; $j < $numbytes; $j++) {
              $o += ($bytes[$j] << (($numbytes - $j - 1) * 0x06));
            }
            if ((($o >= 0xD800) and ($o <= 0xDFFF)) or ($o >= 0x10FFFF)) {
              // The definition of UTF-8 prohibits encoding character numbers between
              // U+D800 and U+DFFF, which are reserved for use with the UTF-16
              // encoding form (as surrogate pairs) and do not directly represent
              // characters.
              return false;
            } else {
              $ord = $o; // add char to array
            }
            // reset data for next char
            $bytes = [];
            $numbytes = 1;
          }
      } else {
        $ord = false;
        break;
      }
    }

    return $ord;
  }


*/
    }

    /**
     * restituisce la posizione del carattere richiesto o 0 se il carattere non è presente
     */
    public function getCharPosition(string $string, ?string $encoding = null): int
    {
        $ord = mb_ord($string, $encoding);
        return $this->unicodeCharMap[$ord] ?? 0;
    }
}
