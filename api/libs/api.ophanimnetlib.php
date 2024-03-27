<?php

/**
 * Represents a basic network address subroutines library.
 */
class OphanimNetLib {

    /**
     * Contains all available networks array
     *
     * @var array
     */
    protected $allNets = array();

    public function __construct($loadNets = true) {
        if ($loadNets) {
            $this->loadNets();
        }
    }

    /**
     * Loads the networks by retrieving all networks from the OphanimMgr class.
     */
    protected function loadNets() {
        $settings = new OphanimMgr();
        $this->allNets = $settings->getAllNetworks();
    }

    /**
     * Returns array with range start and end IP from IP address with CIDR notation
     *
     * @param string $ipcidr
     * @param bool $excludeNetworkAddr
     * @param bool $excludeBroadcastAddr
     *
     * @return array startip/endip
     */
    public function ipcidrToStartEndIP($ipcidr, $excludeNetworkAddr = false, $excludeBroadcastAddr = false) {
        $range = array();
        $ipcidr = explode('/', $ipcidr);
        $startip = (ip2long($ipcidr[0])) & ((-1 << (32 - (int) $ipcidr[1])));
        $endip = $startip + pow(2, (32 - (int) $ipcidr[1])) - 1;
        $startip = ($excludeNetworkAddr ? $startip + 1 : $startip);
        $endip = ($excludeBroadcastAddr ? $endip - 1 : $endip);

        $range['startip'] = long2ip($startip);
        $range['endip'] = long2ip($endip);

        return ($range);
    }

    /**
     * Converts CIDR mask into decimal like 24 => 255.255.255.0
     * 
     * @param int $mask_bits
     * 
     * @return string 
     */
    public function cidr2mask($mask_bits) {
        if ($mask_bits > 31 || $mask_bits < 0)
            return ("0.0.0.0");
        $host_bits = 32 - $mask_bits;
        $num_hosts = pow(2, $host_bits) - 1;
        $netmask = ip2long("255.255.255.255") - $num_hosts;
        return long2ip($netmask);
    }

    /**
     * Checks is some IP between another two
     * 
     * @param string $user_ip
     * @param string $ip_begin
     * @param string $ip_end
     * 
     * @return bool
     */
    public function isIpBetween($ip, $ip_begin, $ip_end) {
        return (ip2int($ip) >= ip2int($ip_begin) && ip2int($ip) <= ip2int($ip_end));
    }


    /**
     * Checks if an IP is within a CIDR network
     *
     * @param string $ip
     * @param string $cidr
     *
     * @return bool
     */
    public function isIpInCidr($ip, $cidr) {
        list($network, $mask) = explode('/', $cidr);
        $networkStart = ip2long($network) & ~((1 << (32 - $mask)) - 1);
        $networkEnd = $networkStart + pow(2, (32 - $mask)) - 1;
        $ipLong = ip2long($ip);

        return ($ipLong >= $networkStart && $ipLong <= $networkEnd);
    }


    /**
     * Retrieves the network description for a given IP address.
     *
     * @param string $ip The IP address to retrieve the network description for.
     * 
     * @return string
     */
    public function getIpNetDescription($ip) {
        $result = '';
        if (!empty($this->allNets)) {
            foreach ($this->allNets as $netId => $eachNetData) {
                if ($this->isIpInCidr($ip, $eachNetData['network'])) {
                    $netDesc = $eachNetData['network'];
                    if (isset($eachNetData['descr'])) {
                        if (!empty($eachNetData['descr'])) {
                            $netDesc = $eachNetData['descr'];
                        }
                    }
                    $result = $netDesc;
                    break;
                }
            }
        }
        return ($result);
    }
}
