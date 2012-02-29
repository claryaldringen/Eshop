<?php

/**
 * @license LGPL
 */

class EditableColumn extends NObject{

    /**
     * Parent
     * @var EditableDatagrid
     */
    public $parent;

    /**
     * Type of column
     * @var string
     */
    public $type;

    /**
     * NForm control
     * @var NFormNControl
     */
    public $formNControl;

    /**
     * Column name
     * @var string
     */
    public $columnName;

    /**
     * Dictionary of column. For columns with known values.
     * @var array
     */
    public $dictionary = array();

    function  __toString() {
        $fNControl = $this->formNControl->control;
        $fNControl->id = null;
        $fNControl->name = null;
        return $fNControl->__toString();
    }
}