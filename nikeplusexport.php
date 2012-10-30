<?php

/**
 * A PHP class that makes it easy to get your gps data from the Nike+ service
 * 
 * NikePlusExport requires PHP 5 with SimpleXML and cURL.
 * You must have the nikeplusphp file located in the same directory.
 * 
 * @author Nicolas Guelpa - http://nicolas.guelpa.me
 * @link https://bitbucket.org/cheesemacfly/nikeplusexport
 * @version 1.0
 */

require_once 'nikeplusphp.4.5.php';

class NikePlusExport extends NikePlusPHP {
    /**
     * max/min for longitude and latitude as defined here: http://www.topografix.com/GPX/1/1/
     */
    const MINLAT = -90;
    const MAXLAT = 90;
    const MINLON = -180;
    const MAXLON = 180;
    /**
     * Returns an activity in JSON format
     * @param type $activity
     * @return string
     */
    public function toJSON($activity)
    {
        return json_encode($activity);
    }
    
    /**
     * Returns an activity in GPX format
     * @param type $activity
     * @return string
     */
    public function toGPX($activity)
    {   
        if(!$activity->gps) return NULL;
        
        $startTime = new DateTime($activity->startTimeUtc, new DateTimeZone($activity->timeZoneId));
        
        //prepare GPX
        $xml = new XMLWriter();
        $xml->openMemory();
        $xml->setIndent(true);
        $xml->startDocument("1.0", "UTF-8");

        $xml->startElement('gpx');  
        $xml->writeAttribute('version', '1.1');
        $xml->writeAttribute('creator', 'NikePlusExport');
        $xml->writeAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $xml->writeAttribute('xmlns', 'http://www.topografix.com/GPX/1/1');
        $xml->writeAttribute('xsi:schemaLocation', 'http://www.topografix.com/GPX/1/1 http://www.topografix.com/GPX/1/1/gpx.xsd');

        //metadata
        $xml->startElement('metadata');
        $xml->writeElement('name', $activity->name);
        $xml->writeElement('desc', isset($activity->tags->note) ? $activity->tags->note : $activity->name);
        $xml->writeElement('time', date('Y-m-d\TH:i:s'));
        $xml->writeElement('link', 'https://bitbucket.org/cheesemacfly/nikeplusexport');
        
        //set min/max lat/lng
        $minLat = self::MAXLAT;
        $maxLat = self::MINLAT;
        $minLon = self::MAXLON;
        $maxLon = self::MINLON;
        foreach($activity->geo->waypoints as $wp) {
                if($wp->lon > $maxLon) $maxLon = $wp->lon;
                if($wp->lon < $minLon) $minLon = $wp->lon;
                if($wp->lat > $maxLat) $maxLat = $wp->lat;
                if($wp->lat < $minLat) $minLat = $wp->lat;
        }
        $xml->startElement('bounds');
        $xml->writeAttribute('maxlon', $maxLon);
        $xml->writeAttribute('minlon', $minLon);
        $xml->writeAttribute('maxlat', $maxLat);
        $xml->writeAttribute('minlat', $minLat);
        
        $xml->endElement();//EO bounds        
        $xml->endElement();//EO metadata

        //track
        $xml->startElement('trk');    
        $xml->writeElement('name', 'trkName ' . time());
        $xml->writeElement('type', 'Run');         

        $xml->startElement('trkseg');
                
        $distance = 0;
        $lastLat = self::MINLAT - 1;
        $lastLon = self::MINLON - 1;
        foreach($activity->geo->waypoints as $wp) {
            $xml->startElement('trkpt');
            $xml->writeAttribute('lat', $wp->lat);
            $xml->writeAttribute('lon', $wp->lon);
            $xml->writeElement('ele', $wp->ele);
                        
            //get total distance done at this waypoint
            if($lastLat >= self::MINLAT && $lastLon >= self::MINLON)
                $distance += self::_distanceKM($lastLat, $lastLon, $wp->lat, $wp->lon);

            //calculate the waypoint time            
            $timeSpan = $activity->duration / 1000;
            foreach($activity->history[1]->values as $index => $value)
            {
                if($value >= $distance && $value > 0)
                {
                    $timeSpan = 10 * (($distance * $index) / $value);
                    break;
                }
            }
            $xml->writeElement('time', gmdate('Y-m-d\TH:i:s', $startTime->getTimestamp() + round($timeSpan)));
            $xml->writeElement('src', $activity->deviceType);

            $xml->endElement();//EO trkpt

            $lastLat = $wp->lat;
            $lastLon = $wp->lon;
        }

        $xml->endElement(); //EO trkseg
        $xml->endElement(); //EO trk


        $xml->endElement(); //EO gpx       


        $xml->endDocument();
        
        return $xml->outputMemory();
    }
    
    /**
     * Returns an activity in TCX format
     * @param type $activity
     * @return string
     */
    public function toTCX($activity)
    {
        return NULL;
    }
    
    /**
     * Calculates the distance in kilometers between 2 lat/lon points using haversine formula
     * @param type $lat1
     * @param type $lon1
     * @param type $lat2
     * @param type $lon2
     * @return float
     */
    private function _distanceKM($lat1, $lon1, $lat2, $lon2)
    {
        return self::_distance(6371, $lat1, $lon1, $lat2, $lon2);
    }

    /**
     * Calculates the distance in miles between 2 lat/lon points using harvesine formula
     * @param type $lat1
     * @param type $lon1
     * @param type $lat2
     * @param type $lon2
     * @return float
     */
    private function _distanceMILES($lat1, $lon1, $lat2, $lon2)
    {
        return self::_distance(3959, $lat1, $lon1, $lat2, $lon2);
    }
    
    /**
     * Calculates the distance between 2 lat/lon points using haversine formula
     * @param type $radius
     * @param type $lat1
     * @param type $lon1
     * @param type $lat2
     * @param type $lon2
     * @return float
     */
    private function _distance($radius, $lat1, $lon1, $lat2, $lon2)
    {
        $lat1Deg = deg2rad($lat1);
        $lon1Deg = deg2rad($lon1);
        $lat2Deg = deg2rad($lat2);
        $lon2Deg = deg2rad($lon2);
        
        $a = cos($lat2Deg) * cos($lat1Deg) * cos($lon1Deg - $lon2Deg) + sin($lat2Deg) * sin($lat1Deg);
        
        return -1 <= $a && $a <= 1 ? ($radius * acos($a)) : NULL;
    }
}