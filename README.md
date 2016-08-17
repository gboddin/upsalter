# WIP WIP WIP NOTHING READY YET - UPSALTER

## Introduction

Upsalter was designed to create a rootfs image, possibly with a salt-minion installed that you can deploy anywhere.

## Usage

### Create salted chroot image

It will build a rootfs image of your favorite distribution with salt installed

```sh
./bin/upsalter chroot:build centos 6 centos6-salted.tar.gz
```

### Deploy chroot image

It will deploy your chroot package to a remote server and register it to your salt server

```sh
./bin/upsalter chroot:deploy centos6.tar.gz user@server:directory my-container-id
```