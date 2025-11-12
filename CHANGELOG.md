# Changelog

All notable changes to this project will be documented in this file.

## [0.0.6] - in development now
- alter.ini: new option UBILLING_URL that sets full URL of Ubilling >=1.5.2 for integration
- alter.ini: new option UBILLING_API_KEY that sets Ubilling API key aka serial for integration
- alter.ini: new option CHARTS_PREALLOC_TIMELINE implemented. It allows render more human familiar graph look.
- protofilter: optional ability to show remote Ubilling user data in reports
- graph: now optional can preallocate timeline depend on selected time period.
- graph: fixed timestamp offsets. Now its calculated depends on selected period instead of available datasize.
- graph: dates format changed to ISO 8601 to avoid ordering issues.
- global: gravatar/libravatar replaced with facekit
- installer: Debian 13.0 trixie tested and works
- installer: FreeBSD 14.3 tested and works

## [0.0.5] - rev 110

- alter.ini: new option STORAGE_RESERVED_SPACE that sets reserved free storage percent (10% by default)
- alter.ini: new option ROTATOR_DEBUG that sets rotator debug flag
- rotator now works and preserves some disk space


## [0.0.4] - rev 100

- installer: FreeBSD 13.4 setup tested
- dashboard: the IP list should not be broken anymore
- graph: fixed traffic classes rendering... again...

## [0.0.3] - rev 99

- alter.ini: new option CHARTS_ACCURATE that enables more detailed and accurate charts instead of maximum performance
- alter.ini: new option CONSIDER_VLANS that enables VLANs filters for v9/IPFIX flows.
- graph: now different classes of traffic are not overdrawn by the total column by default
- graph: explict period option implemented
- dashboard: better system info UX
- installer: FreeBSD 14.1 setup tested

## [0.0.2] - rev 88

- alter.ini: new option CHARTS_NETDESC that enables networks description render on charts
- settings: networks descriptions implemented
- graph: optional ability to render network description or its CIDR on charts
- graph: cosmetic issues fixed, better data vizualization
- graph: additional debug counters with drawcalls
- graph: significant performance improvements with ChartMancer 0.0.9 lib.
- protofilter: optional ability to render network description or its CIDR on charts
- installer: FreeBSD 13.2 setup works again.
- installer: FreeBSD 13.3 setup tested and works
- installer: Linux Debian 12.5 bookworm installer

## [0.0.1] - rev 77

- protofilter: implemented time depth selector and separate direction filters

## [0.0.1] - rev 76

- graph: added 24h and 48h periods

## [0.0.1] - rev 72

- classifier: sip proto filter added
- protofilter: draft implementation

## [0.0.1] - rev 58

- classifier: rtsp proto filter replaced with proxy filter.
