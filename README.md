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


# NetFlow sensor example

```
# softflowd -i bridge0 -s 100 -t udp=60 -t tcp=60 -t icmp=60 -t general=60 -t maxlife=60 -t tcp.rst=60 -t tcp.fin=60 -n 192.168.0.220:42112
```