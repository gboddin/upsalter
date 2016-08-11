# UPSALTER

## Introduction

Upsalter was designed to create a rootfs image, possibly with a salt-minion installed that you can deploy anywhere.

## Usage

### Create chroot image

It will build a rootfs image of your favorite distribution

```sh
./bin/upsalter chroot:build centos 6 centos6.tar.gz
```

### Deploy chroot image

It will deploy your chroot package to a remote server

```sh
./bin/upsalter chroot:deploy centos6.tar.gz user@server:directory
```

### Use proot to run the container with supervisor and register it on your salt master

It will install proot and supervisord in your container, and register it so salt-minion will be started a boot time

```sh
./bin/upsalter chroot:register-proot-minion user@server:directory minion-id yoursaltmaster.address.net
```