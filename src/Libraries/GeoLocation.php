<?php namespace Sygecon\AdminBundle\Libraries;

use Config\Services;

class GeoLocation {
	protected $centerLat = 55.75119;
	protected $centerLon = 37.61541;
//--------------------------------------------------------------------       
	protected function getCurl (string $url) {
		if (isset($url) && $url) {
			$client = Services::curlrequest();
			$response = $client->request('GET', toUrl($url)); 
			$body = $response->getBody();
			return jsonDecode($body);
		} return [];
	}
        
	public function getgeoip () {
		$res['ip'] = Services::request()->getIPAddress();
		$mear = $this->getCurl ('//ip-api.com/json/' . (string) $res['ip'] . '?lang=ru');
		if (isset($mear->lat) && $mear->lat && isset($mear->lon) && $mear->lon) {
			$res['lat'] = round((float) $mear->lat,7);
			$res['lon'] = round((float) $mear->lon,7);
			$res['timezone'] = (string) $mear->timezone;
			$res['country'] = (string) $mear->country;
			$res['countryCode'] = (string) $mear->countryCode;
			$res['region'] = (string) $mear->region;
			$res['regionName'] = (string) $mear->regionName;
			$res['city'] = (string) $mear->city;
			$res['zip'] = (string) $mear->zip;
			$res['isp'] = (string) $mear->isp;
		}
		if (isset($res['lat']) && $res['lat'] && isset($res['lon']) && $res['lon'])
			$res['distance'] = $this->getDistanceFromLatLonInKm ($this->meLat, $this->meLon, $res['lat'], $res['lon']);        
		return json_encode($res);
	} 
 
    public function getAddressFromCoord ($lat, $lon) {
		if (isset($lat) && $lat && isset($lon) && $lon && is_numeric($lat) && is_numeric($lon)) 
			return $this->getCurl ('https://nominatim.openstreetmap.org/reverse?format=json&lat=' . (string)(float) $lat . '&lon=' . (string)(float) $lon . '&zoom=18&addressdetails=1&accept-language=ru');    
		return [];
	} 
 
    public function getCoordFromAddress (string $address = '') {
		$address = str_replace(' ', '%20', str_replace('  ', ' ', trim($address)));
		if (isset($address) && $address)     
			return $this->getCurl ('https://nominatim.openstreetmap.org/search?q=' . $address . '&format=json&addressdetails=1&limit=1&polygon_svg=1&accept-language=ru');
		return [];
	} 
 
	public function getOnlyAddress ($lat, $lon) {
		$sres = [];
		$sres['postcode']  = 123456; 
		$sres['state']     = '';
		$sres['city']      = '';   
		$sres['address']   = '';
		$sres['latitude']  = $this->centerLat;
		$sres['longitude'] = $this->centerLon;
		if (isset($lat) && $lat && isset($lon) && $lon && is_numeric($lat) && is_numeric($lon)) { 
			$loc = $this->getAddressFromCoord ($lat, $lon); 
			if (isset($loc) && $loc) {
				$sres['postcode'] = $loc->address->postcode; 
				$sres['state'] = $loc->address->state;
				$sres['city'] = $loc->address->city;   
				$sres['address'] = $loc->address->road . ', ' . $loc->address->house_number;
				$sres['latitude'] = $loc->lat;
				$sres['longitude'] = $loc->lon;
			}
		}
		return $sres;
	}

    protected function getDistanceFromLatLonInKm ($lat1, $lon1, $lat2, $lon2) {
		$R = 6371; // Radius of the earth in km
		$dLat = $this->deg2rad($lat2 - $lat1);  // deg2rad below
		$dLon = $this->deg2rad($lon2 - $lon1); 
		$a = sin ($dLat/2) * sin ($dLat/2) + cos ($this->deg2rad($lat1)) * cos ($this->deg2rad($lat2)) * sin ($dLon/2) * sin ($dLon/2); 
		$c = 2 * atan2 (sqrt ($a), sqrt (1 - $a)); 
		$d = $R * $c; // Distance in km
		return round($d, 0, PHP_ROUND_HALF_UP);
	}

    protected function deg2rad ($deg) {
		return $deg * (M_PI / 180);
    } 
}