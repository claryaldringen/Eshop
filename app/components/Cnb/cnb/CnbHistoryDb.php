<?php
/**
 * momentálně nefunguje jak má
 * @author Hakuna
 * @deprecated
 */
class CnbHistoryDb extends Cnb
{
    const CNB_YEAR  ='http://www.cnb.cz/cs/financni_trhy/devizovy_trh/kurzy_devizoveho_trhu/rok.txt?rok=';
    const DATE      ='date';

    protected $history  =array();
    protected $property =array();
    protected $date     =false;

    /**
     * @var DibiConnection
     */
    protected $db;

    public function __construct($webMoney=0, $globaVat=false, $connection=0)
    {
        $this->fontSize =2;//DONT CHANGE
        parent::__construct($webMoney, $globaVat);
        $this->db   =dibi::getConnection($connection);
    }

    /**
     *
     * @param $date string CZECH FORMAT
     * @return date
     */
    static public function czechDate2Sql($date='01.01.1991')
    {
        $date   =explode('.', $date);
        return $date[2] .'-'. $date[1] .'-'. $date[0];
    }

    /**
     * upravi pole kde se budou vyskytovat pouze kody men, pro dalsi pouziti napr: selectCode()
     * @param array $item
     * @return void
     */
    private function onlyCode(&$item)
    {
        $to     =$this->strTo;
        $item   =$to(substr($item, -3));
    }

    /**
     * vytahne z databaze
     * @param array $onlyCode
     * @param string $culomn    -seznam sloupcu
     * @param bool $fetchAll
     * @return DibiRow
     */
    protected function selectCode(&$onlyCode, $culomn='*', $fetchAll=true)
    {
        $rows   =$this->db->select($culomn)
              ->from('c')
              ->where('[code]')
              ->in( $this->code2db($onlyCode) )
              ->orderBy('[code]')
              ->asc();

        if($fetchAll === true)
            return $rows->fetchAll();
        return $rows->fetchPairs();
    }

    /**
     *
     * @param array $onlyCode
     * @return string
     */
    protected function code2db(&$onlyCode)
    {
        return "('". implode("', '", $onlyCode) ."')";
    }

    /**
     * provede iteraci nad polem a upravi velikost hodnot
     * @param array $onlyCode
     * @return void
     */
    protected function arrayCode(&$onlyCode)
    {
        array_walk($onlyCode, array($this, 'onlyCode'));
    }

    /**
     * seznam ID v databazi
     * @param array $firstLine
     * @param array $onlyCode
     * @return DibiRow
     */
    protected function saveNewCode(&$firstLine, &$onlyCode)
    {
        $onlyCode   =$firstLine;
        $this->arrayCode($onlyCode);

        $insert =array();
        foreach($onlyCode as $key => $val)
        {
            list(
            $insert[Cnb::NUM_FORMAT][$key],
            $insert[Cnb::DECIMAL][$key],
            $insert[Cnb::DEC_POINT][$key],
            $insert[Cnb::THOUSANDS][$key],
            $insert[Cnb::CODE][$key],
            ) =$this->createFormat(strtoupper($val));
            $insert[Cnb::SYMBOL][$key]  =$this->setSymbol($insert[Cnb::NUM_FORMAT][$key]);
        }

        $this->db->query('INSERT IGNORE INTO [c] %m', $insert);

        return $this->selectCode($onlyCode, '[code], [idcurrency]', false);
    }

    /**
     * prohazuje hodnoty mezi sebou na zaklade podminiky vetsi
     * @param mixed $from
     * @param mixed $to
     * @return void
     */
    private function changeValue(&$from, &$to)
    {
        if($from > $to)
        {
            $maxYear    =$to;
            $to     =$form;
            $from   =$maxYear;
        }
    }

    /**
     * stahne listek od roku az, minimum je 1991
     * @param int -A full numeric representation of a year, 4 digits
     * @param int -A full numeric representation of a year, 4 digits
     * @return bool
     */
    public function downloadYear($from=1991, $to=true)
    {
        $maxYear    =(int)strftime('%Y');
        $to =($to === true)? $maxYear: (int)Math::interval($to, 1991, $maxYear);

        $from   =(int)Math::interval($from, 1991, $maxYear);

        $this->changeValue($from, $to);

        for($from; $from <= $to; $from++)
        {
            if(ini_get('allow_url_fopen'))
            {
                $cnb   =file_get_contents(self::CNB_YEAR . $from);
            }
            else
            {
                $curl   =new CUrl(self::CNB_YEAR . $from);
                $cnb    =$curl->getResult();
            }

            $list       =explode("\n", Math::stroke2point($cnb));
            $firstLine  =explode(parent::PIPE, $list[0]);

            unset($list[0], $firstLine[0]);
            array_pop($list);//odsttrani posledni radek vzdy je prazdny

            $idCode   =$this->saveNewCode($firstLine, $onlyCode);

            $this->db->begin();

            try
            {
                foreach($list as $line)
                {
                    $line   =explode(parent::PIPE, $line);

                    if(!is_numeric($line[1]))
                    {
                        unset($line[0]);
                        $firstLine  =$line;
                        $idCode     =$this->saveNewCode($firstLine, $onlyCode);
                        continue;
                    }

                    try
                    {
                        $this->db->query('INSERT INTO [c_history] ', array('date'=>self::czechDate2Sql($line[0])) );
                        $idYear =$this->db->getInsertId();
                    }
                    catch (DibiException $e)
                    {
                        if($e->getCode() === DibiMySqliDriver::ERROR_DUPLICATE_ENTRY)
                            continue;

                        throw $e;
                    }

                    $multi  =array();
                    foreach($firstLine as $key => $code)
                    {
                        $multi['idcurrency'][]  =$idCode[ $onlyCode[$key] ];
                        $multi['idc_history'][] =$idYear;
                        $multi['rate'][]        =$line[$key] / (float)substr($firstLine[$key], 0, -4);
                    }
                    $this->db->query('INSERT INTO [c_rate] %m', $multi);
                }
            }
            catch (DibiException $e)
            {
                $this->db->rollback();
                return $e;
            }

            $this->db->commit();
        }
        return true;
    }

    /**
     * @param DateTime|int|string $date -example: 2008, '2008', '2008-10-30'
     * @param mixed $from date
     * @param mixed $lowLine date
     * @return string YYYY-MM-DD
     */
    public static function setDate($date, $from=true, $lowLine=1991)
    {
        if(is_object($date))
        {
            if($date instanceof DateTime)
            {//object DateTime
                return $date->format('Y-m-d');
            }
            else
            {
                throw new InvalidArgumentException('This object does not supported!');
            }
        }
        elseif(is_numeric($date) && strlen( (int)$date ) === 4)
        {//jedna se o hodnotu roku
            $post   =($from == true)? '-01-01': '-12-31';
            return (int)Math::interval($date, $lowLine, (int)strftime('%Y')) . $post;
        }
        elseif(is_string($date))
        {//predpokladany format YYYY-MM-DD
            list($year, $month, $day) = explode('-', $date);
            if(!checkdate($month, $day, $year))
            {
                throw new InvalidArgumentException('This date format does not supported or it is invalidate! Let\'s try YYYY-MM-DD');
            }
            return date('Y-m-d', mktime(0, 0, 0, $month, $day, $year));
        }

        throw new InvalidArgumentException('This date format does not supported!');
    }

    /**
     * pripravi nacteni historie
     * @param $from -date
     * @param $to   -date
     * @param ...   -list of money code, default use parent::$defineMoney
     * @return bool
     */
    public function loadHistory($from=1991, $to=true/*, ...*/)
    {
        if($to === true)
        {
            $to =new DateTime('now -1 day');
        }
        $from   =self::setDate($from);
        $to     =self::setDate($to, false);
        $this->changeValue($from, $to);

        $arg    =array();
        if(func_num_args() > 2)
        {
            $arg    =func_get_args();
            unset($arg[0], $arg[1]);
        }
        else
            $arg[2] =false;

        $in     =null;
        if($arg[2] !== true)
        {
            $arg    =array_merge(array_keys(parent::$defineMoney), $arg);
            if(!empty($arg))
            {
                $this->arrayCode($arg);
                $in =' [c.code] IN '. $this->code2db($arg) .' AND ';
            }
        }

        $rows   =$this->db->query('SELECT [h.date], [c.code], [r.rate] FROM [c_history] AS h
                        LEFT JOIN [c_rate] AS r ON r.idc_history = h.idc_history
                        LEFT JOIN [c] ON c.idcurrency = r.idcurrency
                        WHERE '. $in .' [date] BETWEEN %d AND %d', $from, $to,
                        'ORDER BY [c.code] ASC, [h.date] ASC')->fetchAll();


        foreach($rows as $val)
        {
            $this->history[$val['code']][$val['date']]    =(float)$val['rate'];
        }

        $onlyCode   =array_keys($this->history);

        //nazvy sloupcu musi byt stejne jako konstanty
        $this->property =array_combine($onlyCode, $this->selectCode($onlyCode, '['. Cnb::NUM_FORMAT .'], ['. Cnb::CODE .'],
                                                ['. Cnb::DECIMAL .'], ['. Cnb::DEC_POINT .'],
                                                ['. Cnb::THOUSANDS .'], ['. Cnb::SYMBOL .']'));
        return true;
    }

    /**
     * vrati timestampe fotmatu YYYY-MM-DD
     * @param $date
     * @return int
     */
    public static function getTimeStamp($date)
    {
        list($year, $month, $day) = explode('-', $date);
        return mktime(0, 0, 0, $month, $day, $year);
    }

    /**
     * (non-PHPdoc)
     * @see cnb/Cnb#loadRating($code)
     */
    public function loadRating($code, $add = true)
    {
        $strTo  =$this->strTo;
        $code   =$strTo($code);
        if($this->date === false || strtoupper($code) === 'CZK')
        {
            if(isset($this->rating[$code][self::DATE]) && $this->date === false)
            {
                unset($this->rating[$code]);
            }
            return parent::loadRating($code);
        }

        $this->date =self::setDate($this->date);

        if(!(isset($this->rating[$code][self::DATE]) && $this->rating[$code][self::DATE] == $this->date));
        {
            $date   =$this->date;

            $limit  =@key($this->history[$code]);//nebudeli existovat odchyti se nize
            $diff   =0;
            if(!isset($this->history[$code][$this->date]))
            {
                if($limit > $this->date)
                {//chyba stary datum
                    $diff   =(int)( (self::getTimeStamp($limit) - self::getTimeStamp($this->date)) / Tools::DAY);
                    $this->date =$limit;
                }
                else
                {
                    $time   =new DateTime($this->date . ' -1 day');
                    while(!isset($this->history[$code][$time->format('Y-m-d')]) && $diff < 10)
                    {
                        $time->modify('-1 day');
                        $diff++;
                    }
                    $this->date =$time->format('Y-m-d');
                }
            }

            if($diff > 5)//tolerance 5 dnu, mozna dopsat automaticke nacitani hodnot
                throw new OutOfRangeException('The currency "'. $code .'" does not exists for this date "'. $date .'". You can try higger range by method '.__CLASS__.'::loadHistory()');

            $this->rating[$code]   =(array)$this->property[$code];
            $this->rating[$code][Cnb::RATE]    =$this->history[$code][$this->date];
            $this->rating[$code][self::DATE]   =$this->date;
        }

        return $code;
    }


    /**
     * (non-PHPdoc)
     * @see Cnb#change($price, $from, $to, $round)
     */
    public function change($price, $from=false, $to=false, $round=false, $date=true)
    {
        $this->setPropertyDate($date);
        $result =parent::change($price, $from, $to, $round);
        $this->setPropertyDate($date);

        return $result;
    }

    /**
     * (non-PHPdoc)
     * @see Cnb#format($number, $from, $to)
     */
    public function format($number, $from=false, $to=false, $vat=false, $date=true)
    {
        $this->setPropertyDate($date);
        $result =parent::format($number, $from, $to, $vat);
        $this->date =false;

        return $result;
    }

    /**
     * upravuje hodnotu $this->date pro dalsi pouziti
     * @param bool|string $date
     * @return void
     */
    public function setPropertyDate($date=true)
    {
        if($date === false)
        {
            $this->date =false;
        }
        elseif($date !== true)
        {
            $this->date =$date;
        }
    }

    /**
     * testovaci metoda vrati jaky je nejvetsi rozestup v datu, nyni je max 5
     *
     */
    public function tolerantion()
    {
        $row    =$this->db->select('*')->from('c_history')->orderBy('date')->asc()->fetchPairs('date', 'idc_history');
        $date   =new DateTime('1991-01-01');
        $max    =0;
        while($date->format('Y-m-d') != '2010-01-28')
        {
            $buffer =0;
            while(!isset($row[$date->format('Y-m-d')]))
            {
                $date->modify('+1 day');
                $buffer++;
            }
            if($buffer > $max)
                $max    =$buffer;
            $date->modify('+1 day');
        }
        return $max;
    }
}