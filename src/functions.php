<?php

function abort($message) {
    echo 'Fatal error: "' . $message . '". Exiting.' . PHP_EOL;
    exit(1);
}

/* Works as ctype_print(), but with unicode support.

Returns true if every character in text will actually create output (including blanks).
Returns false if text contains control characters or characters that do not have any output or control function at all.
*/
function ctype_print_utf(string $string): bool {
    return preg_match('/[[:cntrl:]]/', $string) === 0;
}
