<?php
class NonObject
{
    final public function __construct()
    {
        die('This class '. __CLASS__ .' cannot instance of object.');
    }
}