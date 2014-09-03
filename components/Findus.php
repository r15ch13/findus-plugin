<?php namespace Radiantweb\Findus\Components;

use Cms\Classes\ComponentBase;
use Request;
use Cache;
use Lang;

class Findus extends ComponentBase
{

    /**
     * @var string
     */
    public $map_template;

    /**
     * @var string
     */
    public $address;

    /**
     * @var string
     */
    public $location_title;

    /**
     * @var string
     */
    public $address1;

    /**
     * @var string
     */
    public $city;

    /**
     * @var string
     */
    public $state_zip;

    /**
     * @var string
     */
    public $link_text;

    /**
     * @var string
     */
    public $link_text_marker;

    /**
     * @var string
     */
    public $color;

    /**
     * @var string
     */
    public $lat;

    /**
     * @var string
     */
    public $lon;

    public function componentDetails()
    {
        return [
            'name'        => 'Findus',
            'description' => 'Displays a simple map & or directions link on your page.'
        ];
    }

    public function defineProperties()
    {
        return [
            'location_title' => [
                'description' => 'Title of Location',
                'title'       => 'Location Title',
                'default'     => '',
                'type'        => 'string'
            ],
            'address' => [
                'description' => 'Your location address',
                'title'       => 'Address',
                'default'     => '',
                'type'        => 'string'
            ],
            'template' => [
                'description' => 'Type of map/link to use',
                'title'       => 'Map Style',
                'default'     => 'map_only',
                'type'        => 'dropdown',
                'options'     => [
                    'map_only'       => 'Large Map',
                    'map_info_right' => 'Large Map (Info Right)',
                    'info_small_map' => 'Small Map',
                    'link_only'      => 'Link only'
                ]
            ],
            'color' => [
                'description' => 'Color Hue',
                'title'       => 'Color Hue',
                'default'     => 'none',
                'type'        => 'dropdown',
                'options'     => [
                    'none'   => 'none',
                    'black'  => 'black',
                    'red'    => 'red',
                    'blue'   => 'blue',
                    'green'  => 'green',
                    'yellow' => 'yellow',
                    'purple' => 'purple',
                    'brown'  => 'brown',
                    'grey'   => 'grey'
                ]
            ]
        ];
    }

    public function onRun()
    {
        $this->addCss('/plugins/radiantweb/findus/assets/css/findus.css');
        $this->addJs('/modules/backend/assets/js/vendor/jquery-2.0.3.min.js');

        $this->addCss('/plugins/radiantweb/findus/assets/fancybox/jquery.fancybox.css');
        $this->addJs('/plugins/radiantweb/findus/assets/fancybox/jquery.fancybox.pack.js');

        $this->map_template = $this->property('template');

        $findus_latlon = Cache::get('findus_latlon_'.$this->page->id.$this->alias);
        $findus_address = Cache::get('findus_address_'.$this->page->id.$this->alias);
        $findus_formatted_address = Cache::get('findus_formatted_address_'.$this->page->id.$this->alias);

        if(!$findus_latlon || $findus_address != $this->property('address')){
            $coords = $this->getGeoCode($this->property('address'));
            $findus_latlon = $coords['lat'].','.$coords['lon'];
            $findus_formatted_address = $coords['formatted'];
            Cache::forever('findus_latlon_'.$this->page->id.$this->alias, $findus_latlon);
            Cache::forever('findus_address_'.$this->page->id.$this->alias, $this->property('address'));
            Cache::forever('findus_formatted_address_'.$this->page->id.$this->alias, $findus_formatted_address);
        }

        $coords = explode(',', $findus_latlon);
        $this->address = $this->property('address');
        $this->location_title = $this->property('location_title');

        $address_array = explode(',', $findus_formatted_address);
        $this->address1 = isset($address_array[0]) ? $address_array[0] : '';
        $this->city = isset($address_array[1]) ? $address_array[1] : '';
        $this->state_zip = isset($address_array[2]) ? $address_array[2] : '';

        $colors = array(
            'none'=>'none',
            'black'=>'black',
            'red'=>'#ff0000',
            'blue'=>'#0077ff',
            'green'=>'#22ff00',
            'yellow'=>'#ffc300',
            'purple'=>'#aa00ff',
            'brown'=>'#ff6e00',
            'grey'=>'grey'
        );

        $this->link_text = Lang::get('radiantweb.findus::lang.link_text');
        $this->link_text_marker = Lang::get('radiantweb.findus::lang.link_text_marker');

        $this->color = $colors[strtolower($this->property('color'))];
        $this->lat = $coords[0];
        $this->lon = $coords[1];
    }

    private function getGeoCode($address)
    {
        //build url
        $base_url = "http://maps.google.com/maps/api/geocode/json?sensor=false";
        $request_url = $base_url . "&address=".urlencode($address);

        // Get cURL resource
        $curl = curl_init();
        // Set some options - we are passing in a useragent too here
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $request_url,
            CURLOPT_USERAGENT => 'October CMS Site'
        ));
        // Send the request & save response to $resp
        $reslt = curl_exec($curl);

        $res = json_decode($reslt);

        switch($res->status) {
            case 'OK':
                $lat = $res->results[0]->geometry->location->lat;
                $lng = $res->results[0]->geometry->location->lng;
                //var_dump($address_array);exit;
                return array('lat'=>$lat, 'lon'=>$lng, 'formatted'=>$res->results[0]->formatted_address);
                break;
        }

        // Close request to clear up some resources
        curl_close($curl);
    }
}