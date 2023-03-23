<?php

namespace Backup\Helper;

class Randomness {

    static public function getRandomString() {
        $length = 10;

        $chars = 'ABCDEFGHJKLMNPQRTUVWXY346789';

        $string = '';

        $clen = mb_strlen($chars) - 1;

        for ($i = 0; $i < $length; $i++) {
            $string .= $chars[ random_int(0, $clen) ];
        }

        return $string;
    }

}
