# OphanimFlow
NetFlow aggregation and graph toolkit

# FreeBSD 13.2 batch setup

```
# fetch https://raw.githubusercontent.com/nightflyza/OphanimFlow/main/dist/batchsetup132.sh
# sh batchsetup132.sh
```

# Automatic upgrade OphanimFlow to latest build

```
# /bin/autoofupdate.sh
```


# NetFlow software sensor usage example

```
# softflowd -i bridge0 -s 100 -t udp=60 -t tcp=60 -t icmp=60 -t general=60 -t maxlife=60 -t tcp.rst=60 -t tcp.fin=60 -n 192.168.0.220:42112
```


# REST API

The REST API has several endpoints for getting preprocessed data as well as graphs. Details of the payloads and endpoints are below.
All API requests performs as GET requests with some parameters to base URL like http://yourhost/of/ 

## graph

This API call returns traffic graph, with distribution by traffic classes as PNG image for some specified IP address. Parameter "ip" - is mandatory. All other is optional.

All endpoint parameters:

- ip - IP address in format x.x.x.x
- dir - traffic direction. Possible values: R, S that points to "received" and "sent". Default: R.
- period - possible values: day, week, month, year. Default: day.
- w - width of graph image in pixels. Default : 1540.
- h - heigth of graph image in pixels. Default: 400.

Minimal example:
```
?module=graph&ip=172.30.73.247
```

Returns something like this for a current day

![of1](https://github.com/nightflyza/OphanimFlow/assets/1496954/efc90007-b814-4257-9a5e-c1835b527db0)

or 

```
?module=graph&ip=172.30.73.247&period=week
```

like this for a week

![of2](https://github.com/nightflyza/OphanimFlow/assets/1496954/eacdf6e3-0992-4f5c-8821-9092526b2463)


Full example:
```
?module=graph&dir=R&period=week&ip=172.16.68.173&w=1300&h=400
```

## gettraff

This API call returns JSON array of all traffic summary collected by some period for all or specified IP address.

Optional endpoint parameters:

- year - year of summary.
- month - month number to return traffic summary with leading zero.
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
