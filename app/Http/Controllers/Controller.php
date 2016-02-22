<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use GuzzleHttp;
use SoapClient;
use Illuminate\Http\Request;

use SimpleXMLElement;
use Input;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function index()
    {
        $client = new GuzzleHttp\Client(['base_url' => 'https://flydev.rocketroute.com']);
        $res = $client->post('https://flydev.rocketroute.com/remote/auth', ['form_params' =>  ['req' =>'<?xml version="1.0" encoding="UTF-8"?>
<AUTH>
  <USR>zavyalovroman@gmail.com</USR>
  <PASSWD>535cef66a1b4fd1e6f2b05b18cc2a2a4</PASSWD>
  <DEVICEID>e138231a68ad82f054e3d756c6634ba1</DEVICEID>
  <PCATEGORY>RocketRoute</PCATEGORY>
  <APPMD5>3RlYIIyapJicemgsBZcy</APPMD5>
</AUTH>']]);
        echo $res->getStatusCode(); // 200
        echo $res->getBody();
    }

    public function soap(Request $request)
    {
        $icao = $request->input('icao');

        $request = '<?xml version="1.0" encoding="UTF-8" ?>
<REQNOTAM>
<USR>zavyalovroman@gmail.com</USR>
<PASSWD>535cef66a1b4fd1e6f2b05b18cc2a2a4</PASSWD>
<ICAO>'.$icao.'</ICAO>
</REQNOTAM>';

       $client = new SoapClient('https://apidev.rocketroute.com/notam/v1/service.wsdl');
        $response = $client->getNotam($request);
        $responseAr = array();

        $notams = new SimpleXMLElement($response);
        foreach ($notams->NOTAMSET as $notam) {
            foreach ($notam as $nm) {
                $itemQ = $nm->ItemQ;
                $coordinates = explode("/",str_replace(" ","",$itemQ))[7];
               $coordinatesLatLng = $this->parse($coordinates);
                if(!array_key_exists($coordinates, $responseAr))
                {
                    $html = "";
                    $doc = new SimpleXMLElement($response);
                    foreach ($doc->NOTAMSET as $nn) {
                        foreach ($nn as $item) {
                            $coord = explode("/",str_replace(" ","",$item->ItemQ))[7];

                            if($coord == $coordinates) {
                                $html .= "Q: " . $item->ItemQ . "<br/>";
                                $html .= "A: " . $item->ItemA . "<br/>";
                                $html .= "B: " . $item->ItemB . "<br/>";
                                $html .= "C: " . $item->ItemC . "<br/>";
                                $html .= "D: " . $item->ItemD . "<br/>";
                                $html .= "E: " . $item->ItemE . "<br/>";
                                $html .= "<hr>";
                            }
                        }
                    }
                    $responseAr[$coordinates] = ['lat' => $coordinatesLatLng['lat'],
                                                    'lng' => $coordinatesLatLng['lng'],
                                                    'message' => $html];
                }
            }
        }
        return Response(json_encode($responseAr), '200')->header('Content-Type', 'application/json');
    }

    function parse($coord)
    {
        $ret['lat'] = $this->degree2decimal(substr($coord, 0,5));
        $ret['lng'] = $this->degree2decimal(substr($coord, 6,5));;
        return $ret;
    }
    function degree2decimal($deg_coord="")
    {
        $direction = substr(strrev($deg_coord),0,1);

        $degrees=substr($deg_coord,0,2);
        $minutes=substr($deg_coord,2,2);
        $seconds=0;
        $seconds = ($seconds/60);
        $minutes = $minutes + $seconds;
        $minutes =($minutes/60);
        $decimal = $degrees+$minutes;
        if (($direction=="S") or ($direction=="W"))
        { $decimal=$decimal*(-1);}
        return $decimal;
    }
}
