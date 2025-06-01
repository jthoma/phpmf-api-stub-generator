<?php 

/**
 * Rest API %cw_date_time%
 */

require "./MF.php";

/* urilmask is expected to have the sub directory from 
 application server docroot */
MF::set('urimask' ,'/api');

// just source the file. 
MF::addon('libFunctions');  

// hook this function defined in the addons/libFunctions.php 
// to the event before-handler ie, the function will be called
// when ever a route is identified and will execute only for 
// defined routes
MF::addaction('before-handler','jsonContentHeader');


%Defined_Routes_and_Handlers%


// if request is undefined, just output unauthorized
// since we are hanlding an api
MF::run('', 403);
