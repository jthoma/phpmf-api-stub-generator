# phpmf-api-stub-generator
# Added cw.php code writer on 30 May, 2025

The code writer is for rapid development of  rest-api backend.
So far it is tested with the help of large language generated openapi defenition for rest api. And successfully generates the route configuration in appropriate places and the handler.js is updated properly. All handler code stub is generated, it needs business logic to be added. I will be working on the same as and when time permits.

A sample openapi rest defenition is also added to this deployment which was created from chatGPT using the following prompt.

Suggest a JSON defenition for a user management rest api, with preferably these functionalities. - Create, Update, Delete, Login for these actions the preferred properties being Name, email or mobile, Password . For the specified actions only choose absolute necessary properties from the suggested list.

*cw-base/* This folder contains the initialization for the code writer extension, essentially copy the contents from this folder to your development folder and use that as the *target foler* as explained below.

# To use it

Navigate to this directory and invoke

php -q cw.php path to open_api_defenition.json  *target folder*
The *taget folder* should have contents from *cw-base/*

The code in *target folder* will be modified according to the api defenition

handlers will be written into *target folder*/plugins
*target folder*/index.php will be created or overwritten with the route configurations
*target folder*/plugins will need proper business logic to be integrated. 
class and public methods will be handling the routing with methods being 
passed one argument $p having url embedded variables if any and $payload 
captures the post body json using helper methods from the framework.


# Real Time 
Just to state it I have used this one to create a 8 action rest-api for a 
production project recently with the stub generation from openapi taking 
just 7 seconds and my business logic integration was done in an hour. And it
was submitted to the client as a hosted solution on an aws EC2 instance. 
The beauty was that I did it on my Samsung Galaxy M14 phone with termux
and php installed. [Checkout my termux scripts](../code-collection/termux) 
Now I am working on a possible solution where I can run this on aws lambda 
by maintaing a php-fpm runtime ready for phpmf

* Update on June 26, the above project's client had found someone who has
deployed the full backend on Google as Serverless App without any workaround

