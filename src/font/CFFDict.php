<?php

namespace Boajr\PDF\font;


abstract class CFFDict
{
    public function __construct(string $data)
    {
        $len = strlen($data);
        $i = 0;

        $ops = [];

        while ($i < $len) {
            $ord = ord($data[$i++]);

            if ($ord >=0 && $ord <=21) {
                // è un operando
                if ($ord == 12) {
                    if ($i >= $len)
                        throw new FontException('Found a two bytes operator but there is no more data available.');
                    $due = ord($data[$i++]);
                } else
                $due = 0;

                $this->operator($ord, $due, $ops);
                $ops = [];
                continue;
            }

            $num = 0;
            if ($ord >= 32 && $ord <= 246) {
                $num = $ord - 139;
            } else if ($ord >= 247 && $ord <= 250) {
                if ($i >= $len)
                    throw new FontException('Found an operand encoding that requires more data than availables.');
                $num = ($ord - 247) * 256 + ord($data[$i++]) + 108;
            } else if ($ord >= 251 && $ord <= 254) {
                if ($i >= $len)
                    throw new FontException('Found an operand encoding that requires more data than availables.');
                $num = - ($ord - 251) * 256 - ord($data[$i++]) - 108;
            } else if ($ord == 28) {
                if ($i + 1 >= $len)
                    throw new FontException('Found an operand encoding that requires more data than availables.');
                $num = (ord($data[$i++]) << 8) + ord($data[$i++]);
            } else if ($ord == 29) {
                if ($i + 3 >= $len)
                    throw new FontException('Found an operand encoding that requires more data than availables.');
                $num = (ord($data[$i++]) << 24) + (ord($data[$i++]) << 16) + (ord($data[$i++]) << 8) + ord($data[$i++]);
            } else if ($ord == 30) {
                // A real number
                $v = [1, 0.0, 0, false, 0, 0, false];
                do {
                    $ord = ord($data[$i++]);
                    if (!$this->estrai_real($v, ($ord >> 4) & 0xf))
                        break;
                    if (!$this->estrai_real($v, $ord & 0xf))
                        break;
                } while ($i < $len);
                $num = $v[0] * $v[1] * (10 ** ($v[2] + $v[4] * $v[5]));
            } else {
                throw new FontException('Invalid operator/operand encoding.');
            }

            $ops[] = $num;
        }
    }

    protected abstract function operator(int $op1, int $op2, array $params);

    private function estrai_real(array &$v, int $n): bool
    {
        if ($n >= 0 && $n <= 9) {
            if ($v[6]) {
                $v[5] = $v[5] * 10 + $n;
            } else {
                $v[1] = $v[1] * 10 + $n;
                if ($v[3])
                    --$v[2];
            }
            return true;
        }

        if ($n == 10) {
            $v[3] = true;
            return true;
        }

        if ($n == 11) {
            $v[4] = 1;
            $v[6] = true;
            return true;
        }

        if ($n == 12) {
            $v[4] = -1;
            $v[6] = true;
            return true;
        }

        if ($n == 14) {
            $v[0] = -1;
            return true;
        }

        return false;
    }
}
