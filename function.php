<?php
function is($text) {
    return preg_match('/[А-Яа-яЁё]/u', $text);
}
