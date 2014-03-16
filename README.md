Opauth-Basecamp
=============
[Opauth][1] strategy for Basecamp authentication.

Implemented based on https://github.com/basecamp/bcx-api

Getting started
----------------
1. Install Opauth-Basecamp:
   ```bash
   cd path_to_opauth/Strategy
   git clone https://github.com/t1mmen/opauth-basecamp.git Basecamp
   ```

2. Create Basecamp application at https://integrate.37signals.com/

3. Configure Opauth-Basecamp strategy with at least `Client ID` and `Client Secret`.

4. Direct user to `http://path_to_opauth/basecamp` to authenticate

Strategy configuration
----------------------

Required parameters:

```php
<?php
'Basecamp' => array(
	'client_id' => 'YOUR CLIENT ID',
	'client_secret' => 'YOUR CLIENT SECRET'
)
```

License
---------
Opauth-Basecamp is MIT Licensed
Copyright © 2014 Timm Stokke (http://timm.stokke.me)

[1]: https://github.com/opauth/opauth
