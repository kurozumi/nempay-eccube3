# simple_nempay-eccube3

## Overview
EC-CUBE 3 series Nem (Xem) payment plug-in.
By installing this plug-in you can settle in Xem.

## Demo

[Demo](http://nem-ec.tech/eccube3/)

## Install
1. Create plug-in file
```bash
$ git clone git@github.com:maroemon58/simple_nempay-eccube3.git
$ cd simple_nempay-eccube3
$ tar -zcvf SimpleNemPay.tar.gz *
```

2. Install on EC-CUBE
Install the created plug-in(SimpleNemPay.tar.gz) from "owner's store > plugin > plugin list"

3. Plug-in setting
Register an auctioneer account (deposit destination)
**※ When testing, switch "Environment switch" to test environment(testnet)**

4. Payment confirmation setting
Set up confirmation program to activate payment every fixed time
Program：〜/SimpleNemPay/Command/PaymentConfirmBatchCommand.php
```bash
# 1.change console.
$ cd /var/www/html/eccube3/
$ vim app/console
# add below.
$console->add(new Plugin\SimpleNemPay\Command\PaymentConfirmBatchCommand(new Eccube\Application()));
# 2.set crontab
$ crontab -e
*/5 * * * * /usr/bin/php /var/www/html/eccube-3.0.15/app/console simple_nempay:payment_confirm
```
  
## Licence

[GNU](https://github.com/maroemon58/simple_nempay-eccube3/blob/master/LICENSE)

## Author

[maroemon58](https://github.com/maroemon58)