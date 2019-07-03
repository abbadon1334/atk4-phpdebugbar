[![Build Status](https://travis-ci.org/abbadon1334/atk4-phpdebugbar.svg?branch=master)](https://travis-ci.org/abbadon1334/atk4-fastroute)
[![Codacy Badge](https://api.codacy.com/project/badge/Grade/757dd5a567944d4e97cc00f9c4a437b2)](https://www.codacy.com/app/abbadon1334/atk4-phpdebugbar?utm_source=github.com&amp;utm_medium=referral&amp;utm_content=abbadon1334/atk4-phpdebugbar&amp;utm_campaign=Badge_Grade)
[![Codacy Badge](https://api.codacy.com/project/badge/Coverage/757dd5a567944d4e97cc00f9c4a437b2)](https://www.codacy.com/app/abbadon1334/atk4-phpdebugbar?utm_source=github.com&utm_medium=referral&utm_content=abbadon1334/atk4-phpdebugbar&utm_campaign=Badge_Coverage)

# atk4-phpdebugbar
ATK4 With PHPDebugBar

#### First step :

install via composer :
`composer require abbadon1334/atk4-phpdebugbar`

#### Use it in ATK
add to atk4\ui\App, just after initLayout :
```php
$debugBar = $app->add(
    new ATK4PHPDebugBar\DebugBar()
)
```
#### Configure ATKDebugBar - Assets loading

PHPDebugBar needs to load his own assets (JS and CSS), you need to set the correct relative url :

```php
$debugBar->setAssetsResourcesUrl('http://localhost/test');
/*
this will load :
 
 #CSS
 - http://localhost/test/vendor/maximebf/debugbar/src/DebugBar/Resources/vendor/font-awesome/css/font-awesome.min.css
 - http://localhost/test/vendor/maximebf/debugbar/src/DebugBar/Resources/vendor/highlightjs/styles/github.css
 - http://localhost/test/vendor/maximebf/debugbar/src/DebugBar/Resources/debugbar.css
 - http://localhost/test/vendor/maximebf/debugbar/src/DebugBar/Resources/widgets.css
 - http://localhost/test/vendor/maximebf/debugbar/src/DebugBar/Resources/openhandler.css
 
 #JS
 - http://localhost/test/vendor/maximebf/debugbar/src/DebugBar/Resources/vendor/highlightjs/highlight.pack.js
 - http://localhost/test/vendor/maximebf/debugbar/src/DebugBar/Resources/debugbar.js
 - http://localhost/test/vendor/maximebf/debugbar/src/DebugBar/Resources/widgets.js
 - http://localhost/test/vendor/maximebf/debugbar/src/DebugBar/Resources/openhandler.js
*/ 
``` 

##### Configure phpdebugbar resource assets loading :
 
the asset url is composed by 3 parts : relative,

example : http://localhost/test/vendor/maximebf/debugbar/src/DebugBar/Resources/vendor/font-awesome/css/font-awesome.min.css

* resource url  = `http://localhost/test`
* resource path = `/vendor/maximebf/debugbar/src/DebugBar/Resources`
* debugbar path = `/vendor/font-awesome/css/font-awesome.min.css`

resource url and resource path can be defined with methods :
 - `setAssetsResourcesUrl(string $url)`
 - `setAssetsResourcesPath(string $path)`
 
Example if you use routing :

you have to define a route that serve PHPDebugBar assets :

```
$debugBar->setAssetsResourcesUrl('/');
$debugBar->setAssetsResourcesPath('debugbar/');

/*
this will load :
 - /debugbar/vendor/font-awesome/css/font-awesome.min.css
 - /debugbar/openhandler.js
*/
```

##### Adding Collectors
For general documentation, look in the really complete documentation of phpdebugbar : 
http://phpdebugbar.com/docs/data-collectors.html#using-collectors 

#### Adding default Collectors

Add default collectors :
    `$debugbar->addDefaultCollectors()`
OR
    `$debugbar->addCollector(DebugBar\DataCollector\DataCollectorInterface $collector)`

#### ATK4 Logger Collector

if there is an already defined LoggerInterface in the app it will act as a proxy.

add it in this way : `$debugBar->addATK4LoggerCollector()`

To interact with the logger :
 - if your App implements `DebugTrait` any calls to `LoggerInterface` methods
 
    example : `$app->info('test msg')`
     
 - if your App implements or not `DebugTrait` , you can call it this way : `$app->getDebugBarCollector('atk4-logger')->info('test');` or any other LoggerInterface methods 

#### ATK4 Persistence\SQL Collector

 - added in this way `$debugBar->addATK4PersistenceSQLCollector();`, it will add logging to $this->app->db ( $app->db must exists ).
 - added in this way `$debugBar->addATK4PersistenceSQLCollector($persistence);`, it will add logging to $persistence that must be instance of Persistence\SQL.

No interaction excepted, just add logs to PHPDebugBar of every call to PDO made by Persistence\SQL  


#### Helpers

on Init, ATK4PHPDebugBar will add 3 dynamic methods to AppScope :

 - `getDebugBar() : \DebugBar\DebugBar`
    
    return object `\DebugBar\DebugBar`
 
 - `getDebugBarCollector($collector_name) : DebugBar\DataCollector\DataCollectorInterface`
    
    shorthand to `\DebugBar\DebugBar::getCollector`
      
 - `hasDebugBarCollector($collector_name) : bool;`
 
    shorthand to `\DebugBar\DebugBar::hasCollector`
    
### TODO...

Add Unit Test and more examples
