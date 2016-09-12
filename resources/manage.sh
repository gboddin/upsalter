#!/bin/bash
# small rewrite of manage for salt usage
MY_DIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )
PROOT_ARGS=""

cd ${MY_DIR}

[ -f proot.cfg ] && source proot.cfg

my_ld_library_path="${MY_DIR}/lib64:${MY_DIR}/usr/lib64"

while read lib_path; do
    my_ld_library_path="${MY_DIR}${lib_path}:${my_ld_library_path}"
done < <(cat "${MY_DIR}/etc/ld.so.conf.d"/* 2> /dev/null | grep -v '^#')

my_path=${MY_DIR}/bin:${MY_DIR}/sbin:${MY_DIR}/usr/bin:${MY_DIR}/usr/sbin:${MY_DIR}/usr/local/bin:${MY_DIR}/usr/local/bin:

proot_run="HOME=/root SHELL=/bin/bash PATH=/bin:/sbin:/usr/sbin:/usr/bin ./proot ${PROOT_ARGS} -w /root -b ${MY_DIR} -b /etc/mtab -b /etc/resolv.conf -b /etc/hostname -b /dev -b /sys -b /proc -r ${MY_DIR} -0"

if [ "$1" = "start-minion" ]; then
        echo "Starting root minion supervisor ..."
        $proot_run /usr/bin/supervisord -c /etc/supervisor/supervisord.conf > proot.log 2> proot.err &
fi

if [ "$1" = "stop-minion" ]; then
        echo "Stopping root minion supervisor ..."
        $proot_run supervisorctl shutdown
fi

if [ "$1" = "admin" ]; then
        echo "Dropping admin shell ..."
        shift
        $proot_run "$@"
fi

if [ "$1" = "start-user" ]; then
    shift
    export LD_LIBRARY_PATH=${my_ld_library_path}
    export PATH=${my_path}
    export PYTHONHOME=${MY_DIR}/usr
    echo $LD_LIBRARY_PATH $PATH $PYTHONHOME
    ${MY_DIR}/lib64/ld-linux-x86-64.so.2 ${MY_DIR}/usr/bin/python ${MY_DIR}/usr/bin/supervisord -c ${MY_DIR}/etc/supervisor-${USER}/supervisor.conf
fi

if [ "$1" = "stop-user" ]; then
    shift
    export LD_LIBRARY_PATH=${my_ld_library_path}
    export PATH=${my_path}
    export PYTHONHOME=${MY_DIR}/usr
    echo $LD_LIBRARY_PATH $PATH $PYTHONHOME
    ${MY_DIR}/lib64/ld-linux-x86-64.so.2 ${MY_DIR}/usr/bin/python ${MY_DIR}/usr/bin/supervisorctl -c ${MY_DIR}/etc/supervisor-${USER}/supervisor.conf shutdown
fi