<?php

/**
 * IInvoiceParticipant - part of Invoice control plugin for Nette Framework.
 *
 * @copyright  Copyright (c) 2009 Ondřej Brejla
 * @license    New BSD License
 * @link       http://nettephp.com/cs/extras/invoice-control
 * @package    Nette\Extras
 * @version    0.3
 */
interface IInvoiceParticipant {

    /**
     * Returns the name of participant.
     *
     * @return string
     */
    public function getName();

    /**
     * Sets the street of participant.
     *
     * @param string $street
     * @return void
     */
    public function getStreet();

    /**
     * Returns the house number of participant.
     *
     * @return string
     */
    public function getHouseNumber();

    /**
     * Returns the city of participant.
     *
     * @return string
     */
    public function getCity();

    /**
     * Returns the zip of participant.
     *
     * @return string
     */
    public function getZip();

    /**
     * Returns the identification number of participant.
     *
     * @return string
     */
    public function getIn();

    /**
     * Returns the tax identification number of participant.
     *
     * @return string
     */
    public function getTin();

    /**
     * Returns the account number of participant.
     *
     * @return string
     */
    public function getAccountNumber();

}
