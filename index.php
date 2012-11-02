<?php
    //Imports
    require_once 'nikeplusexport.php';
    require_once 'config.php';

    //Creates the object
    $n = new NikePlusExport($username, $password);
    foreach ($n->activities() as $activity)
    {
        if ($activity->gps)
        {
            //Activities need to be loaded one by one to get the geo data
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
                break;
            }
        }
    }