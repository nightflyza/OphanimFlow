<?php

class OphanimClassifier {
         
    /**
     * 
     * @var array
     */
    protected $baseStruct=array(
        'time'=>0,
        'total'=>0,
        'icmp'=>0,
        'udp'=>0,
        'tcp'=>0,
        'mail'=>0,
        'dns'=>0,
        'vpn'=>0,
        'ftp'=>0,
        'web'=>0,
        'rtsp'=>0,
        'quic'=>0,
    );


    protected $tcpProto=array(
        80=>'web',
        443=>'web',
        21=>'ftp',
        20=>'ftp',
        110=>'mail',
        25=>'mail',
        143=>'mail',
        587=>'mail',
        53=>'dns',
        554=>'rtsp',
    );

    protected $udpProto=array(
        53=>'dns',
        1701=>'vpn',
        51820=>'vpn',
        554=>'rtsp',
        443=>'quic',
        80=>'quic',
    );

    /**
     * some predefined stuff here
     */
    const DATA_PATH='gdata/';
    const DELIMITER=';';
    const TABLE_RAW_IN='raw_in';
    const TABLE_RAW_OUT='raw_out';
    

    public function __construct() {
   
    }

    protected function flushSource($dataSource) {
        nr_query('TRUNCATE TABLE `'.$dataSource.'`');
    }

    public function getBaseStruct() {
        $result=array();
        if (!empty($this->baseStruct)) {
            foreach ($this->baseStruct as $class=>$io) {
                if ($class!='time') {
                $result[]=$class;
                }
            }
        }
        return($result);
    }

   
    public function aggregateSource($dataSource,$ipColumn,$portColumn) {
        $result=array();
        $rawData=array();
        $databaseLayer=new NyanORM($dataSource);
        $rawData=$databaseLayer->getAll();

       if (!empty($rawData)) {
            foreach ($rawData as $io=>$each) {
                $ip=$each[$ipColumn];
                $time=$each['stamp_inserted'];
                $proto=$each['ip_proto'];
                $port=$each[$portColumn];

                if (!isset($result[$ip][$time])) {
                    $result[$ip][$time]=$this->baseStruct;
                }
                
                $result[$ip][$time]['time']=$time;
                $result[$ip][$time]['total']+=$each['bytes'];

                switch ($proto) {
                    case 'icmp':
                        $result[$ip][$time]['icmp']+=$each['bytes'];
                    break;

                    case 'tcp':
                        $result[$ip][$time]['tcp']+=$each['bytes'];
                        if (isset($this->tcpProto[$port])) {
                            $result[$ip][$time][$this->tcpProto[$port]]+=$each['bytes'];
                        }
                    break;

                    case 'udp':
                        $result[$ip][$time]['udp']+=$each['bytes'];
                        if (isset($this->udpProto[$port])) {
                            $result[$ip][$time][$this->udpProto[$port]]+=$each['bytes'];
                        }
                    break;                        

                    case 'gre':
                        $result[$ip][$time]['vpn']+=$each['bytes'];
                    break;

                    case 'esp':
                        $result[$ip][$time]['vpn']+=$each['bytes'];
                    break;
                    
                    default:

                    break;
                }
            }
        }
        $this->flushSource($dataSource);
      //  debarr($result);
        return($result);
    }


    public function saveAggregatedData($direction,$aggregatedData) {
        if (!empty($aggregatedData)) {
            $fnamePrefix=self::DATA_PATH.$direction.'_';
                foreach ($aggregatedData as $eachIp=>$eachTimeStamp) {
                    $fileToSave=$fnamePrefix.$eachIp;
                    $line='';
                    if (!empty($eachTimeStamp)) {
                     foreach ($eachTimeStamp as $timeStamp=>$lineData) {
                        $line='';
                        $line.=implode(self::DELIMITER,$lineData);
                        $line.=PHP_EOL;
                        file_put_contents($fileToSave,$line,FILE_APPEND);
                     }
                    }
                }
        }
    }

}
