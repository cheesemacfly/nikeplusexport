**This repo is not maintained and the script most likely doesn't work anymore since [Nike+PHP is dead](https://nikeplusphp.org/).**

## About ##

NikePlusExport is a script built to help you export your Nike+ gps activities.  
The script supports JSON, GPX and TCX as output formats.  
This project has been mainly inspired by https://gist.github.com/3121560.

**This script does NOT use the [nike+ api](http://developer.nike.com/) to retrieve the activites**

## Requirements ##

This script is based on the excellent Nike+PHP http://nikeplusphp.org/.  
It has been successfully tested with nikeplusphp.4.5.php.  
To run correctly, it needs PHP 5 with cURL and JSON.

## Installation ##

Simply run, assuming you have installed composer.phar or composer binary:

    $ composer require ngpp/nikeplusexport

## Examples ##

Here's an example of code to export all your runs:

    #!php
    <?php
        //Creates the object
        $n = new \Nike\Export($username, $password);
        foreach ($n->activities() as $activity)
        {
            if ($activity->gps)
            {
                //Activities need to be loaded one by one to get the gps data
                $activity = $n->activity($activity->activityId)->activity;
                //Create JSON file
                if(!is_null($jsonContent = $n->toJSON($activity)))
                {
                    file_put_contents($activity->activityId . '.json', $jsonContent);
                }
                //Create GPX file
                if(!is_null($gpxContent = $n->toGPX($activity)))
                {
                    file_put_contents($activity->activityId . '.gpx', $gpxContent);
                }
                //Create TCX file
                if(!is_null($tcxContent = $n->toTCX($activity)))
                {
                    file_put_contents($activity->activityId . '.tcx', $tcxContent);
                }
            }
        }

## Information and contact ##

The script outputs respect the following implementations:

* GPX format: http://www.topografix.com/gpx.asp
* TCX format: http://developer.garmin.com/schemas/tcx/v2/

The gps data stored by the Nike+ application doesn't contain the time. It is calculated and could not be 100% accurate.

If you have any problem using the script or want to see new features, please send an email to nikeplusexport@guelpa.me
