# OphanimFlow
NetFlow aggregation and graph toolkit. 

Basic idea is replacement of bandwidthd and Stargazer cap_nf module in one solution, which performs NetFlow data collecting, classification, preprocessing and performing network bandwidth utilization graphs rendering per each host in your network and basic traffic accounting of it, somewhere on some dedicated host.

# FreeBSD 13.5/14.2/14.3/15.0 batch setup

ninja way

```
# fetch https://raw.githubusercontent.com/nightflyza/OphanimFlow/main/dist/batchfreebsd.sh && sh batchfreebsd.sh
```

# Debian 13.0 trixie batch setup
```
$ su -
# wget https://raw.githubusercontent.com/nightflyza/OphanimFlow/main/dist/batchdebian.sh && sh batchdebian.sh
```

After that, a simple web interface will be available to you at a link like http://yourhost/of/, which will allow you to make the minimum necessary settings, such as specifying your networks, and start using OphanimFlow. The default login is "admin", the default password is "demo". Don't forget to change it in the user profile settings.

![ofdashboard](https://github.com/nightflyza/OphanimFlow/assets/1496954/df650ff6-1113-4c92-93d6-6f6371799e2f)

# Upgrade OphanimFlow to latest build

Just run the script

```
# /bin/autoofupdate.sh
```

and stay tuned! ;)

# NetFlow software sensor usage example

Default NetFlow collector UDP port is 42112 and default sampling rate is 100. Flows data dumps to database every 5 minutes, and preprocesses every 5 minutes for charts and every 10 minutes for summary traffic counters, so 

```
# softflowd -i bridge0 -s 100 -t udp=60 -t tcp=60 -t icmp=60 -t general=60 -t maxlife=60 -t tcp.rst=60 -t tcp.fin=60 -n 192.168.0.220:42112
```


# REST API

The REST API has several endpoints for getting preprocessed data as well as graphs. So you can use OphanimFlow data in your external apps, somethink like that:

![opharchabstract](https://github.com/nightflyza/OphanimFlow/assets/1496954/0115ecc1-7d6f-473c-885a-169d01f5f04e)

Details of the payloads and endpoints are below.
All API requests performs as GET requests with some parameters to base URL like http://yourhost/of/ 

## graph

This API call returns traffic graph, with distribution by traffic classes as PNG image for some specified IP address. Parameter "ip" - is mandatory. All other is optional.

All endpoint parameters:

- ip - IP address in format x.x.x.x
- dir - traffic direction. Possible values: R, S that points to "received" and "sent". Default: R.
- period - possible values: hour, day, week, month, year, 24h, 48h, explict. Default: day.
- w - width of graph image in pixels. Default : 1540.
- h - heigth of graph image in pixels. Default: 400.

Minimal example:
```
?module=graph&ip=172.30.73.247
```

Returns something like this for a current day

![day](https://github.com/nightflyza/OphanimFlow/assets/1496954/54296af6-9e7a-4145-b301-37c243df87d7)


or 

```
?module=graph&ip=172.30.73.247&period=week
```

like this for a week

![week](https://github.com/nightflyza/OphanimFlow/assets/1496954/9eaf8f1f-7bd6-43f5-a165-d38ae37ad141)

or 

```
?module=graph&ip=172.30.73.247&period=hour
```
![hour](https://github.com/nightflyza/OphanimFlow/assets/1496954/5c5b4c0a-5cea-4114-aaab-ae16f729b411)

like this for a past hour

explict period requires set of two UNIX timestamp GET variables - "from" and "to"

Explict period usage:
```
?module=graph&dir=R&ip=0.0.0.0&period=explict&from=1115779033&to=1715782633
```

Full example with custom dimensions:
```
?module=graph&dir=R&period=week&ip=172.16.68.173&w=1300&h=400
```

IP 0.0.0.0 returns summary bandwidth chart for all tracked hosts.

Data debugging: requires "dumpdata" GET parameter.

```
?module=graph&dir=R&period=hour&ip=0.0.0.0&dumpdata=true
```

returns something like this instead graph:
```
==== original data ====
[stat] data keys count: 10
Array
(
    [15:30] => Array
        (
            [0] => 0.071194966634115
            [1] => 0
            [2] => 0
            [3] => 0.071194966634115
            [4] => 0
            [5] => 0
            [6] => 0
            [7] => 0
            [8] => 0
            [9] => 0
            [10] => 0
            [11] => 0
        )

    [15:35] => Array
        (
            [0] => 0.067311604817708
            [1] => 0
            [2] => 0
            [3] => 0.067311604817708
            [4] => 0
            [5] => 0
            [6] => 0
            [7] => 0
            [8] => 0
            [9] => 0
            [10] => 0
            [11] => 0
        )
.....

==== mixed with timeline ====
[stat] data keys count: 14
Array
(
    [15:25] => Array
        (
            [0] => 0
            [1] => 0
            [2] => 0
            [3] => 0
            [4] => 0
            [5] => 0
            [6] => 0
            [7] => 0
            [8] => 0
            [9] => 0
            [10] => 0
            [11] => 0
        )

    [15:30] => Array
        (
            [0] => 0.071194966634115
            [1] => 0
            [2] => 0
            [3] => 0.071194966634115
            [4] => 0
            [5] => 0
            [6] => 0
            [7] => 0
            [8] => 0
            [9] => 0
            [10] => 0
            [11] => 0
        )
```

## gettraff

This API call returns JSON array of all traffic summary collected by some period for all or specified IP address.

Optional endpoint parameters:

- year - year of summary.
- month - month number to return traffic summary without leading zero.
- ip - IP address in format x.x.x.x

Minimal example:
```
?module=gettraff
```

Returns something like

```
{

    "172.16.1.175":{
        "dl":"66912063800",
        "ul":"2691439300"
    },
    "172.16.25.75":{
        "dl":"145398529200",
        "ul":"10223157100"
    },
    "172.16.60.149":{
        "dl":"49337740100",
        "ul":"7548407400"
    },
    ....
```
with data for current month "dl" - bytes downloaded, ul - bytes uploaded.

Full example:
```
?module=gettraff&year=2023&month=12&ip=172.16.1.33
```
Returns:

```
{
    "172.16.1.33":{
        "dl":"190673220400",
        "ul":"9911547100"
    }

}
```

as just data for specified IP 172.16.1.33 for december 2023.

# More configuration

While the API endpoints is currently accessible without any authorization, you may want to limit the list of hosts IPs that can receive data from them. This is done using the ENDPOINTS_HOSTS option in the of/config/alter.ini configuration file. Something like:

```
ENDPOINTS_HOSTS="192.168.0.8,192.168.42.56"
```

Also you may want to change NetFlow collector port or sampling rate, you also can do this in the same alter.ini config file using following options:

```
;NetFlow collector default options
COLLECTOR_PORT=42112
SAMPLING_RATE=100
```

dont forget regenerate configuration and restart collector after this

Also OphanimFlow from release 0.0.5 automatically rotates and flushes old data to keep some storage space reserved and prevent it from exhausting. Its 10% of total storage size by default. This behaviour is controlled by following options:

```
;Reserved storage free space percent
STORAGE_RESERVED_SPACE=10
;write data rotator debug log into exports/rotator.log?
ROTATOR_DEBUG=0
```
