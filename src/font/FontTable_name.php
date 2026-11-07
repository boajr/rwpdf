<?php

namespace Boajr\PDF\font;


class FontTable_name extends FontTable
{
    #[FontPropertyType(FontPropertyType::uint16)]    public ?int $version = null;
    #[FontPropertyType(FontPropertyType::uint16)]    public ?int $count = null;
    #[FontPropertyType(FontPropertyType::Offset16)]  public ?int $storageOffset = null;
    public array $nameRecord = [];
    #[FontPropertyType(FontPropertyType::uint16)]    public ?int $langTagCount = null;
    public array $langTagRecord = [];


    private array $system_encodings;
    private array $strings = [];

    public function __construct(Font $font, int $offset, int $length)
    {
        parent::__construct($font, $offset, $length);

        $this->system_encodings = array_change_key_case(array_fill_keys(mb_list_encodings(), true), CASE_UPPER);

        $this->seek(0);
        $this->parseProperty('version');
        $this->parseProperty('count');
        $this->parseProperty('storageOffset');
        for ($i = 0; $i < $this->count; ++$i) {
            $this->nameRecord[] = (object)unpack('nplatformID/nencodingID/nlanguageID/nnameID/nlength/nstringOffset', $this->read(12));
        }

        if ($this->version >= 1) {
            $this->parseProperty('langTagCount');
            for ($i = 0; $i < $this->langTagCount; ++$i)
                $this->langTagRecord[] = (object)unpack('nlength/nlangTagOffset', $this->read(4));
        }

        //print_r($this);

        //$this->getString(0);
        $this->getString(1);
        $this->getString(2);
        $this->getString(4);
        $this->getString(16);
        $this->getString(17);
        $this->getString(6);
    }

    public function getString(int $nameID): ?string
    {
        if (!array_key_exists($nameID, $this->strings)) {
            $str = [];

            foreach ($this->nameRecord as $nr) {
                if ($nr->nameID == $nameID) {
                    $encoding = null;
                    switch ($nr->platformID) {
                        case 0:
                            if (array_key_exists("UTF-16", $this->system_encodings))
                                $encoding = "UTF-16";
                            break;

                        case 3:
                            switch ($nr->encodingID) {
                                case 2:
                                    if (array_key_exists("SJIS", $this->system_encodings))
                                        $encoding = "SJIS";
                                    break;
                                case 3:
                                    if (array_key_exists("GB18030", $this->system_encodings))
                                        $encoding = "GB18030";
                                    break;
                                case 4:
                                    if (array_key_exists("BIG-5", $this->system_encodings))
                                        $encoding = "BIG-5";
                                    break;
                                case 5:
                                    if (array_key_exists("UHC", $this->system_encodings))
                                        $encoding = "UHC";
                                    break;
                                case 6:
                                    if (array_key_exists("CP1361", $this->system_encodings))
                                        $encoding = "CP1361";
                                    break;
                                default:
                                    if (array_key_exists("UTF-16", $this->system_encodings))
                                        $encoding = "UTF-16";
                                    break;
                            }
                            break;
                    }

                    $this->seek($this->storageOffset + $nr->stringOffset);
                    $nr->string = mb_convert_encoding($this->read($nr->length), "UTF-8", $encoding);
                    if (strpos($nr->string, "\0") !== false)
                        $nr->string = str_replace("\0", "", $nr->string);

                    if (!array_key_exists($nr->platformID, $str))
                        $str[$nr->platformID] = [];

                    if (!array_key_exists($nr->encodingID, $str[$nr->platformID]))
                        $str[$nr->platformID][$nr->encodingID] = [];

                    $str[$nr->platformID][$nr->encodingID][$nr->languageID] = $nr->string;


                    //echo str_pad($nameID, 2, ' ', STR_PAD_LEFT) . ' - ' . $nr->platformID . ' - ' . $nr->encodingID . ' - ' . str_pad($nr->languageID, 4, ' ', STR_PAD_LEFT) . ' - ' . $nr->string . PHP_EOL;
                }
            }

            // windows english, unicode, mac english, windows, mac
            // 3-10-1033 (windows english)
            // 3-1-1033 (windows english)
            // 0-*-*
            // 1-0-0 (mac english)
            // 3-*-*
            // 1-*-*

            if ($str[3][10][1033] ?? false)
                $this->strings[$nameID] = $str[3][10][1033];
            elseif ($str[3][1][1033] ?? false)
                $this->strings[$nameID] = $str[3][1][1033];
            elseif ($str[0] ?? false)
                $this->strings[$nameID] = $this->my_array_first($this->my_array_first($str[0]));
            elseif ($str[1][0][0] ?? false)
                $this->strings[$nameID] = $str[1][0][0];
            elseif ($str[3] ?? false)
                $this->strings[$nameID] = $this->my_array_first($this->my_array_first($str[3]));
            else
                $this->strings[$nameID] = $this->my_array_first($this->my_array_first($this->my_array_first($str)));
        }

        return $this->strings[$nameID];
    }

    private function my_array_first(?array $arr): mixed
    {
        if (!$arr || !count($arr))
            return null;
        $k = array_key_first($arr);
        return $arr[$k];
    }
}
