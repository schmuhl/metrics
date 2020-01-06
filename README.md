# metrics
Quick and dirty metric recording and reporting

##Installation##
Drop a file called config.inc into the directory with the path to the configuration file. It should be accessible to the PHP but not to be served by Apache. It should look something like this:
```php
<?php $configPath = '../config-metrics.json'; ?>
```

Here is the default configuration JSON required by the template.inc via that config.inc file:
```json
{"name":"Metrics","timezone":"UTC","database":{"host":"127.0.0.1","database":"metrics","username":"root","password":"root1234"}}
```
